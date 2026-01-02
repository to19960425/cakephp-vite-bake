<?php
declare(strict_types=1);

namespace ViteBake\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use ViteBake\Template\TemplateGenerator;

/**
 * Vite セットアップコマンド
 *
 * CakePHPプロジェクトにVite + モダンフロントエンド環境をセットアップするコマンドです。
 *
 * ## CakePHPコマンドの仕組み
 *
 * CakePHPのCLIコマンドは `Cake\Command\Command` クラスを継承して作成します。
 * 主要なメソッド:
 *
 * - `defaultName()`: コマンド名を定義（例: 'bake vite' → `bin/cake bake vite`）
 * - `buildOptionParser()`: コマンドライン引数とオプションを定義
 * - `execute()`: コマンドの実際の処理を実行
 *
 * ## 使用例
 *
 * ```bash
 * # 対話モード（ウィザード形式）
 * bin/cake bake vite
 *
 * # プリセット使用
 * bin/cake bake vite -p react-ts-tailwind
 *
 * # オプション直接指定
 * bin/cake bake vite -f react -t -w
 * ```
 *
 * ## 終了コード
 *
 * - CODE_SUCCESS (0): 正常終了
 * - CODE_ERROR (1): エラー終了
 *
 * @see https://book.cakephp.org/5/ja/console-commands/commands.html CakePHPコマンドドキュメント
 */
class ViteBakeCommand extends Command
{
    /**
     * 利用可能なフロントエンドフレームワーク
     *
     * CLI引数 `-f` または `--framework` で指定できる値です。
     * ConsoleOptionParserの `choices` に渡され、
     * この配列以外の値が指定されるとエラーになります。
     */
    protected const FRAMEWORKS = ['react', 'vue', 'vanilla'];

    /**
     * プリセット設定
     *
     * よく使う組み合わせを事前定義したものです。
     * `-p` または `--preset` オプションで使用できます。
     *
     * 各プリセットは以下のキーを持つ配列です:
     * - framework: 使用するフレームワーク（react/vue/vanilla）
     * - typescript: TypeScriptを使用するか
     * - tailwind: Tailwind CSSを使用するか
     */
    protected const PRESETS = [
        // React + TypeScript + Tailwind CSS（フル構成）
        'react-ts-tailwind' => [
            'framework' => 'react',
            'typescript' => true,
            'tailwind' => true,
        ],
        // React + TypeScript（Tailwindなし）
        'react-ts' => [
            'framework' => 'react',
            'typescript' => true,
            'tailwind' => false,
        ],
        // Vue + TypeScript + Tailwind CSS
        'vue-ts-tailwind' => [
            'framework' => 'vue',
            'typescript' => true,
            'tailwind' => true,
        ],
        // Vue + TypeScript
        'vue-ts' => [
            'framework' => 'vue',
            'typescript' => true,
            'tailwind' => false,
        ],
        // Vanilla JS（フレームワークなし、最小構成）
        'vanilla' => [
            'framework' => 'vanilla',
            'typescript' => false,
            'tailwind' => false,
        ],
    ];

    /**
     * デフォルトのコマンド名を返す
     *
     * この名前がCLIでコマンドを呼び出す際に使用されます。
     * 'bake vite' を返すことで、`bin/cake bake vite` として実行できます。
     *
     * スペースで区切ると、コマンドのサブコマンド（入れ子）として認識されます。
     * 例: 'bake vite' → bake の下の vite サブコマンド
     *
     * @return string コマンド名
     */
    public static function defaultName(): string
    {
        return 'bake vite';
    }

