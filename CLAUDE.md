# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## プロジェクト概要

CakePHP 5.x用のBakeコマンド拡張プラグイン。`bin/cake bake vite`コマンドでVite + モダンフロントエンド環境（React/Vue/TypeScript/Tailwind）を一発でセットアップする。

## 開発コマンド

```bash
# 依存関係インストール
composer install

# テスト実行
composer test                    # 全テスト実行
vendor/bin/phpunit              # PHPUnit直接実行
vendor/bin/phpunit --filter testMethodName  # 特定のテスト実行
vendor/bin/phpunit tests/TestCase/Command/  # 特定ディレクトリのテスト

# コードスタイル
composer cs-check               # CakePHP規約チェック
composer cs-fix                 # 自動修正

# 静的解析（CI環境）
vendor/bin/phpstan analyse src/ --level=5
```

## アーキテクチャ

```
src/
├── Plugin.php                    # CakePHPプラグインエントリーポイント
│                                 # console()でViteBakeCommandを登録
├── Command/
│   └── ViteBakeCommand.php       # Bakeコマンド本体
│                                 # - buildOptionParser(): CLI引数定義
│                                 # - execute(): メイン処理フロー
│                                 # - getConfiguration(): プリセット/対話モードから設定取得
│                                 # - interactiveSetup(): 対話式ウィザード
└── Template/
    └── TemplateGenerator.php     # ファイル生成エンジン
                                  # - generate(): 全ファイル生成の統括
                                  # - getFilesToGenerate(): 設定に基づくファイルリスト
                                  # - get*Config(): 各設定ファイルのコンテンツ生成
                                  # - updatePackageJson(): 既存package.jsonとのマージ
```

### 処理フロー

1. `ViteBakeCommand::execute()` がエントリーポイント
2. プリセット/オプション/対話モードから設定を取得
3. `TemplateGenerator`に設定を渡してファイル生成
4. 生成するファイルは設定（framework/typescript/tailwind）に応じて動的に決定

### 設定オブジェクト構造

```php
$config = [
    'framework' => 'react' | 'vue' | 'vanilla',
    'typescript' => bool,
    'tailwind' => bool
];
```

## テスト構成

```
tests/
├── bootstrap.php                 # テスト環境初期化
├── TestCase/
│   ├── Command/
│   │   └── ViteBakeCommandTest.php  # コマンドの統合テスト
│   └── Template/
│       └── TemplateGeneratorTest.php # ファイル生成のユニットテスト
└── test_app/                     # テスト用CakePHPアプリ骨格
```

## 対応プリセット

- `react-ts-tailwind`: React + TypeScript + Tailwind
- `react-ts`: React + TypeScript
- `vue-ts-tailwind`: Vue + TypeScript + Tailwind
- `vue-ts`: Vue + TypeScript
- `vanilla`: Vanilla JS

## 依存関係

- CakePHP 5.0+、cakephp/bake 3.0+
- PHP 8.1+
- 生成されるプロジェクトは[passchn/cakephp-vite](https://github.com/brandcom/cakephp-vite)のViteHelperを使用
