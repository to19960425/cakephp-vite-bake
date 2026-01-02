<?php
declare(strict_types=1);

namespace ViteBake\Test\TestApp;

use Cake\Core\ContainerInterface;
use Cake\Http\BaseApplication;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\RouteBuilder;

/**
 * Test Application
 *
 * A minimal CakePHP application for testing console commands.
 */
class Application extends BaseApplication
{
    /**
     * @inheritDoc
     */
    public function bootstrap(): void
    {
        // Load parent bootstrap
    }

    /**
     * @inheritDoc
     */
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        return $middlewareQueue;
    }

    /**
     * @inheritDoc
     */
    public function routes(RouteBuilder $routes): void
    {
        // No routes needed for testing
    }

    /**
     * @inheritDoc
     */
    public function services(ContainerInterface $container): void
    {
        // No services needed for testing
    }
}