    /**
     * コマンドライン引数とオプションを定義
     *
     * ConsoleOptionParserを使って、コマンドが受け取る引数とオプションを設定します。
     * ここで定義した内容は `bin/cake bake vite --help` で表示されます。
     *
     * ## オプションの種類
     *
     * - 通常のオプション: 値を受け取る（例: `-f react`）
     * - ブールオプション: フラグとして機能（例: `-t` で true）
     * - 選択肢付きオプション: 指定された値のみ受け付ける
     *
     * @param \Cake\Console\ConsoleOptionParser $parser オプションパーサー
     * @return \Cake\Console\ConsoleOptionParser 設定済みのパーサー
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser
            // コマンドの説明文（--help で表示される）
            ->setDescription([
                'Set up Vite with modern frontend frameworks in your CakePHP project.',
                '',
                'This command creates all necessary configuration files for:',
                '- Vite build tool',
                '- React or Vue.js (optional)',
                '- TypeScript (optional)',
                '- Tailwind CSS (optional)',
            ])
            // -f, --framework オプション: フレームワーク選択
            ->addOption('framework', [
                'short' => 'f', // 短縮形（-f react のように使用）
                'help' => 'Frontend framework to use (react, vue, vanilla)',
                'choices' => self::FRAMEWORKS, // この配列の値のみ受け付ける
            ])
            // -t, --typescript オプション: TypeScript有効化フラグ
            ->addOption('typescript', [
                'short' => 't',
                'help' => 'Use TypeScript',
                'boolean' => true, // ブールオプション（値なしで true）
            ])
            // -w, --tailwind オプション: Tailwind CSS有効化フラグ
            ->addOption('tailwind', [
                'short' => 'w',
                'help' => 'Use Tailwind CSS',
                'boolean' => true,
            ])
            // -p, --preset オプション: プリセット選択
            ->addOption('preset', [
                'short' => 'p',
                'help' => 'Use a preset configuration (react-ts-tailwind, react-ts, vue-ts-tailwind, vue-ts, vanilla)',
                'choices' => array_keys(self::PRESETS), // PRESETS配列のキーのみ受け付ける
            ])
            // --force オプション: 既存ファイルの上書き許可
            ->addOption('force', [
                'help' => 'Overwrite existing files',
                'boolean' => true,
                'default' => false, // デフォルトは上書きしない
            ]);

        return $parser;
    }

    /**
     * コマンドの実行
     *
     * `bin/cake bake vite` を実行した時に呼ばれるメインの処理です。
     *
     * 処理の流れ:
     * 1. 設定を取得（プリセット、オプション指定、または対話モード）
     * 2. 設定内容を表示
     * 3. ユーザーに確認
     * 4. TemplateGeneratorでファイル生成
     * 5. 次のステップを表示
     *
     * @param \Cake\Console\Arguments $args パースされたコマンドライン引数
     * @param \Cake\Console\ConsoleIo $io コンソール入出力ヘルパー
     * @return int 終了コード（CODE_SUCCESS または CODE_ERROR）
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        // ウィザードヘッダーを表示
        $io->out('');
        $io->out('<info>🍰 CakePHP Vite Setup Wizard</info>');
        $io->out('');

        // 設定を取得（プリセット、CLIオプション、または対話モードから）
        $config = $this->getConfiguration($args, $io);
        if ($config === null) {
            return self::CODE_ERROR;
        }

        // --force オプションの値を取得
        $force = (bool)$args->getOption('force');

        // 設定サマリーを表示
        $this->showConfigSummary($config, $io);

        // ユーザーに確認（--force 指定時はスキップ）
        if (!$force && $io->askChoice('Proceed with setup?', ['y', 'n'], 'y') !== 'y') {
            $io->out('<warning>Setup cancelled.</warning>');

            return self::CODE_SUCCESS; // キャンセルも正常終了として扱う
        }

        // TemplateGeneratorを使ってファイルを生成
        $generator = new TemplateGenerator($config, $force);
        $result = $generator->generate($io);

        if (!$result) {
            return self::CODE_ERROR;
        }

        // 次のステップを表示
        $this->showNextSteps($config, $io);

        return self::CODE_SUCCESS;
    }

    /**
     * 設定を取得する
     *
     * 以下の優先順位で設定を決定します:
     * 1. プリセットが指定されていればそれを使用
     * 2. フレームワークがCLI指定されていれば非対話モード
     * 3. それ以外は対話モード（ウィザード）
     *
     * ## CakePHPのブールオプションについて
     *
     * CakePHPのブールオプションは、CLIで指定されていない場合でも
     * デフォルト値として `false` を返します（`null` ではない）。
     *
     * そのため、フレームワークの指定有無で対話モードかどうかを判断しています。
     *
     * @param \Cake\Console\Arguments $args コマンドライン引数
     * @param \Cake\Console\ConsoleIo $io コンソール入出力
     * @return array<string, mixed>|null 設定配列、またはエラー時はnull
     */
    protected function getConfiguration(Arguments $args, ConsoleIo $io): ?array
    {
        // プリセットが指定されているか確認
        $preset = $args->getOption('preset');
        if ($preset !== null && isset(self::PRESETS[$preset])) {
            return self::PRESETS[$preset];
        }

        // フレームワークがCLI指定されているか確認
        $framework = $args->getOption('framework');

        // フレームワークが指定されている場合は非対話モード
        // ブールオプションはCLI未指定でもfalseを返すため、
        // フレームワーク指定を非対話モードの判断基準にしている
        if ($framework !== null) {
            return [
                'framework' => $framework,
                'typescript' => (bool)$args->getOption('typescript'),
                'tailwind' => (bool)$args->getOption('tailwind'),
            ];
        }

        // 対話モード - すべてのオプションをnullで渡してユーザーに質問する
        return $this->interactiveSetup($io, null, null, null);
    }

