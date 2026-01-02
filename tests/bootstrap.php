<?php
declare(strict_types=1);

/**
 * ViteBake Plugin Test Bootstrap
 *
 * This file sets up the test environment for the ViteBake plugin.
 */

use Cake\Core\Configure;

// Path constants (only define if not already defined by CakePHP)
if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__));
}
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
if (!defined('TMP')) {
    define('TMP', ROOT . DS . 'tmp' . DS);
}
if (!defined('LOGS')) {
    define('LOGS', TMP . 'logs' . DS);
}
if (!defined('CACHE')) {
    define('CACHE', TMP . 'cache' . DS);
}
if (!defined('CONFIG')) {
    define('CONFIG', ROOT . DS . 'config' . DS);
}
if (!defined('APP')) {
    define('APP', ROOT . DS . 'tests' . DS . 'test_app' . DS);
}
if (!defined('WWW_ROOT')) {
    define('WWW_ROOT', ROOT . DS . 'tests' . DS . 'test_app' . DS . 'webroot' . DS);
}

// Ensure tmp directories exist
foreach ([TMP, LOGS, CACHE, CACHE . 'views', CACHE . 'models'] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Create test_app directories
$testAppDirs = [
    APP,
    APP . 'src',
    WWW_ROOT,
];
foreach ($testAppDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

require dirname(__DIR__) . '/vendor/autoload.php';

// Configure CakePHP
Configure::write('debug', true);
Configure::write('App', [
    'namespace' => 'ViteBake\Test\TestApp',
    'encoding' => 'UTF-8',
    'base' => false,
    'dir' => 'src',
    'webroot' => 'webroot',
    'wwwRoot' => WWW_ROOT,
    'fullBaseUrl' => 'http://localhost',
    'imageBaseUrl' => 'img/',
    'cssBaseUrl' => 'css/',
    'jsBaseUrl' => 'js/',
    'paths' => [
        'plugins' => [ROOT . DS . 'plugins' . DS],
        'templates' => [APP . 'templates' . DS],
    ],
]);

// Configure logging
Configure::write('Log', [
    'debug' => [
        'className' => 'Cake\Log\Engine\FileLog',
        'path' => LOGS,
        'levels' => ['notice', 'info', 'debug'],
        'file' => 'debug',
    ],
    'error' => [
        'className' => 'Cake\Log\Engine\FileLog',
        'path' => LOGS,
        'levels' => ['warning', 'error', 'critical', 'alert', 'emergency'],
        'file' => 'error',
    ],
]);

// Configure cache
Configure::write('Cache', [
    'default' => [
        'className' => 'Cake\Cache\Engine\ArrayEngine',
    ],
    '_cake_core_' => [
        'className' => 'Cake\Cache\Engine\ArrayEngine',
    ],
    '_cake_model_' => [
        'className' => 'Cake\Cache\Engine\ArrayEngine',
    ],
]);
