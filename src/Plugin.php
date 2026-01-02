<?php
declare(strict_types=1);

namespace ViteBake;

use Cake\Console\CommandCollection;
use Cake\Core\BasePlugin;
use Cake\Core\PluginApplicationInterface;
use ViteBake\Command\ViteBakeCommand;

/**
 * ViteBake プラグイン
 *
 * CakePHPプラグインのメインクラスです。
 * CakePHPでは、プラグインはBasePluginクラスを継承して作成します。
 *
 * このプラグインは `bin/cake bake vite` コマンドを提供し、
 * Vite + React/Vue/TypeScript/Tailwind CSS環境を自動セットアップします。
 *
 * ## プラグインの仕組み
 *
 * CakePHPプラグインは以下のライフサイクルメソッドを持ちます：
 * - bootstrap(): アプリケーション起動時に呼ばれる初期化処理
 * - console(): CLIコマンドを登録する
 * - routes(): ルーティングを定義する
 * - middleware(): ミドルウェアを登録する
 * - services(): DIコンテナにサービスを登録する
 *
 * ## 使い方
 *
 * 1. composer require で本プラグインをインストール
 * 2. src/Application.php で `$this->addPlugin('ViteBake')` を呼ぶ
 * 3. `bin/cake bake vite` でセットアップウィザードを実行
 *
 * @see https://book.cakephp.org/5/ja/plugins.html CakePHP プラグインドキュメント
 */
class Plugin extends BasePlugin
{
    /**
     * プラグイン名
     *
     * CakePHPがプラグインを識別するために使用する名前です。
     * nullの場合はクラス名から自動推定されます。
     *
     * @var string|null
     */
    protected ?string $name = 'ViteBake';

    /**
     * bootstrap処理を有効にするか
     *
     * trueの場合、bootstrap()メソッドが呼ばれます。
     * アプリケーション起動時の初期化処理（設定の読み込み等）に使用します。
     *
     * @var bool
     */
    protected bool $bootstrapEnabled = true;

    /**
     * コンソールコマンドを有効にするか
     *
     * trueの場合、console()メソッドが呼ばれます。
     * CLIコマンド（bin/cake xxx）を登録するために必要です。
     * このプラグインはBakeコマンドを提供するため、trueに設定しています。
     *
     * @var bool
     */
    protected bool $consoleEnabled = true;

    /**
     * ルーティングを有効にするか
     *
     * trueの場合、routes()メソッドが呼ばれ、
     * プラグイン独自のURLルーティングを定義できます。
     * このプラグインはCLIコマンドのみなので不要です。
     *
     * @var bool
     */
    protected bool $routesEnabled = false;

    /**
     * ミドルウェアを有効にするか
     *
     * trueの場合、middleware()メソッドが呼ばれ、
     * HTTPリクエスト/レスポンスを処理するミドルウェアを登録できます。
     * このプラグインはCLIコマンドのみなので不要です。
     *
     * @var bool
     */
    protected bool $middlewareEnabled = false;

    /**
     * サービス（DI）を有効にするか
     *
     * trueの場合、services()メソッドが呼ばれ、
     * 依存性注入コンテナにサービスを登録できます。
     * このプラグインでは使用しません。
     *
     * @var bool
     */
    protected bool $servicesEnabled = false;

    /**
     * プラグインの初期化処理
     *
     * アプリケーション起動時に呼ばれます。
     * 設定ファイルの読み込みや、イベントリスナーの登録などに使用します。
     *
     * 現在は特別な初期化処理は不要なので、親クラスの処理のみ実行しています。
     *
     * @param \Cake\Core\PluginApplicationInterface $app アプリケーションインスタンス
     * @return void
     */
    public function bootstrap(PluginApplicationInterface $app): void
    {
        parent::bootstrap($app);
    }

    /**
     * CLIコマンドの登録
     *
     * `bin/cake` で実行できるコマンドを登録します。
     *
     * ここでは `bake vite` コマンドを登録しています。
     * 登録後は `bin/cake bake vite` で実行できるようになります。
     *
     * CommandCollectionは連想配列のように動作し、
     * キー（コマンド名）と値（コマンドクラス）のペアを管理します。
     *
     * @param \Cake\Console\CommandCollection $commands コマンドコレクション
     * @return \Cake\Console\CommandCollection 更新されたコマンドコレクション
     */
    public function console(CommandCollection $commands): CommandCollection
    {
        // 親クラスの処理を実行（デフォルトのコマンド登録など）
        $commands = parent::console($commands);

        // 'bake vite' コマンドを登録
        // ViteBakeCommandクラスがこのコマンドの実際の処理を担当します
        $commands->add('bake vite', ViteBakeCommand::class);

        return $commands;
    }
}
