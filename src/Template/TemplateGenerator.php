<?php
declare(strict_types=1);

namespace ViteBake\Template;

use Cake\Console\ConsoleIo;

/**
 * テンプレートジェネレーター
 *
 * Viteセットアップに必要な設定ファイルを生成するクラスです。
 *
 * ## 役割
 *
 * このクラスは以下のファイルを動的に生成します:
 * - vite.config.ts: Viteのビルド設定
 * - tsconfig.json: TypeScript設定（TypeScript使用時）
 * - tailwind.config.js: Tailwind CSS設定（Tailwind使用時）
 * - postcss.config.js: PostCSS設定（Tailwind使用時）
 * - config/app_vite.php: CakePHP ViteHelper設定
 * - resources/js/main.{tsx,jsx,ts,js}: エントリーポイント
 * - resources/js/App.{tsx,jsx}: Appコンポーネント（React/Vue使用時）
 * - resources/css/app.css: スタイルシート
 * - templates/layout/vite.php: サンプルレイアウト
 * - package.json: npm依存関係（更新または新規作成）
 *
 * ## 設計思想
 *
 * 各ファイルの内容は専用のprotectedメソッドで生成します。
 * これにより、将来の変更やカスタマイズが容易になります。
 *
 * ヒアドキュメント（<<<EOF ... EOF）を活用して、
 * テンプレートの可読性を高めています。
 */
class TemplateGenerator
{
    /**
     * 設定配列
     *
     * ViteBakeCommandから渡される設定です。
     * 以下のキーを含みます:
     * - framework: 'react' | 'vue' | 'vanilla'
     * - typescript: bool
     * - tailwind: bool
     *
     * @var array<string, mixed>
     */
    protected array $config;

    /**
     * 強制上書きフラグ
     *
     * trueの場合、既存のファイルを上書きします。
     * --force オプションに対応します。
     */
    protected bool $force;

    /**
     * プロジェクトルートパス
     *
     * ファイルを生成する基準となるディレクトリです。
     * CakePHPの定数 ROOT を使用します。
     * 末尾にディレクトリセパレータが付きます。
     */
    protected string $rootPath;

    /**
     * コンストラクタ
     *
     * @param array<string, mixed> $config 設定配列（framework, typescript, tailwind）
     * @param bool $force 強制上書きするか（デフォルト: false）
     */
    public function __construct(array $config, bool $force = false)
    {
        $this->config = $config;
        $this->force = $force;
        // ROOT はCakePHPが定義するプロジェクトルートの定数
        // DS はディレクトリセパレータ（Windows: \、Unix: /）
        $this->rootPath = ROOT . DS;
    }

    /**
     * 全ファイルを生成
     *
     * 設定に基づいて必要なファイルをすべて生成します。
     *
     * 処理の流れ:
     * 1. 生成するファイルのリストを取得
     * 2. 各ファイルをディスクに書き込み
     * 3. package.jsonを更新
     *
     * @param \Cake\Console\ConsoleIo $io コンソール入出力（進捗表示用）
     * @return bool 成功した場合true
     */
    public function generate(ConsoleIo $io): bool
    {
        // 生成するファイルのリスト（相対パス => 内容）
        $files = $this->getFilesToGenerate();

        // 各ファイルを書き込み
        foreach ($files as $path => $content) {
            if (!$this->writeFile($path, $content, $io)) {
                return false;
            }
        }

        // package.jsonは既存ファイルとマージするため別処理
        if (!$this->updatePackageJson($io)) {
            return false;
        }

        return true;
    }

