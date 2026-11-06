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

    private FileHandler&\Mockery\MockInterface $fileHandler;

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

        $this->conveyor->installPackage();
    }

    public function test_it_calls_process_to_uninstall_package(): void
    {
        $this->conveyor->vendor('myVendor');
        $this->conveyor->package('myPackage');

        $this->commandRunner->expectRun(['composer', 'config', '--unset', 'repositories.myvendor/mypackage'], true);
        $this->commandRunner->expectRun(['composer', 'remove', 'myVendor/myPackage'], true);

        $this->conveyor->uninstallPackage();
    }

    public function test_it_remembers_vendor_and_package(): void
    {
        $this->assertSame('my-vendor', $this->conveyor->vendor('my-vendor'));
        $this->assertSame('my-package', $this->conveyor->package('my-package'));

        $this->assertSame('my-vendor', $this->conveyor->vendor());
        $this->assertSame('my-package', $this->conveyor->package());
    }

    public function test_it_formats_vendor_and_package_names(): void
    {
        $this->conveyor->vendor('my-vendor');
        $this->conveyor->package('MyPackage');

        $this->assertSame('MyVendor', $this->conveyor->vendorStudly());
        $this->assertSame('my-vendor', $this->conveyor->vendorKebab());
        $this->assertSame('MyPackage', $this->conveyor->packageStudly());
        $this->assertSame('my-package', $this->conveyor->packageKebab());
    }

    public function test_it_slugs_the_repository_name(): void
    {
        $this->conveyor->vendor('My Vendor');
        $this->conveyor->package('My Package');

        $this->commandRunner->expectRun(['composer', 'config', '--unset', 'repositories.my-vendor/my-package'], true);
        $this->commandRunner->expectRun(['composer', 'remove', 'My Vendor/My Package'], true);

        $this->conveyor->uninstallPackage();
    }

    public function test_it_requires_a_vendor_name(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Please provide a vendor name');

        $this->conveyor->vendor();
    }

    public function test_it_requires_a_package_name(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Please provide a package name');

        $this->conveyor->package();
    }
}
