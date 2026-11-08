<?php

declare(strict_types=1);

namespace JeroenG\Packager\Tests;

use JeroenG\Packager\CommandRunnerInterface;
use JeroenG\Packager\PackagerServiceProvider;
use JeroenG\Packager\Tests\Support\CommandRunnerExpectation;
use JeroenG\Packager\Tests\Support\RunsCommands;
use Orchestra\Testbench\TestCase as TestBench;

/**
 * Runs Packager commands in a lightweight application whose external commands (git, composer) are mocked.
 */
abstract class PackagerTestCase extends TestBench
{
    use RunsCommands;

    protected CommandRunnerExpectation $commandRunner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandRunner = CommandRunnerExpectation::create();
        app()->instance(CommandRunnerInterface::class, $this->commandRunner->getMock());
    }

    protected function getPackageProviders($app): array
    {
        return [PackagerServiceProvider::class];
    }
}
