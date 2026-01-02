<?php
declare(strict_types=1);

namespace ViteBake\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use ReflectionClass;
use ViteBake\Command\ViteBakeCommand;

/**
 * ViteBakeCommand Test Case
 *
 * Tests the Vite setup wizard command for CakePHP.
 *
 * @covers \ViteBake\Command\ViteBakeCommand
 */
class ViteBakeCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

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
        $this->testOutputDir = TMP . 'test_output' . DS . uniqid('vite_', true) . DS;
        if (!is_dir($this->testOutputDir)) {
            mkdir($this->testOutputDir, 0777, true);
        }
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
     * Test command description contains expected information
     *
     * This verifies the option parser description without requiring
     * a full CakePHP application integration test environment.
     *
     * @return void
     */
    public function testCommandDescription(): void
    {
        $command = new ViteBakeCommand();
        $parser = $command->getOptionParser();
        $description = $parser->getDescription();

        $this->assertStringContainsString('Set up Vite with modern frontend frameworks', $description);
    }

    /**
     * Test default command name is 'bake vite'
     *
     * @return void
     */
    public function testDefaultName(): void
    {
        $command = new ViteBakeCommand();
        $this->assertSame('bake vite', $command::defaultName());
    }

    /**
     * Test available frameworks constant
     *
     * @return void
     */
    public function testAvailableFrameworks(): void
    {
        $reflection = new ReflectionClass(ViteBakeCommand::class);
        $frameworks = $reflection->getConstant('FRAMEWORKS');

        $this->assertIsArray($frameworks);
        $this->assertContains('react', $frameworks);
        $this->assertContains('vue', $frameworks);
        $this->assertContains('vanilla', $frameworks);
    }

    /**
     * Test available presets constant
     *
     * @return void
     */
    public function testAvailablePresets(): void
    {
        $reflection = new ReflectionClass(ViteBakeCommand::class);
        $presets = $reflection->getConstant('PRESETS');

        $this->assertIsArray($presets);
        $this->assertArrayHasKey('react-ts-tailwind', $presets);
        $this->assertArrayHasKey('react-ts', $presets);
        $this->assertArrayHasKey('vue-ts-tailwind', $presets);
        $this->assertArrayHasKey('vue-ts', $presets);
        $this->assertArrayHasKey('vanilla', $presets);
    }

    /**
     * Test preset configuration structure
     *
     * @return void
     */
    public function testPresetConfigurationStructure(): void
    {
        $reflection = new ReflectionClass(ViteBakeCommand::class);
        $presets = $reflection->getConstant('PRESETS');

        foreach ($presets as $name => $config) {
            $this->assertArrayHasKey('framework', $config, "Preset '{$name}' missing 'framework' key");
            $this->assertArrayHasKey('typescript', $config, "Preset '{$name}' missing 'typescript' key");
            $this->assertArrayHasKey('tailwind', $config, "Preset '{$name}' missing 'tailwind' key");

            $this->assertIsBool($config['typescript'], "Preset '{$name}' typescript should be boolean");
            $this->assertIsBool($config['tailwind'], "Preset '{$name}' tailwind should be boolean");
        }
    }

    /**
     * Test react-ts-tailwind preset values
     *
     * @return void
     */
    public function testReactTsTailwindPreset(): void
    {
        $reflection = new ReflectionClass(ViteBakeCommand::class);
        $presets = $reflection->getConstant('PRESETS');
        $preset = $presets['react-ts-tailwind'];

        $this->assertSame('react', $preset['framework']);
        $this->assertTrue($preset['typescript']);
        $this->assertTrue($preset['tailwind']);
    }

    /**
     * Test vue-ts preset values
     *
     * @return void
     */
    public function testVueTsPreset(): void
    {
        $reflection = new ReflectionClass(ViteBakeCommand::class);
        $presets = $reflection->getConstant('PRESETS');
        $preset = $presets['vue-ts'];

        $this->assertSame('vue', $preset['framework']);
        $this->assertTrue($preset['typescript']);
        $this->assertFalse($preset['tailwind']);
    }

    /**
     * Test vanilla preset values
     *
     * @return void
     */
    public function testVanillaPreset(): void
    {
        $reflection = new ReflectionClass(ViteBakeCommand::class);
        $presets = $reflection->getConstant('PRESETS');
        $preset = $presets['vanilla'];

        $this->assertSame('vanilla', $preset['framework']);
        $this->assertFalse($preset['typescript']);
        $this->assertFalse($preset['tailwind']);
    }

    /**
     * Test option parser includes framework choices
     *
     * @return void
     */
    public function testOptionParserFrameworkChoices(): void
    {
        $command = new ViteBakeCommand();
        $parser = $command->getOptionParser();
        $options = $parser->options();

        $this->assertArrayHasKey('framework', $options);
        $frameworkOption = $options['framework'];
        $this->assertSame(['react', 'vue', 'vanilla'], $frameworkOption->choices());
    }

    /**
     * Test option parser includes preset choices
     *
     * @return void
     */
    public function testOptionParserPresetChoices(): void
    {
        $command = new ViteBakeCommand();
        $parser = $command->getOptionParser();
        $options = $parser->options();

        $this->assertArrayHasKey('preset', $options);
        $presetOption = $options['preset'];
        $expectedPresets = ['react-ts-tailwind', 'react-ts', 'vue-ts-tailwind', 'vue-ts', 'vanilla'];
        $this->assertSame($expectedPresets, $presetOption->choices());
    }

    /**
     * Test option parser boolean options
     *
     * @return void
     */
    public function testOptionParserBooleanOptions(): void
    {
        $command = new ViteBakeCommand();
        $parser = $command->getOptionParser();
        $options = $parser->options();

        $this->assertArrayHasKey('typescript', $options);
        $this->assertTrue($options['typescript']->isBoolean());

        $this->assertArrayHasKey('tailwind', $options);
        $this->assertTrue($options['tailwind']->isBoolean());

        $this->assertArrayHasKey('force', $options);
        $this->assertTrue($options['force']->isBoolean());
    }

    /**
     * Test option parser short options
     *
     * @return void
     */
    public function testOptionParserShortOptions(): void
    {
        $command = new ViteBakeCommand();
        $parser = $command->getOptionParser();
        $options = $parser->options();

        $this->assertSame('f', $options['framework']->short());
        $this->assertSame('t', $options['typescript']->short());
        $this->assertSame('w', $options['tailwind']->short());
        $this->assertSame('p', $options['preset']->short());
    }
}
