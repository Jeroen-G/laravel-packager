<?php

declare(strict_types=1);

namespace JeroenG\Packager\Tests;

use Orchestra\Testbench\TestCase as TestBench;

abstract class IntegrationTestCase extends TestBench
{
    use TestHelper;

    protected const TEST_APP_TEMPLATE = __DIR__.'/../testbench/template';

    public static function setUpBeforeClass(): void
    {
        if (!file_exists(self::TEST_APP_TEMPLATE)) {
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

    public static function applicationBasePath(): string
    {
        return static::testApp();
    }

    /**
     * Each process gets its own application, so parallel runs (e.g. Infection threads)
     * cannot see each other's packages and composer.json changes.
     */
    protected static function testApp(): string
    {
        return __DIR__.'/../testbench/laravel-'.getmypid();
    }

    protected function getPackageProviders($app): array
    {
        return ['JeroenG\Packager\PackagerServiceProvider'];
    }
}
