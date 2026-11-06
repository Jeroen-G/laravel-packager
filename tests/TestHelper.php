<?php

declare(strict_types=1);

namespace JeroenG\Packager\Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

trait TestHelper
{
    protected function seeInConsoleOutput($expectedText): void
    {
        $consoleOutput = $this->app[Kernel::class]->output();
        $this->assertStringContainsString(
            $expectedText,
            $consoleOutput,
            "Did not see `{$expectedText}` in console output: `$consoleOutput`"
        );
    }

    protected function installTestApp(): void
    {
        $this->uninstallTestApp();
        $files = new Filesystem();
        $files->copyDirectory(self::TEST_APP_TEMPLATE, static::testApp());
    }

    protected function uninstallTestApp(): void
    {
        $files = new Filesystem();
        if ($files->exists(static::testApp())) {
            $files->deleteDirectory(static::testApp());
        }
    }

    /**
     * Create a modified copy of testbench to be used as a template.
     * Before each test, a fresh copy of the template is created.
     */
    private static function setUpLocalTestbench(): void
    {
        fwrite(STDOUT, "Setting up test environment for first use.\n");
        $files = new Filesystem();
        $files->makeDirectory(self::TEST_APP_TEMPLATE, 0755, true);
        $original = __DIR__.'/../vendor/orchestra/testbench-core/laravel';
        // Testbench may symlink its skeleton's vendor directory to ours, which must not be copied.
        foreach (array_diff(scandir($original), ['.', '..', 'vendor']) as $item) {
            if (is_dir($original.'/'.$item)) {
                $files->copyDirectory($original.'/'.$item, self::TEST_APP_TEMPLATE.'/'.$item);
            } else {
                $files->copy($original.'/'.$item, self::TEST_APP_TEMPLATE.'/'.$item);
            }
        }
        // Modify the composer.json file
        $composer = json_decode($files->get(self::TEST_APP_TEMPLATE.'/composer.json'), true);
        // Remove "tests/IntegrationTestCase.php" from autoload (it doesn't exist)
        unset($composer['autoload']['classmap'][1]);
        // Install stable version
        $composer['minimum-stability'] = 'stable';
        $files->put(self::TEST_APP_TEMPLATE.'/composer.json', json_encode($composer, JSON_PRETTY_PRINT));
        // Install dependencies
        fwrite(STDOUT, "Installing test environment dependencies\n");
        (new Process(['composer', 'install', '--no-dev'], self::TEST_APP_TEMPLATE))->run(function ($type, $buffer) {
            fwrite(STDOUT, $buffer);
        });
    }
}