    /**
     * 対話モードのセットアップウィザード
     *
     * ユーザーに質問を表示し、選択に基づいて設定を構築します。
     *
     * ## ConsoleIoの対話メソッド
     *
     * - `out()`: テキストを出力
     * - `askChoice()`: 選択肢を表示して入力を受け取る
     *
     * `askChoice($question, $choices, $default)` の引数:
     * - $question: 質問文
     * - $choices: 選択肢の配列
     * - $default: デフォルト値（Enterのみで選択される）
     *
     * @param \Cake\Console\ConsoleIo $io コンソール入出力
     * @param string|null $framework 事前選択されたフレームワーク（nullなら質問する）
     * @param bool|null $typescript 事前選択されたTypeScript設定（nullなら質問する）
     * @param bool|null $tailwind 事前選択されたTailwind設定（nullなら質問する）
     * @return array<string, mixed> 設定配列
     */
    protected function interactiveSetup(
        ConsoleIo $io,
        ?string $framework,
        ?bool $typescript,
        ?bool $tailwind,
    ): array {
        // フレームワーク選択（未指定の場合）
        if ($framework === null) {
            $io->out('<info>Select frontend framework:</info>');
            $io->out('  [1] React (recommended)');
            $io->out('  [2] Vue.js');
            $io->out('  [3] Vanilla JS');
            $io->out('');

            // 選択肢から入力を受け取る（デフォルト: 1 = React）
            $choice = $io->askChoice('Your choice', ['1', '2', '3'], '1');

            // PHP 8.0+ の match 式で選択肢を変換
            $framework = match ($choice) {
                '2' => 'vue',
                '3' => 'vanilla',
                default => 'react',
            };
        }

        // TypeScript選択（未指定の場合、Vanilla以外のみ）
        if ($typescript === null) {
            if ($framework !== 'vanilla') {
                // y/n で質問し、'y' なら true
                $typescript = $io->askChoice('Use TypeScript?', ['y', 'n'], 'y') === 'y';
            } else {
                // VanillaモードではTypeScriptを使用しない
                $typescript = false;
            }
        }

        // Tailwind CSS選択（未指定の場合）
        if ($tailwind === null) {
            $tailwind = $io->askChoice('Use Tailwind CSS?', ['y', 'n'], 'y') === 'y';
        }

        return [
            'framework' => $framework,
            'typescript' => $typescript,
            'tailwind' => $tailwind,
        ];
    }

    /**
     * 設定サマリーを表示
     *
     * ユーザーが選択した設定内容を見やすく表示します。
     *
     * ## ConsoleIoの出力タグ
     *
     * CakePHPのConsoleIoはHTMLライクなタグで出力を装飾できます:
     * - `<info>`: 情報（通常は青色）
     * - `<success>`: 成功（通常は緑色）
     * - `<warning>`: 警告（通常は黄色）
     * - `<error>`: エラー（通常は赤色）
     * - `<comment>`: コメント（通常はシアン色）
     *
     * @param array<string, mixed> $config 設定配列
     * @param \Cake\Console\ConsoleIo $io コンソール入出力
     * @return void
     */
    protected function showConfigSummary(array $config, ConsoleIo $io): void
    {
        $io->out('');
        $io->out('<info>Configuration Summary:</info>');
        $io->out('');

        // フレームワーク名を表示用に変換
        $frameworkDisplay = match ($config['framework']) {
            'react' => 'React',
            'vue' => 'Vue.js',
            'vanilla' => 'Vanilla JS',
            default => $config['framework'],
        };

        // 各設定項目を表示（Yes/Noで色分け）
        $io->out("  Framework:   <success>{$frameworkDisplay}</success>");
        $io->out('  TypeScript:  ' . ($config['typescript'] ? '<success>Yes</success>' : '<warning>No</warning>'));
        $io->out('  Tailwind:    ' . ($config['tailwind'] ? '<success>Yes</success>' : '<warning>No</warning>'));
        $io->out('');
    }

    /**
     * 次のステップを表示
     *
     * セットアップ完了後、ユーザーが次に実行すべきコマンドを案内します。
     * この案内がないと、ユーザーは何をすればいいかわからなくなるため重要です。
     *
     * @param array<string, mixed> $config 設定配列（将来の拡張用）
     * @param \Cake\Console\ConsoleIo $io コンソール入出力
     * @return void
     */
    protected function showNextSteps(array $config, ConsoleIo $io): void
    {
        $io->out('');
        $io->out('<info>✅ Setup complete!</info>');
        $io->out('');
        $io->out('<info>Next steps:</info>');
        $io->out('');
        $io->out('  1. Install dependencies:');
        $io->out('     <comment>npm install</comment>');
        $io->out('');
        $io->out('  2. Start development servers (in separate terminals):');
        $io->out('     <comment>npm run dev</comment>');
        $io->out('     <comment>bin/cake server</comment>');
        $io->out('');
        $io->out('  3. Open your browser:');
        $io->out('     <comment>http://localhost:8765</comment>');
        $io->out('');
        $io->out('  4. For production build:');
        $io->out('     <comment>npm run build</comment>');
        $io->out('');
        $io->out('<info>Happy coding! 🚀</info>');
        $io->out('');
    }
}
