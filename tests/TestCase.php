<?php

namespace JeroenG\Packager\Tests;

use JeroenG\Packager\Tests\TestHelper;
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
    }

    /**
     * Tear down after each test.
     * @return  void
     */
    public function tearDown()
    {
        parent::tearDown();

        $this->removeDir(base_path('packages'));
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
