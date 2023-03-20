<?php

declare(strict_types=1);

namespace JeroenG\Packager\Tests\Unit;

use JeroenG\Packager\Conveyor;
use JeroenG\Packager\FileHandler;
use JeroenG\Packager\Tests\Support\CommandRunnerExpectation;
use JeroenG\Packager\Tests\UnitTestCase;

final class ConveyorTest extends UnitTestCase
{
    private CommandRunnerExpectation $commandRunner;

    private Conveyor $conveyor;

    private FileHandler|\Mockery\MockInterface $fileHandler;

    protected function setUp(): void
    {
        $this->commandRunner = CommandRunnerExpectation::create();
        $this->fileHandler = \Mockery::mock(FileHandler::class);

        $this->conveyor = new Conveyor($this->commandRunner->getMock(), $this->fileHandler);
    }

    public function test_it_calls_process_to_install_package(): void
    {
        $this->conveyor->vendor('myVendor');
        $this->conveyor->package('myPackage');

        $this->fileHandler->expects('packagePath')->andReturn('packages/myvendor/mypackage');
        $this->commandRunner->expectRun(['composer', 'require', 'myVendor/myPackage:@dev'], true);
        $this->commandRunner->expectRun([
            'composer',
            'config',
            'repositories.myvendor/mypackage',
            '{"type":"path","url":"packages\/myvendor\/mypackage","options":{"symlink":true}}',
            '--file',
            'composer.json',
        ], true);

        $this->expectNotToPerformAssertions();
        $this->conveyor->installPackage();
    }

    public function test_it_calls_process_to_uninstall_package(): void
    {
        $this->conveyor->vendor('myVendor');
        $this->conveyor->package('myPackage');

        $this->commandRunner->expectRun(['composer', 'config', '--unset', 'repositories.myvendor/mypackage'], true);
        $this->commandRunner->expectRun(['composer', 'remove', 'myVendor/myPackage'], true);

        $this->expectNotToPerformAssertions();
        $this->conveyor->uninstallPackage();
    }
}
