<?php

namespace JeroenG\Packager\Tests;

use Orchestra\Testbench\TestCase as TestBench;

abstract class TestCase extends TestBench
{
    use TestHelper;

    /**
     * Setup before each test.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->updateConfigFile();
    }

    public function updateConfigFile()
    {
        $filledFile = str_replace('Illuminate\View\ViewServiceProvider::class,',
        'Illuminate\View\ViewServiceProvider::class,
        /*
         * Package Service Providers...
         */', file_get_contents(config_path('app.php')));
        file_put_contents(config_path('app.php'), $filledFile);
    }

    /**
     * Tear down after each test.
     * @return  void
     */
    public function tearDown()
    {
        $this->removeDir(base_path('packages'));
        $this->undoConfigFile();

        parent::tearDown();
    }

    public function undoConfigFile()
    {
        $filledFile = str_replace('
        /*
         * Package Service Providers...
         */', '', file_get_contents(config_path('app.php')));
        file_put_contents(config_path('app.php'), $filledFile);

        $filledFile = str_replace('MyVendor\MyPackage\MyPackageServiceProvider::class,', '', file_get_contents(config_path('app.php')));
        file_put_contents(config_path('app.php'), $filledFile);
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
}