    /**
     * 生成するファイルのリストを取得
     *
     * 設定に基づいて、生成が必要なファイルとその内容を返します。
     * 返り値は「相対パス => ファイル内容」の連想配列です。
     *
     * ## ファイル拡張子の決定ロジック
     *
     * - React/Vue + TypeScript: .tsx
     * - React/Vue + JavaScript: .jsx
     * - Vanilla + TypeScript: .ts
     * - Vanilla + JavaScript: .js
     *
     * @return array<string, string> ファイルパス => 内容の配列
     */
    protected function getFilesToGenerate(): array
    {
        $files = [];

        // Vite設定ファイル（常に生成）
        $files['vite.config.ts'] = $this->getViteConfig();

        // TypeScript設定（TypeScript使用時のみ）
        if ($this->config['typescript']) {
            $files['tsconfig.json'] = $this->getTsConfig();
        }

        // Tailwind関連設定（Tailwind使用時のみ）
        if ($this->config['tailwind']) {
            $files['tailwind.config.js'] = $this->getTailwindConfig();
            $files['postcss.config.js'] = $this->getPostCssConfig();
        }

        // CakePHP ViteHelper設定ファイル
        $files['config' . DS . 'app_vite.php'] = $this->getCakeViteConfig();

        // エントリーファイルの拡張子を決定
        $ext = $this->config['typescript'] ? 'tsx' : 'jsx';
        if ($this->config['framework'] === 'vanilla') {
            // Vanillaモードは.tsx/.jsxではなく.ts/.jsを使用
            $ext = $this->config['typescript'] ? 'ts' : 'js';
        }

        // メインエントリーポイント
        $files['resources' . DS . 'js' . DS . "main.{$ext}"] = $this->getMainJs();

        // Appコンポーネント（React/Vueのみ）
        if ($this->config['framework'] !== 'vanilla') {
            $files['resources' . DS . 'js' . DS . "App.{$ext}"] = $this->getAppComponent();
        }

        // CSSファイル
        $files['resources' . DS . 'css' . DS . 'app.css'] = $this->getAppCss();

        // サンプルレイアウトテンプレート
        $files['templates' . DS . 'layout' . DS . 'vite.php'] = $this->getLayoutTemplate();

        return $files;
    }

    /**
     * ファイルをディスクに書き込み
     *
     * ファイルの存在確認、ディレクトリ作成、書き込みを行います。
     *
     * ## 上書き動作
     *
     * - force=false かつ ファイルが存在: スキップ（警告表示）
     * - force=true または ファイルが存在しない: 書き込み
     *
     * @param string $relativePath プロジェクトルートからの相対パス
     * @param string $content ファイル内容
     * @param \Cake\Console\ConsoleIo $io コンソール入出力
     * @return bool 成功した場合true
     */
    protected function writeFile(string $relativePath, string $content, ConsoleIo $io): bool
    {
        $fullPath = $this->rootPath . $relativePath;
        $dir = dirname($fullPath);

        // ファイルが既に存在し、強制上書きでない場合はスキップ
        if (file_exists($fullPath) && !$this->force) {
            $io->warning("  Skipped: {$relativePath} (already exists, use --force to overwrite)");

            return true; // スキップは成功扱い
        }

        // ディレクトリが存在しない場合は作成
        if (!is_dir($dir)) {
            // 第3引数のtrueで再帰的にディレクトリを作成
            if (!mkdir($dir, 0755, true)) {
                $io->error("  Failed to create directory: {$dir}");

                return false;
            }
        }

        // ファイルを書き込み
        if (file_put_contents($fullPath, $content) === false) {
            $io->error("  Failed to write: {$relativePath}");

            return false;
        }

        // 成功メッセージを表示
        $io->success("  Created: {$relativePath}");

        return true;
    }

