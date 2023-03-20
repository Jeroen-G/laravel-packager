<?php

declare(strict_types=1);

namespace JeroenG\Packager\Tests;

use Orchestra\Testbench\TestCase as TestBench;

abstract class IntegrationTestCase extends TestBench
{
    use TestHelper;

    protected const TEST_APP_TEMPLATE = __DIR__.'/../testbench/template';

    protected const TEST_APP = __DIR__.'/../testbench/laravel';

    public static function setUpBeforeClass(): void
    {
        if (! file_exists(self::TEST_APP_TEMPLATE)) {
            self::setUpLocalTestbench();
        }
        parent::setUpBeforeClass();
    }

    public function setUp(): void
    {
        $this->installTestApp();
        parent::setUp();
    }

    public function tearDown(): void
    {
        $this->uninstallTestApp();
        parent::tearDown();
    }

    protected function getBasePath(): string
    {
        return self::TEST_APP;
    }

    protected function getPackageProviders($app): array
    {
        return ['JeroenG\Packager\PackagerServiceProvider'];
    }
}
