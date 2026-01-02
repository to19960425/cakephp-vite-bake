<?php
declare(strict_types=1);

namespace ViteBake\Test\TestCase\Template;

use Cake\TestSuite\TestCase;
use ReflectionClass;
use ViteBake\Template\TemplateGenerator;

/**
 * TemplateGenerator Test Case
 *
 * Tests the template generation for Vite configuration files.
 *
 * @covers \ViteBake\Template\TemplateGenerator
 */
class TemplateGeneratorTest extends TestCase
{
    /**
     * Test output directory for generated files
     */
    protected string $testOutputDir;

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create isolated test output directory
        $this->testOutputDir = TMP . 'test_output' . DS . uniqid('template_', true) . DS;
        if (!is_dir($this->testOutputDir)) {
            mkdir($this->testOutputDir, 0777, true);
        }

        // Create subdirectories that a CakePHP project would have
        mkdir($this->testOutputDir . 'config', 0777, true);
        mkdir($this->testOutputDir . 'resources' . DS . 'js', 0777, true);
        mkdir($this->testOutputDir . 'resources' . DS . 'css', 0777, true);
        mkdir($this->testOutputDir . 'templates' . DS . 'layout', 0777, true);
        mkdir($this->testOutputDir . 'webroot', 0777, true);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up test output directory
        $this->removeDirectory($this->testOutputDir);
    }

    /**
     * Recursively remove a directory
     *
     * @param string $dir Directory path
     * @return void
     */
    protected function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DS . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    /**
     * Create a TemplateGenerator with test root path
     *
     * @param array<string, mixed> $config Configuration
     * @param bool $force Force overwrite
     * @return \ViteBake\Template\TemplateGenerator
     */
    protected function createGenerator(array $config, bool $force = false): TemplateGenerator
    {
        $generator = new TemplateGenerator($config, $force);

        // Use reflection to set test root path
        $reflection = new ReflectionClass($generator);
        $property = $reflection->getProperty('rootPath');
        $property->setAccessible(true);
        $property->setValue($generator, $this->testOutputDir);

        return $generator;
    }

    // =========================================================================
    // Configuration Tests
    // =========================================================================

    /**
     * Test constructor stores configuration
     *
     * @return void
     */
    public function testConstructorStoresConfig(): void
    {
        $config = [
            'framework' => 'react',
            'typescript' => true,
            'tailwind' => true,
        ];

        $generator = new TemplateGenerator($config);

        $reflection = new ReflectionClass($generator);
        $property = $reflection->getProperty('config');
        $property->setAccessible(true);

        $this->assertSame($config, $property->getValue($generator));
    }

    /**
     * Test constructor stores force flag
     *
     * @return void
     */
    public function testConstructorStoresForceFlag(): void
    {
        $generator = new TemplateGenerator(['framework' => 'react'], true);

        $reflection = new ReflectionClass($generator);
        $property = $reflection->getProperty('force');
        $property->setAccessible(true);

        $this->assertTrue($property->getValue($generator));
    }

    /**
     * Test default force flag is false
     *
     * @return void
     */
    public function testDefaultForceFlagIsFalse(): void
    {
        $generator = new TemplateGenerator(['framework' => 'react']);

        $reflection = new ReflectionClass($generator);
        $property = $reflection->getProperty('force');
        $property->setAccessible(true);

        $this->assertFalse($property->getValue($generator));
    }

    // =========================================================================
    // Vite Config Generation Tests
    // =========================================================================

    /**
     * Test Vite config contains basic structure for React
     *
     * @return void
     */
    public function testViteConfigReactStructure(): void
    {
        $generator = $this->createGenerator([
            'framework' => 'react',
            'typescript' => true,
            'tailwind' => false,
        ]);

        $reflection = new ReflectionClass($generator);
        $method = $reflection->getMethod('getViteConfig');
        $method->setAccessible(true);

        $config = $method->invoke($generator);

        $this->assertStringContainsString("import { defineConfig } from 'vite'", $config);
        $this->assertStringContainsString("import react from '@vitejs/plugin-react'", $config);
        $this->assertStringContainsString('react()', $config);
        $this->assertStringContainsString("outDir: 'webroot/dist'", $config);
        $this->assertStringContainsString('manifest: true', $config);
        $this->assertStringContainsString('main.tsx', $config);
    }

    /**
     * Test Vite config contains Vue plugin for Vue framework
     *
     * @return void
     */
    public function testViteConfigVueStructure(): void
    {
        $generator = $this->createGenerator([
            'framework' => 'vue',
            'typescript' => true,
            'tailwind' => false,
        ]);

        $reflection = new ReflectionClass($generator);
        $method = $reflection->getMethod('getViteConfig');
        $method->setAccessible(true);

        $config = $method->invoke($generator);

        $this->assertStringContainsString("import vue from '@vitejs/plugin-vue'", $config);
        $this->assertStringContainsString('vue()', $config);
        $this->assertStringContainsString('main.tsx', $config);
    }

    /**
     * Test Vite config uses .js extension for vanilla without TypeScript
     *
     * @return void
     */
    public function testViteConfigVanillaJsExtension(): void
    {
        $generator = $this->createGenerator([
            'framework' => 'vanilla',
            'typescript' => false,
            'tailwind' => false,
        ]);

        $reflection = new ReflectionClass($generator);
        $method = $reflection->getMethod('getViteConfig');
        $method->setAccessible(true);

        $config = $method->invoke($generator);

        $this->assertStringContainsString('main.js', $config);
        $this->assertStringNotContainsString('main.ts', $config);
        $this->assertStringNotContainsString('main.tsx', $config);
    }

    /**
     * Test Vite config includes live reload plugin
     *
     * @return void
     */
    public function testViteConfigIncludesLiveReload(): void
    {
        $generator = $this->createGenerator([
            'framework' => 'react',
            'typescript' => true,
            'tailwind' => false,
        ]);

        $reflection = new ReflectionClass($generator);
        $method = $reflection->getMethod('getViteConfig');
        $method->setAccessible(true);

        $config = $method->invoke($generator);

        $this->assertStringContainsString("import liveReload from 'vite-plugin-live-reload'", $config);
        $this->assertStringContainsString("liveReload(['templates/**/*.php'])", $config);
    }

    // =========================================================================
    // TypeScript Config Tests
    // =========================================================================

    /**
     * Test TypeScript config for React uses react-jsx
     *
     * @return void
     */
    public function testTsConfigReactJsx(): void
    {
        $generator = $this->createGenerator([
            'framework' => 'react',
            'typescript' => true,
            'tailwind' => false,
        ]);

        $reflection = new ReflectionClass($generator);
        $method = $reflection->getMethod('getTsConfig');
        $method->setAccessible(true);

        $config = $method->invoke($generator);

        $this->assertStringContainsString('"jsx": "react-jsx"', $config);
    }

    /**
     * Test TypeScript config for Vue uses preserve
     *
     * @return void
     */
    public function testTsConfigVuePreserve(): void
    {
        $generator = $this->createGenerator([
            'framework' => 'vue',
            'typescript' => true,
            'tailwind' => false,
        ]);

        $reflection = new ReflectionClass($generator);
        $method = $reflection->getMethod('getTsConfig');
        $method->setAccessible(true);

        $config = $method->invoke($generator);

        $this->assertStringContainsString('"jsx": "preserve"', $config);
    }

    /**
     * Test TypeScript config has strict mode enabled
     *
     * @return void
     */
    public function testTsConfigStrictMode(): void
    {
        $generator = $this->createGenerator([
            'framework' => 'react',
            'typescript' => true,
            'tailwind' => false,
        ]);

        $reflection = new ReflectionClass($generator);
        $method = $reflection->getMethod('getTsConfig');
        $method->setAccessible(true);

        $config = $method->invoke($generator);

        $this->assertStringContainsString('"strict": true', $config);
    }

    // =========================================================================
    // Tailwind Config Tests
    // =========================================================================

    /**
     * Test Tailwind config content paths
     *
     * @return void
     */
    public function testTailwindConfigContentPaths(): void
    {
        $generator = $this->createGenerator([
            'framework' => 'react',
            'typescript' => true,
            'tailwind' => true,
        ]);

        $reflection = new ReflectionClass($generator);
        $method = $reflection->getMethod('getTailwindConfig');
        $method->setAccessible(true);

        $config = $method->invoke($generator);

        $this->assertStringContainsString("'./resources/**/*.{js,ts,jsx,tsx,vue}'", $config);
        $this->assertStringContainsString("'./templates/**/*.php'", $config);
    }

    /**
     * Test PostCSS config includes Tailwind and Autoprefixer
     *
     * @return void
     */
    public function testPostCssConfig(): void
    {
        $generator = $this->createGenerator([
            'framework' => 'react',
            'typescript' => true,
            'tailwind' => true,
        ]);

        $reflection = new ReflectionClass($generator);
        $method = $reflection->getMethod('getPostCssConfig');
        $method->setAccessible(true);

        $config = $method->invoke($generator);

        $this->assertStringContainsString('tailwindcss: {}', $config);
        $this->assertStringContainsString('autoprefixer: {}', $config);
    }

    // =========================================================================
    // CSS Generation Tests
    // =========================================================================

    /**
     * Test app.css with Tailwind includes directives
     *
     * @return void
     */
    public function testAppCssWithTailwind(): void
    {
        $generator = $this->createGenerator([
            'framework' => 'react',
            'typescript' => true,
            'tailwind' => true,
        ]);

        $reflection = new ReflectionClass($generator);
        $method = $reflection->getMethod('getAppCss');
        $method->setAccessible(true);

        $css = $method->invoke($generator);

        $this->assertStringContainsString('@tailwind base;', $css);
        $this->assertStringContainsString('@tailwind components;', $css);
        $this->assertStringContainsString('@tailwind utilities;', $css);
    }

    /**
     * Test app.css without Tailwind has basic styles
     *
     * @return void
     */
    public function testAppCssWithoutTailwind(): void
    {
        $generator = $this->createGenerator([
            'framework' => 'react',
            'typescript' => true,
            'tailwind' => false,
        ]);

        $reflection = new ReflectionClass($generator);
        $method = $reflection->getMethod('getAppCss');
        $method->setAccessible(true);

        $css = $method->invoke($generator);

        $this->assertStringContainsString('box-sizing: border-box', $css);
        $this->assertStringContainsString('font-family:', $css);
        $this->assertStringNotContainsString('@tailwind', $css);
    }

    // =========================================================================
    // Main Entry File Tests
    // =========================================================================

    /**
     * Test React main entry includes React imports
     *
     * @return void
     */
    public function testReactMainEntry(): void
    {
        $generator = $this->createGenerator([
            'framework' => 'react',
            'typescript' => true,
            'tailwind' => false,
        ]);

        $reflection = new ReflectionClass($generator);
        $method = $reflection->getMethod('getMainJs');
        $method->setAccessible(true);

        $main = $method->invoke($generator);

        $this->assertStringContainsString("import React from 'react'", $main);
        $this->assertStringContainsString("import { createRoot } from 'react-dom/client'", $main);
        $this->assertStringContainsString("import App from './App'", $main);
        $this->assertStringContainsString('<React.StrictMode>', $main);
    }

    /**
     * Test Vue main entry includes Vue imports
     *
     * @return void
     */
    public function testVueMainEntry(): void
    {
        $generator = $this->createGenerator([
            'framework' => 'vue',
            'typescript' => true,
            'tailwind' => false,
        ]);

        $reflection = new ReflectionClass($generator);
        $method = $reflection->getMethod('getMainJs');
        $method->setAccessible(true);

        $main = $method->invoke($generator);

        $this->assertStringContainsString("import { createApp } from 'vue'", $main);
        $this->assertStringContainsString("import App from './App.vue'", $main);
        $this->assertStringContainsString('app.mount(container)', $main);
    }

    /**
     * Test Vanilla main entry is simple
     *
     * @return void
     */
    public function testVanillaMainEntry(): void
    {
        $generator = $this->createGenerator([
            'framework' => 'vanilla',
            'typescript' => false,
            'tailwind' => false,
        ]);

        $reflection = new ReflectionClass($generator);
        $method = $reflection->getMethod('getMainJs');
        $method->setAccessible(true);

        $main = $method->invoke($generator);

        $this->assertStringContainsString("console.log('", $main);
        $this->assertStringContainsString("document.addEventListener('DOMContentLoaded'", $main);
        $this->assertStringNotContainsString('React', $main);
        $this->assertStringNotContainsString('Vue', $main);
    }

    // =========================================================================
    // App Component Tests
    // =========================================================================

    /**
     * Test React App component with Tailwind classes
     *
     * @return void
     */
    public function testReactAppComponentWithTailwind(): void
    {
        $generator = $this->createGenerator([
            'framework' => 'react',
            'typescript' => true,
            'tailwind' => true,
        ]);

        $reflection = new ReflectionClass($generator);
        $method = $reflection->getMethod('getAppComponent');
        $method->setAccessible(true);

        $component = $method->invoke($generator);

        $this->assertStringContainsString('import { useState }', $component);
        $this->assertStringContainsString('className="min-h-screen', $component);
        $this->assertStringContainsString('bg-gray-100', $component);
        $this->assertStringContainsString('export default App', $component);
    }

    /**
     * Test Vue App component with Tailwind classes
     *
     * @return void
     */
    public function testVueAppComponentWithTailwind(): void
    {
        $generator = $this->createGenerator([
            'framework' => 'vue',
            'typescript' => true,
            'tailwind' => true,
        ]);

        $reflection = new ReflectionClass($generator);
        $method = $reflection->getMethod('getAppComponent');
        $method->setAccessible(true);

        $component = $method->invoke($generator);

        $this->assertStringContainsString('<script setup', $component);
        $this->assertStringContainsString('<template>', $component);
        $this->assertStringContainsString('class="min-h-screen', $component);
        $this->assertStringContainsString("import { ref } from 'vue'", $component);
    }

    // =========================================================================
    // Package.json Config Tests
    // =========================================================================

    /**
     * Test package.json includes Vite dependencies
     *
     * @return void
     */
    public function testPackageJsonViteDependencies(): void
    {
        $generator = $this->createGenerator([
            'framework' => 'react',
            'typescript' => true,
            'tailwind' => false,
        ]);

        $reflection = new ReflectionClass($generator);
        $method = $reflection->getMethod('getPackageJsonConfig');
        $method->setAccessible(true);

        $config = $method->invoke($generator);

        $this->assertArrayHasKey('devDependencies', $config);
        $this->assertArrayHasKey('vite', $config['devDependencies']);
        $this->assertArrayHasKey('vite-plugin-live-reload', $config['devDependencies']);
    }

    /**
     * Test package.json includes React dependencies
     *
     * @return void
     */
    public function testPackageJsonReactDependencies(): void
    {
        $generator = $this->createGenerator([
            'framework' => 'react',
            'typescript' => true,
            'tailwind' => false,
        ]);

        $reflection = new ReflectionClass($generator);
        $method = $reflection->getMethod('getPackageJsonConfig');
        $method->setAccessible(true);

        $config = $method->invoke($generator);

        $this->assertArrayHasKey('dependencies', $config);
        $this->assertArrayHasKey('react', $config['dependencies']);
        $this->assertArrayHasKey('react-dom', $config['dependencies']);
        $this->assertArrayHasKey('@vitejs/plugin-react', $config['devDependencies']);
        $this->assertArrayHasKey('@types/react', $config['devDependencies']);
        $this->assertArrayHasKey('@types/react-dom', $config['devDependencies']);
    }

    /**
     * Test package.json includes Vue dependencies
     *
     * @return void
     */
    public function testPackageJsonVueDependencies(): void
    {
        $generator = $this->createGenerator([
            'framework' => 'vue',
            'typescript' => true,
            'tailwind' => false,
        ]);

        $reflection = new ReflectionClass($generator);
        $method = $reflection->getMethod('getPackageJsonConfig');
        $method->setAccessible(true);

        $config = $method->invoke($generator);

        $this->assertArrayHasKey('vue', $config['dependencies']);
        $this->assertArrayHasKey('@vitejs/plugin-vue', $config['devDependencies']);
        $this->assertArrayHasKey('vue-tsc', $config['devDependencies']);
    }

    /**
     * Test package.json includes Tailwind dependencies
     *
     * @return void
     */
    public function testPackageJsonTailwindDependencies(): void
    {
        $generator = $this->createGenerator([
            'framework' => 'react',
            'typescript' => true,
            'tailwind' => true,
        ]);

        $reflection = new ReflectionClass($generator);
        $method = $reflection->getMethod('getPackageJsonConfig');
        $method->setAccessible(true);

        $config = $method->invoke($generator);

        $this->assertArrayHasKey('tailwindcss', $config['devDependencies']);
        $this->assertArrayHasKey('postcss', $config['devDependencies']);
        $this->assertArrayHasKey('autoprefixer', $config['devDependencies']);
    }

    /**
     * Test package.json includes TypeScript dependency
     *
     * @return void
     */
    public function testPackageJsonTypescriptDependency(): void
    {
        $generator = $this->createGenerator([
            'framework' => 'react',
            'typescript' => true,
            'tailwind' => false,
        ]);

        $reflection = new ReflectionClass($generator);
        $method = $reflection->getMethod('getPackageJsonConfig');
        $method->setAccessible(true);

        $config = $method->invoke($generator);

        $this->assertArrayHasKey('typescript', $config['devDependencies']);
    }

    /**
     * Test package.json does not include TypeScript when disabled
     *
     * @return void
     */
    public function testPackageJsonNoTypescriptWhenDisabled(): void
    {
        $generator = $this->createGenerator([
            'framework' => 'vanilla',
            'typescript' => false,
            'tailwind' => false,
        ]);

        $reflection = new ReflectionClass($generator);
        $method = $reflection->getMethod('getPackageJsonConfig');
        $method->setAccessible(true);

        $config = $method->invoke($generator);

        $this->assertArrayNotHasKey('typescript', $config['devDependencies']);
    }

    /**
     * Test package.json includes npm scripts
     *
     * @return void
     */
    public function testPackageJsonScripts(): void
    {
        $generator = $this->createGenerator([
            'framework' => 'react',
            'typescript' => true,
            'tailwind' => false,
        ]);

        $reflection = new ReflectionClass($generator);
        $method = $reflection->getMethod('getPackageJsonConfig');
        $method->setAccessible(true);

        $config = $method->invoke($generator);

        $this->assertArrayHasKey('scripts', $config);
        $this->assertSame('vite', $config['scripts']['dev']);
        $this->assertSame('vite build', $config['scripts']['build']);
        $this->assertSame('vite preview', $config['scripts']['preview']);
    }

    /**
     * Test package.json type is module
     *
     * @return void
     */
    public function testPackageJsonTypeModule(): void
    {
        $generator = $this->createGenerator([
            'framework' => 'react',
            'typescript' => true,
            'tailwind' => false,
        ]);

        $reflection = new ReflectionClass($generator);
        $method = $reflection->getMethod('getPackageJsonConfig');
        $method->setAccessible(true);

        $config = $method->invoke($generator);

        $this->assertSame('module', $config['type']);
    }

    // =========================================================================
    // Layout Template Tests
    // =========================================================================

    /**
     * Test layout template includes ViteScripts helper
     *
     * @return void
     */
    public function testLayoutTemplateIncludesViteHelper(): void
    {
        $generator = $this->createGenerator([
            'framework' => 'react',
            'typescript' => true,
            'tailwind' => false,
        ]);

        $reflection = new ReflectionClass($generator);
        $method = $reflection->getMethod('getLayoutTemplate');
        $method->setAccessible(true);

        $template = $method->invoke($generator);

        $this->assertStringContainsString('$this->ViteScripts->script()', $template);
        $this->assertStringContainsString('<div id="app">', $template);
        $this->assertStringContainsString('<!DOCTYPE html>', $template);
    }

    // =========================================================================
    // CakePHP Vite Config Tests
    // =========================================================================

    /**
     * Test CakePHP Vite config structure
     *
     * @return void
     */
    public function testCakeViteConfigStructure(): void
    {
        $generator = $this->createGenerator([
            'framework' => 'react',
            'typescript' => true,
            'tailwind' => false,
        ]);

        $reflection = new ReflectionClass($generator);
        $method = $reflection->getMethod('getCakeViteConfig');
        $method->setAccessible(true);

        $config = $method->invoke($generator);

        $this->assertStringContainsString("'ViteHelper'", $config);
        $this->assertStringContainsString("'build'", $config);
        $this->assertStringContainsString("'development'", $config);
        $this->assertStringContainsString("'outDirectory' => 'dist'", $config);
        $this->assertStringContainsString('main.tsx', $config);
    }
}
