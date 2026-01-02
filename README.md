# CakePHP Vite Bake Plugin

[![CI](https://github.com/oshima-mgreen/cakephp-vite-bake/actions/workflows/ci.yml/badge.svg)](https://github.com/oshima-mgreen/cakephp-vite-bake/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![CakePHP 5.x](https://img.shields.io/badge/CakePHP-5.x-red.svg)](https://cakephp.org)
[![PHP 8.1+](https://img.shields.io/badge/PHP-8.1%2B-purple.svg)](https://php.net)

CakePHPプロジェクトにVite + モダンフロントエンド環境を一発でセットアップするBakeコマンド拡張プラグイン。

## 必要要件

- PHP 8.1+
- CakePHP 5.0+
- Node.js 18+
- Composer

## インストール

### 1. Composerでインストール

```bash
composer require green-oshima/cakephp-vite-bake --dev
```

### 2. プラグインを読み込む

`src/Application.php` の `bootstrap()` メソッドに追加：

```php
public function bootstrap(): void
{
    parent::bootstrap();

    // ViteBakeプラグインを読み込む
    $this->addPlugin('ViteBake');
}
```

または、CLIで：

```bash
bin/cake plugin load ViteBake
```

## 使い方

### 基本的な使い方（対話モード）

```bash
bin/cake bake vite
```

対話的なウィザードが起動し、以下を選択できます:
- フロントエンドフレームワーク（React / Vue / Vanilla）
- TypeScriptの使用有無
- Tailwind CSSの使用有無

### クイックセットアップ（プリセット）

```bash
# React + TypeScript + Tailwind（おすすめ構成）
bin/cake bake vite --preset react-ts-tailwind

# Vue + TypeScript + Tailwind
bin/cake bake vite --preset vue-ts-tailwind

# React + TypeScript（Tailwindなし）
bin/cake bake vite --preset react-ts

# シンプルなVanilla JS
bin/cake bake vite --preset vanilla
```

### オプション指定

```bash
# 個別にオプション指定
bin/cake bake vite --framework vue --typescript --tailwind

# 既存ファイルを上書き
bin/cake bake vite --preset react-ts-tailwind --force
```

### 利用可能なオプション

| オプション | 短縮形 | 説明 | 値 |
|-----------|--------|------|-----|
| `--framework` | `-f` | フレームワーク | react, vue, vanilla |
| `--typescript` | `-t` | TypeScriptを使用 | (フラグ) |
| `--tailwind` | `-w` | Tailwind CSSを使用 | (フラグ) |
| `--preset` | `-p` | プリセット構成 | react-ts-tailwind, react-ts, vue-ts-tailwind, vue-ts, vanilla |
| `--force` | | 既存ファイルを上書き | (フラグ) |

## 生成されるファイル

```
your-cakephp-project/
├── vite.config.ts          # Vite設定
├── tsconfig.json           # TypeScript設定（--typescript時）
├── tailwind.config.js      # Tailwind設定（--tailwind時）
├── postcss.config.js       # PostCSS設定（--tailwind時）
├── package.json            # npm依存関係（更新/作成）
├── config/
│   └── app_vite.php        # CakePHP Vite Helper設定
├── resources/
│   ├── js/
│   │   ├── main.tsx        # エントリーポイント
│   │   └── App.tsx         # サンプルコンポーネント
│   └── css/
│       └── app.css         # スタイルシート
└── templates/
    └── layout/
        └── vite.php        # Vite用レイアウト（サンプル）
```

## セットアップ後の手順

```bash
# 1. 依存関係のインストール
npm install

# 2. 開発サーバー起動（2つのターミナルで）
npm run dev          # Vite開発サーバー (port 3000)
bin/cake server      # CakePHP開発サーバー (port 8765)

# 3. ブラウザで確認
open http://localhost:8765

# 4. 本番ビルド
npm run build
```

## 依存プラグイン

このプラグインは [passchn/cakephp-vite](https://github.com/brandcom/cakephp-vite) のViteHelperを利用します。
ViteHelperは別途インストールが必要です：

```bash
composer require passchn/cakephp-vite
```
### 開発環境セットアップ

```bash
git clone https://github.com/oshima-mgreen/cakephp-vite-bake.git
cd cakephp-vite-bake
composer install
composer test      # テスト実行
composer cs-check  # コードスタイルチェック
```