    /**
     * package.jsonを更新または作成
     *
     * 既存のpackage.jsonがあればマージし、なければ新規作成します。
     *
     * ## マージロジック
     *
     * - type: 上書き（'module'固定）
     * - scripts: 上書き（dev, build, preview）
     * - dependencies: 既存 + 新規（新規が優先）
     * - devDependencies: 既存 + 新規（新規が優先）
     *
     * @param \Cake\Console\ConsoleIo $io コンソール入出力
     * @return bool 成功した場合true
     */
    protected function updatePackageJson(ConsoleIo $io): bool
    {
        $packageJsonPath = $this->rootPath . 'package.json';
        $packageJson = [];

        // 既存のpackage.jsonを読み込み（存在する場合）
        if (file_exists($packageJsonPath)) {
            $content = file_get_contents($packageJsonPath);
            if ($content !== false) {
                $packageJson = json_decode($content, true) ?? [];
            }
        }

        // 新しい設定を取得
        $newConfig = $this->getPackageJsonConfig();

        // type フィールドを設定（ESモジュールとして扱う）
        $packageJson['type'] = $newConfig['type'];

        // scripts を上書き（Vite用のスクリプト）
        $packageJson['scripts'] = $newConfig['scripts'];

        // dependencies をマージ（新規値で上書き）
        $packageJson['dependencies'] = array_merge(
            $packageJson['dependencies'] ?? [],
            $newConfig['dependencies'],
        );

        // devDependencies をマージ（新規値で上書き）
        $packageJson['devDependencies'] = array_merge(
            $packageJson['devDependencies'] ?? [],
            $newConfig['devDependencies'],
        );

        // JSONとして書き込み（整形 + スラッシュをエスケープしない）
        $content = json_encode($packageJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        if (file_put_contents($packageJsonPath, $content) === false) {
            $io->error('  Failed to update: package.json');

            return false;
        }

        $io->success('  Updated: package.json');

        return true;
    }

    /**
     * Vite設定ファイルの内容を生成
     *
     * フレームワークに応じてプラグインを追加します。
     *
     * ## プラグイン構成
     *
     * - 共通: vite-plugin-live-reload（PHPファイル変更時にリロード）
     * - React: @vitejs/plugin-react
     * - Vue: @vitejs/plugin-vue
     *
     * @return string vite.config.ts の内容
     */
    protected function getViteConfig(): string
    {
        // プラグインのリスト（配列で管理し、後で結合）
        $plugins = ["liveReload(['templates/**/*.php'])"];
        $pluginImports = ["import liveReload from 'vite-plugin-live-reload';"];

        // フレームワーク別のプラグインを追加
        if ($this->config['framework'] === 'react') {
            $pluginImports[] = "import react from '@vitejs/plugin-react';";
            // Reactプラグインを先頭に追加
            array_unshift($plugins, 'react()');
        } elseif ($this->config['framework'] === 'vue') {
            $pluginImports[] = "import vue from '@vitejs/plugin-vue';";
            array_unshift($plugins, 'vue()');
        }

        // 配列を文字列に変換
        $pluginImportsStr = implode("\n", $pluginImports);
        $pluginsStr = implode(",\n    ", $plugins);

        // エントリーファイルの拡張子を決定
        $ext = $this->config['typescript'] ? 'tsx' : 'jsx';
        if ($this->config['framework'] === 'vanilla') {
            $ext = $this->config['typescript'] ? 'ts' : 'js';
        }

        // ヒアドキュメントでテンプレートを生成
        return <<<VITE
import { defineConfig } from 'vite';
import { resolve } from 'path';
{$pluginImportsStr}

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [
    {$pluginsStr},
  ],
  build: {
    outDir: 'webroot/dist',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: {
        main: resolve(__dirname, 'resources/js/main.{$ext}'),
      },
    },
  },
  server: {
    port: 3000,
    strictPort: true,
    cors: true,
    hmr: {
      protocol: 'ws',
      host: 'localhost',
    },
  },
  base: '/dist/',
});
VITE;
    }

    /**
     * TypeScript設定ファイルの内容を生成
     *
     * フレームワークに応じてJSX設定を変更します。
     *
     * ## JSX設定
     *
     * - React: 'react-jsx'（React 17+の新JSX変換）
     * - Vue/Vanilla: 'preserve'（変換しない）
     *
     * @return string tsconfig.json の内容
     */
    protected function getTsConfig(): string
    {
        // ReactはReact17+の新JSX変換を使用
        $jsx = $this->config['framework'] === 'react' ? 'react-jsx' : 'preserve';

        return <<<JSON
{
  "compilerOptions": {
    "target": "ES2020",
    "useDefineForClassFields": true,
    "lib": ["ES2020", "DOM", "DOM.Iterable"],
    "module": "ESNext",
    "skipLibCheck": true,
    "moduleResolution": "bundler",
    "allowImportingTsExtensions": true,
    "resolveJsonModule": true,
    "isolatedModules": true,
    "noEmit": true,
    "jsx": "{$jsx}",
    "strict": true,
    "noUnusedLocals": true,
    "noUnusedParameters": true,
    "noFallthroughCasesInSwitch": true,
    "paths": {
      "@/*": ["./resources/js/*"]
    }
  },
  "include": ["resources/**/*"]
}
JSON;
    }

    /**
     * Tailwind CSS設定ファイルの内容を生成
     *
     * contentでスキャン対象のファイルを指定しています。
     * これらのファイルで使用されているクラスのみがビルドに含まれます。
     *
     * @return string tailwind.config.js の内容
     */
    protected function getTailwindConfig(): string
    {
        return <<<JS
/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './resources/**/*.{js,ts,jsx,tsx,vue}',
    './templates/**/*.php',
  ],
  theme: {
    extend: {},
  },
  plugins: [],
};
JS;
    }

    /**
     * PostCSS設定ファイルの内容を生成
     *
     * TailwindCSSとautoprefixerを有効にします。
     * autoprefixerはベンダープレフィックスを自動追加します。
     *
     * @return string postcss.config.js の内容
     */
    protected function getPostCssConfig(): string
    {
        return <<<JS
export default {
  plugins: {
    tailwindcss: {},
    autoprefixer: {},
  },
};
JS;
    }

    /**
     * CakePHP ViteHelper設定ファイルの内容を生成
     *
     * cakephp-viteプラグイン用の設定ファイルです。
     * 開発時と本番時の動作を制御します。
     *
     * ## 設定項目
     *
     * - build.outDirectory: ビルド出力先
     * - build.manifest: マニフェストファイルのパス
     * - development.scriptEntries: 開発時のエントリーポイント
     * - development.hostNeedles: 開発環境判定に使うホスト名
     * - development.url: Vite開発サーバーのURL
     *
     * @see https://github.com/brandcom/cakephp-vite
     * @return string config/app_vite.php の内容
     */
    protected function getCakeViteConfig(): string
    {
        // エントリーファイルの拡張子を決定
        $ext = $this->config['typescript'] ? 'tsx' : 'jsx';
        if ($this->config['framework'] === 'vanilla') {
            $ext = $this->config['typescript'] ? 'ts' : 'js';
        }

        return <<<PHP
<?php
/**
 * ViteHelper 設定ファイル
 *
 * cakephp-vite プラグインの設定です。
 * このファイルは config/bootstrap.php などで読み込んでください。
 *
 * @see https://github.com/brandcom/cakephp-vite
 */
return [
    'ViteHelper' => [
        // 本番ビルド設定
        'build' => [
            'outDirectory' => 'dist',
            'manifest' => WWW_ROOT . 'dist' . DS . '.vite' . DS . 'manifest.json',
        ],
        // 開発環境設定
        'development' => [
            'scriptEntries' => ['resources/js/main.{$ext}'],
            'styleEntries' => [],
            // これらの文字列がホスト名に含まれていれば開発モード
            'hostNeedles' => ['.test', '.local', 'localhost'],
            'url' => 'http://localhost:3000/dist',
        ],
        // trueにすると常に本番モードで動作
        'forceProductionMode' => false,
    ],
];
PHP;
    }

    /**
     * メインエントリーファイルの内容を生成
     *
     * フレームワークに応じて異なるエントリーポイントを生成します。
     *
     * @return string main.{tsx,jsx,ts,js} の内容
     */
    protected function getMainJs(): string
    {
        $cssImport = '../css/app.css';

        if ($this->config['framework'] === 'react') {
            return $this->getReactMain($cssImport);
        }

        if ($this->config['framework'] === 'vue') {
            return $this->getVueMain($cssImport);
        }

        return $this->getVanillaMain($cssImport);
    }

    /**
     * React用メインエントリーファイルの内容を生成
     *
     * ReactDOMのcreateRootを使用して、
     * #app要素にReactアプリケーションをマウントします。
     *
     * @param string $cssImport CSSファイルのインポートパス
     * @return string React用 main.tsx/jsx の内容
     */
    protected function getReactMain(string $cssImport): string
    {
        // TypeScript使用時は型アサーションを追加
        $typeAnnotation = $this->config['typescript'] ? ' as HTMLElement' : '';

        return <<<TSX
import React from 'react';
import { createRoot } from 'react-dom/client';
import App from './App';
import '{$cssImport}';

const container = document.getElementById('app'){$typeAnnotation};

if (container) {
  const root = createRoot(container);
  root.render(
    <React.StrictMode>
      <App />
    </React.StrictMode>
  );
}
TSX;
    }

    /**
     * Vue用メインエントリーファイルの内容を生成
     *
     * Vue 3のcreateAppを使用して、
     * #app要素にVueアプリケーションをマウントします。
     *
     * @param string $cssImport CSSファイルのインポートパス
     * @return string Vue用 main.tsx/jsx の内容
     */
    protected function getVueMain(string $cssImport): string
    {
        return <<<TS
import { createApp } from 'vue';
import App from './App.vue';
import '{$cssImport}';

const container = document.getElementById('app');

if (container) {
  const app = createApp(App);
  app.mount(container);
}
TS;
    }

    /**
     * Vanilla JS用メインエントリーファイルの内容を生成
     *
     * フレームワークを使用しないシンプルなJavaScriptです。
     * DOMContentLoadedイベントで初期化します。
     *
     * @param string $cssImport CSSファイルのインポートパス
     * @return string Vanilla用 main.js/ts の内容
     */
    protected function getVanillaMain(string $cssImport): string
    {
        return <<<JS
import '{$cssImport}';

console.log('🍰 CakePHP + Vite is ready!');

document.addEventListener('DOMContentLoaded', () => {
  const app = document.getElementById('app');
  if (app) {
    app.innerHTML = '<h1>Hello from Vite!</h1>';
  }
});
JS;
    }

    /**
     * Appコンポーネントの内容を生成
     *
     * フレームワークに応じてReactまたはVueのコンポーネントを生成します。
     *
     * @return string App.tsx/jsx または App.vue の内容
     */
    protected function getAppComponent(): string
    {
        if ($this->config['framework'] === 'react') {
            return $this->getReactAppComponent();
        }

        return $this->getVueAppComponent();
    }

    /**
     * React用Appコンポーネントの内容を生成
     *
     * useStateフックを使ったカウンターのサンプルコンポーネントです。
     * Tailwind使用時はTailwindクラスを適用します。
     *
     * @return string React用 App.tsx/jsx の内容
     */
    protected function getReactAppComponent(): string
    {
        // TypeScript使用時は型パラメータを追加
        $stateType = $this->config['typescript'] ? '<number>' : '';

        // Tailwind使用時のクラス設定
        $tailwindClasses = $this->config['tailwind']
            ? 'className="min-h-screen bg-gray-100 flex items-center justify-center"'
            : '';
        $buttonClasses = $this->config['tailwind']
            ? 'className="mt-4 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors"'
            : '';
        $cardClasses = $this->config['tailwind']
            ? 'className="bg-white p-8 rounded-lg shadow-md text-center"'
            : '';
        $titleClasses = $this->config['tailwind']
            ? 'className="text-3xl font-bold text-gray-800"'
            : '';
        $countClasses = $this->config['tailwind']
            ? 'className="mt-4 text-xl text-gray-600"'
            : '';

        return <<<TSX
import { useState } from 'react';

function App() {
  const [count, setCount] = useState{$stateType}(0);

  return (
    <div {$tailwindClasses}>
      <div {$cardClasses}>
        <h1 {$titleClasses}>
          🍰 CakePHP + Vite + React
        </h1>
        <p {$countClasses}>Count: {count}</p>
        <button
          onClick={() => setCount(count + 1)}
          {$buttonClasses}
        >
          Increment
        </button>
      </div>
    </div>
  );
}

export default App;
TSX;
    }

    /**
     * Vue用Appコンポーネントの内容を生成
     *
     * Composition API（script setup）を使ったカウンターのサンプルです。
     * Tailwind使用時はTailwindクラスを適用します。
     *
     * @return string Vue用 App.vue の内容
     */
    protected function getVueAppComponent(): string
    {
        // Tailwind使用時のクラス設定
        $tailwindClasses = $this->config['tailwind']
            ? 'class="min-h-screen bg-gray-100 flex items-center justify-center"'
            : '';
        $cardClasses = $this->config['tailwind']
            ? 'class="bg-white p-8 rounded-lg shadow-md text-center"'
            : '';
        $titleClasses = $this->config['tailwind']
            ? 'class="text-3xl font-bold text-gray-800"'
            : '';
        $countClasses = $this->config['tailwind']
            ? 'class="mt-4 text-xl text-gray-600"'
            : '';
        $buttonClasses = $this->config['tailwind']
            ? 'class="mt-4 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors"'
            : '';

        // TypeScript使用時は型パラメータを追加
        $scriptContent = $this->config['typescript']
            ? "import { ref } from 'vue';\n\nconst count = ref<number>(0);"
            : "import { ref } from 'vue';\n\nconst count = ref(0);";

        return <<<VUE
<script setup lang="ts">
{$scriptContent}
</script>

<template>
  <div {$tailwindClasses}>
    <div {$cardClasses}>
      <h1 {$titleClasses}>
        🍰 CakePHP + Vite + Vue
      </h1>
      <p {$countClasses}>Count: {{ count }}</p>
      <button
        @click="count++"
        {$buttonClasses}
      >
        Increment
      </button>
    </div>
  </div>
</template>
VUE;
    }

    /**
     * app.cssの内容を生成
     *
     * Tailwind使用時はTailwindディレクティブを、
     * 未使用時は基本的なリセットスタイルを生成します。
     *
     * @return string app.css の内容
     */
    protected function getAppCss(): string
    {
        if ($this->config['tailwind']) {
            // Tailwindのディレクティブ（PostCSSで処理される）
            return <<<CSS
@tailwind base;
@tailwind components;
@tailwind utilities;

/* カスタムスタイルはここに追加 */
CSS;
        }

        // Tailwind未使用時の基本スタイル
        return <<<CSS
/* グローバルスタイル */
* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen,
    Ubuntu, Cantarell, 'Fira Sans', 'Droid Sans', 'Helvetica Neue', sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

#app {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
}
CSS;
    }

    /**
     * レイアウトテンプレートの内容を生成
     *
     * CakePHPのViteHelperを使用したサンプルレイアウトです。
     * templates/layout/default.php のベースとして使用できます。
     *
     * @return string templates/layout/vite.php の内容
     */
    protected function getLayoutTemplate(): string
    {
        // NOWDOCで生成（変数展開なし）
        return <<<'PHP'
<?php
/**
 * Vite レイアウトテンプレート
 *
 * ViteHelperを使用したサンプルレイアウトです。
 * このファイルを templates/layout/default.php にコピーするか、
 * 参考にして既存のレイアウトを修正してください。
 *
 * 使用方法:
 * 1. config/app_vite.php を読み込む設定を追加
 * 2. AppView で ViteScripts ヘルパーを読み込む
 * 3. このレイアウトを使用（または参考に修正）
 */
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $this->fetch('title') ?></title>

    <?php
    // Viteのアセット（CSS/JS）を読み込み
    // 開発時: Vite開発サーバーから
    // 本番時: ビルドされたファイルから
    $this->ViteScripts->script();
    ?>

    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
</head>
<body>
    <div id="app">
        <?= $this->fetch('content') ?>
    </div>

    <?= $this->fetch('script') ?>
</body>
</html>
PHP;
    }

    /**
     * package.jsonの設定を生成
     *
     * 選択したフレームワークとオプションに応じて、
     * 必要な依存関係を設定します。
     *
     * ## 依存関係の分類
     *
     * - dependencies: 本番環境でも必要（react, vue等）
     * - devDependencies: 開発時のみ必要（vite, typescript等）
     *
     * @return array<string, mixed> package.jsonの設定配列
     */
    protected function getPackageJsonConfig(): array
    {
        // 開発依存関係（devDependencies）
        $devDependencies = [
            'vite' => '^5.0.0',
            'vite-plugin-live-reload' => '^3.0.0',
        ];

        // 本番依存関係（dependencies）
        $dependencies = [];

        // フレームワーク別の依存関係
        if ($this->config['framework'] === 'react') {
            $dependencies['react'] = '^18.2.0';
            $dependencies['react-dom'] = '^18.2.0';
            $devDependencies['@vitejs/plugin-react'] = '^4.0.0';

            // TypeScript使用時は型定義を追加
            if ($this->config['typescript']) {
                $devDependencies['@types/react'] = '^18.2.0';
                $devDependencies['@types/react-dom'] = '^18.2.0';
            }
        } elseif ($this->config['framework'] === 'vue') {
            $dependencies['vue'] = '^3.4.0';
            $devDependencies['@vitejs/plugin-vue'] = '^5.0.0';

            // TypeScript使用時はvue-tscを追加
            if ($this->config['typescript']) {
                $devDependencies['vue-tsc'] = '^2.0.0';
            }
        }

        // TypeScript（フレームワーク共通）
        if ($this->config['typescript']) {
            $devDependencies['typescript'] = '^5.0.0';
        }

        // Tailwind CSS関連
        if ($this->config['tailwind']) {
            $devDependencies['tailwindcss'] = '^3.4.0';
            $devDependencies['postcss'] = '^8.4.0';
            $devDependencies['autoprefixer'] = '^10.4.0';
        }

        return [
            // ESモジュールとして扱う
            'type' => 'module',
            // npm scripts
            'scripts' => [
                'dev' => 'vite', // 開発サーバー起動
                'build' => 'vite build', // 本番ビルド
                'preview' => 'vite preview', // ビルド結果プレビュー
            ],
            'dependencies' => $dependencies,
            'devDependencies' => $devDependencies,
        ];
    }
}
