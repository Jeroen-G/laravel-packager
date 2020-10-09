<?php

namespace JeroenG\Packager\Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

trait TestHelper
{
    protected function seeInConsoleOutput($expectedText)
    {
        $consoleOutput = $this->app[Kernel::class]->output();
        $this->assertStringContainsString($expectedText, $consoleOutput,
            "Did not see `{$expectedText}` in console output: `$consoleOutput`");
    }

    protected function doNotSeeInConsoleOutput($unExpectedText)
    {
        $consoleOutput = $this->app[Kernel::class]->output();
        $this->assertStringNotContainsString($unExpectedText, $consoleOutput,
            "Did not expect to see `{$unExpectedText}` in console output: `$consoleOutput`");
    }

    /**
     * Create a modified copy of testbench to be used as a template.
     * Before each test, a fresh copy of the template is created.
     */
    private static function setUpLocalTestbench()
    {
        fwrite(STDOUT, "Setting up test environment for first use.\n");
        $files = new Filesystem();
        $files->makeDirectory(self::TEST_APP_TEMPLATE, 0755, true);
        $original = __DIR__.'/../vendor/orchestra/testbench-core/laravel/';
        $files->copyDirectory($original, self::TEST_APP_TEMPLATE);
        // Modify the composer.json file
        $composer = json_decode($files->get(self::TEST_APP_TEMPLATE.'/composer.json'), true);
        // Remove "tests/TestCase.php" from autoload (it doesn't exist)
        unset($composer['autoload']['classmap'][1]);
        // Pre-install illuminate/support
        $composer['require'] = ['illuminate/support' => '~5'];
        // Install stable version
        $composer['minimum-stability'] = 'stable';
        $files->put(self::TEST_APP_TEMPLATE.'/composer.json', json_encode($composer, JSON_PRETTY_PRINT));
        // Install dependencies
        fwrite(STDOUT, "Installing test environment dependencies\n");
        (new Process(['composer', 'install', '--no-dev'], self::TEST_APP_TEMPLATE))->run(function ($type, $buffer) {
            fwrite(STDOUT, $buffer);
        });
    }

    protected function installTestApp()
    {
        $this->uninstallTestApp();
        $files = new Filesystem();
        $files->copyDirectory(self::TEST_APP_TEMPLATE, self::TEST_APP);
    }

    protected function uninstallTestApp()
    {
        $files = new Filesystem();
        if ($files->exists(self::TEST_APP)) {
            $files->deleteDirectory(self::TEST_APP);
        }
    }
}
