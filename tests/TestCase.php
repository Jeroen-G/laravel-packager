<?php

namespace JeroenG\Packager\Tests;

use Illuminate\Contracts\Console\Kernel;
use Orchestra\Testbench\TestCase as TestBench;

abstract class TestCase extends TestBench
{
    /**
     * Setup before each test.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
    }

    /**
     * Tear down after each test.
     * @return  void
     */
    public function tearDown()
    {
        parent::tearDown();

        $this->removeDir(getcwd().'/packages');
    }

    public function removeDir($path)
    {
        $files = array_diff(scandir($path), ['.', '..']);
        foreach ($files as $file) {
            if (is_dir("$path/$file")) {
                $this->removeDir("$path/$file");
            } else {
                @chmod("$path/$file", 0777);
                @unlink("$path/$file");
            }
        }

        return rmdir($path);
    }

    /**
     * Tell Testbench to use this package.
     * @param $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return ['JeroenG\Packager\PackagerServiceProvider'];
    }

    protected function seeInConsoleOutput($expectedText)
    {
        $consoleOutput = $this->app[Kernel::class]->output();
        $this->assertContains($expectedText, $consoleOutput, "Did not see `{$expectedText}` in console output: `$consoleOutput`");
    }

    protected function doNotSeeInConsoleOutput($unExpectedText)
    {
        $consoleOutput = $this->app[Kernel::class]->output();
        $this->assertNotContains($unExpectedText, $consoleOutput, "Did not expect to see `{$unExpectedText}` in console output: `$consoleOutput`");
    }
}
