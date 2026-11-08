<?php

declare(strict_types=1);

namespace JeroenG\Packager\Tests\Unit\Commands;

use JeroenG\Packager\Tests\PackagerTestCase;
use JeroenG\Packager\Tests\Support\MocksFileHandler;

final class RemovePackageTest extends PackagerTestCase
{
    use MocksFileHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockFileHandler();
    }

    public function test_it_uninstalls_and_removes_the_package(): void
    {
        $this->expectUninstall('acme', 'rocket');
        $this->files->expects('removeDir')->with('/app/packages/acme/rocket')->andReturnTrue();
        $this->files->shouldNotReceive('exists', 'isEmptyDir');

        $this->command('packager:remove', ['vendor' => 'acme', 'name' => 'rocket'])
            ->expectsConfirmation('Do you want to remove the vendor directory? [y|N]', 'no')
            ->expectsOutputToContain('Continuing...')
            ->expectsOutputToContain('Package removed successfully!')
            ->assertExitCode(0);
    }

    public function test_it_removes_the_package_by_the_name_in_its_composer_json(): void
    {
        $this->files->expects('read')->with('/app/packages/acme/rocket/composer.json')->andReturn('{"name": "wile/rocket"}');
        $this->commandRunner->expectRun(['composer', 'remove', 'wile/rocket']);
        $this->commandRunner->expectRun(['composer', 'config', '--unset', 'repositories.acme/rocket']);
        $this->files->expects('removeDir')->with('/app/packages/acme/rocket')->andReturnTrue();

        $this->command('packager:remove', ['vendor' => 'acme', 'name' => 'rocket'])
            ->expectsConfirmation('Do you want to remove the vendor directory? [y|N]', 'no')
            ->assertExitCode(0);
    }

    public function test_it_accepts_vendor_and_name_separated_by_a_slash(): void
    {
        $this->expectUninstall('acme', 'rocket');
        $this->files->expects('removeDir')->with('/app/packages/acme/rocket')->andReturnTrue();

        $this->command('packager:remove', ['vendor' => 'acme/rocket'])
            ->expectsConfirmation('Do you want to remove the vendor directory? [y|N]', 'no')
            ->assertExitCode(0);
    }

    public function test_it_removes_an_empty_vendor_directory_when_confirmed(): void
    {
        $this->expectUninstall('acme', 'rocket');
        $this->files->expects('removeDir')->with('/app/packages/acme/rocket')->andReturnTrue();
        $this->files->expects('exists')->with('/app/packages/acme')->andReturnTrue();
        $this->files->expects('isEmptyDir')->with('/app/packages/acme')->andReturnTrue();
        $this->files->expects('removeDir')->with('/app/packages/acme')->andReturnTrue();

        $this->command('packager:remove', ['vendor' => 'acme', 'name' => 'rocket'])
            ->expectsConfirmation('Do you want to remove the vendor directory? [y|N]', 'yes')
            ->expectsOutputToContain('removing vendor directory...')
            ->expectsOutputToContain('Package removed successfully!')
            ->assertExitCode(0);
    }

    public function test_it_keeps_a_vendor_directory_with_other_packages(): void
    {
        $this->expectUninstall('acme', 'rocket');
        $this->files->expects('removeDir')->with('/app/packages/acme/rocket')->andReturnTrue();
        $this->files->expects('exists')->with('/app/packages/acme')->andReturnTrue();
        $this->files->expects('isEmptyDir')->with('/app/packages/acme')->andReturnFalse();

        $this->command('packager:remove', ['vendor' => 'acme', 'name' => 'rocket'])
            ->expectsConfirmation('Do you want to remove the vendor directory? [y|N]', 'yes')
            ->expectsOutputToContain('vendor directory is not empty, continuing...')
            ->doesntExpectOutputToContain('removing vendor directory...')
            ->assertExitCode(0);
    }

    public function test_it_continues_when_the_vendor_directory_is_already_gone(): void
    {
        $this->expectUninstall('acme', 'rocket');
        $this->files->expects('removeDir')->with('/app/packages/acme/rocket')->andReturnTrue();
        $this->files->expects('exists')->with('/app/packages/acme')->andReturnFalse();
        $this->files->shouldNotReceive('isEmptyDir');

        $this->command('packager:remove', ['vendor' => 'acme', 'name' => 'rocket'])
            ->expectsConfirmation('Do you want to remove the vendor directory? [y|N]', 'yes')
            ->expectsOutputToContain('vendor directory does not exist, continuing...')
            ->expectsOutputToContain('Package removed successfully!')
            ->assertExitCode(0);
    }

    public function test_it_does_not_crash_when_the_package_directory_is_already_gone(): void
    {
        $this->expectUninstall('acme', 'comet');
        $this->files->expects('removeDir')->with('/app/packages/acme/comet')->andReturnFalse();
        $this->files->expects('exists')->with('/app/packages/acme/comet')->andReturnFalse();

        $this->command('packager:remove', ['vendor' => 'acme', 'name' => 'comet'])
            ->expectsConfirmation('Do you want to remove the vendor directory? [y|N]', 'no')
            ->expectsOutputToContain('Package removed successfully!')
            ->assertExitCode(0);
    }

    public function test_it_requires_a_package_name(): void
    {
        $this->files->shouldNotReceive('removeDir');

        $this->command('packager:remove', ['vendor' => 'acme'])
            ->expectsOutputToContain('Please provide a package name')
            ->assertExitCode(1);
    }

    public function test_it_requires_a_package_name_after_the_slash(): void
    {
        $this->files->shouldNotReceive('removeDir');

        $this->command('packager:remove', ['vendor' => 'acme/'])
            ->expectsOutputToContain('Please provide a package name')
            ->assertExitCode(1);
    }

    public function test_it_keeps_the_package_when_composer_cannot_remove_it(): void
    {
        $this->commandRunner->expectRun(['composer', 'remove', 'acme/rocket'], false);
        $this->files->shouldNotReceive('removeDir');

        $this->command('packager:remove', ['vendor' => 'acme', 'name' => 'rocket'])
            ->expectsOutputToContain('Composer could not remove the package from composer.json, so its files were kept.')
            ->doesntExpectOutputToContain('Package removed successfully!')
            ->assertExitCode(1);
    }

    public function test_it_reports_a_package_directory_it_could_not_remove(): void
    {
        // FileHandler::removeDir() refuses paths outside packages/ (see FileHandlerTest), like the vendor directory here.
        $this->commandRunner->getMock()->allows('run')->andReturnTrue();
        $this->files->expects('removeDir')->with('/app/packages/acme/..')->andReturnFalse();
        $this->files->expects('exists')->with('/app/packages/acme/..')->andReturnTrue();

        $this->command('packager:remove', ['vendor' => 'acme', 'name' => '..'])
            ->expectsOutputToContain('The package was uninstalled, but its directory /app/packages/acme/.. could not be removed.')
            ->doesntExpectOutputToContain('Do you want to remove the vendor directory?')
            ->doesntExpectOutputToContain('Package removed successfully!')
            ->assertExitCode(1);
    }

    private function expectUninstall(string $vendor, string $package): void
    {
        $this->commandRunner->expectRun(['composer', 'remove', $vendor.'/'.$package]);
        $this->commandRunner->expectRun(['composer', 'config', '--unset', 'repositories.'.$vendor.'/'.$package]);
    }
}
