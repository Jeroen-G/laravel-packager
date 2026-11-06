<?php

declare(strict_types=1);

namespace JeroenG\Packager\Tests;

use Illuminate\Filesystem\Filesystem;
use JeroenG\Packager\CommandRunnerInterface;
use JeroenG\Packager\PackagerServiceProvider;
use JeroenG\Packager\Tests\Support\CommandRunnerExpectation;
use Orchestra\Testbench\TestCase as TestBench;

/**
 * Runs Packager commands in a lightweight application whose base path is a temporary
 * directory and whose external commands (git, composer) are mocked.
 */
abstract class CommandTestCase extends TestBench
{
    protected string $basePath;

    protected CommandRunnerExpectation $commandRunner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'packager-'.uniqid('', true);
        mkdir($this->basePath);
        $this->app->setBasePath($this->basePath);

        $this->commandRunner = CommandRunnerExpectation::create();
        $this->app->instance(CommandRunnerInterface::class, $this->commandRunner->getMock());
    }

    protected function tearDown(): void
    {
        (new Filesystem())->deleteDirectory($this->basePath);

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [PackagerServiceProvider::class];
    }

    protected function packagePath(string $vendor, string $package): string
    {
        return $this->basePath.DIRECTORY_SEPARATOR.'packages'.DIRECTORY_SEPARATOR.$vendor.DIRECTORY_SEPARATOR.$package;
    }

    protected function writeComposerJson(array $contents): void
    {
        file_put_contents($this->basePath.'/composer.json', json_encode($contents, JSON_PRETTY_PRINT));
    }
}
