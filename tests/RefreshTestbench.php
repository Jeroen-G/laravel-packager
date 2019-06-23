<?php

namespace JeroenG\Packager\Tests;

use Exception;
use JeroenG\Packager\ComposerHandler;
use JeroenG\Packager\Conveyor;
use JeroenG\Packager\FileHandler;

trait RefreshTestbench
{
    use ComposerHandler, FileHandler;

    private static function getLocalTestbenchPath(): ?string
    {
        return __DIR__.'/../testbench';
    }

    private static function getTestbenchTemplatePath(): string
    {
        return self::getLocalTestbenchPath() .'/template';
    }

    private static function getTestbenchWorkingCopyPath(): string
    {
        return self::getLocalTestbenchPath() . '/laravel';
    }

    /**
     * Create a modified copy of testbench to be used as a template.
     * Before each test, a fresh copy of the template is created.
     */
    private static function setUpLocalTestbench(): void
    {
        try {
            fwrite(STDOUT, "Setting up test environment for first use.\n");
            Conveyor::fetchSkeleton(
                'http://github.com/Jeroen-G/packager-skeleton/archive/master.zip',
                Conveyor::getSkeletonCachePath()
            );
            $instance = new self;
            $instance->makeDir(self::getTestbenchTemplatePath());
            $original = __DIR__.'/../vendor/orchestra/testbench-core/laravel/';
            $instance->copyDir($original, self::getTestbenchTemplatePath());
            self::modifyComposerJson(function (array $composer) {
                // Remove "tests/TestCase.php" from autoload (it doesn't exist)
                unset($composer['autoload']['classmap'][1]);
                // Pre-install dependencies
                $composer['require'] = ['illuminate/support' => '~5'];
                $composer['minimum-stability'] = 'stable';
                // Enable optimized autoloader, allowing to test if package classes are properly installed
                $composer['config'] = [
                    'optimize-autoloader' => true,
                    'preferred-install' => 'dist',
                ];
                return $composer;
            }, self::getTestbenchTemplatePath());
            fwrite(STDOUT, "Installing test environment dependencies\n");
            self::runComposerCommand([
                'install',
                '--prefer-dist',
                '--no-progress'
            ], self::getTestbenchTemplatePath());
            fwrite(STDOUT, "Test environment installed\n");
        } catch (Exception $e) {
            if (isset($instance)){
                $instance->removeDir(self::getTestbenchTemplatePath());
            }
        }
    }

    protected function installTestApp(): void
    {
        if ($this->pathExists(self::getTestbenchWorkingCopyPath())){
            $this->uninstallTestApp();
        }
        $this->copyDir(self::getTestbenchTemplatePath(), self::getTestbenchWorkingCopyPath());
    }

    protected function uninstallTestApp(): void
    {
        $this->removeDir(self::getTestbenchWorkingCopyPath());
    }

    public static function setUpBeforeClass(): void
    {
        if (!file_exists(self::getTestbenchTemplatePath())) {
            self::setUpLocalTestbench();
        }
        parent::setUpBeforeClass();
    }

    protected function getBasePath(): string
    {
        return self::getTestbenchWorkingCopyPath();
    }

    /**
     * Setup before each test.
     */
    public function setUp(): void
    {
        $this->installTestApp();
        parent::setUp();
        config()->set('packager.cache_skeleton', true);
    }

    /**
     * Tear down after each test.
     */
    public function tearDown(): void
    {
        $this->uninstallTestApp();
        parent::tearDown();
    }
}
