<?php

declare(strict_types=1);

namespace JeroenG\Packager\Tests\Unit\Commands;

use JeroenG\Packager\Tests\CommandTestCase;

final class RemovePackageTest extends CommandTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        mkdir($this->packagePath('acme', 'rocket').'/src', 0777, true);
        file_put_contents($this->packagePath('acme', 'rocket').'/src/Rocket.php', '<?php');
    }

    public function test_it_uninstalls_and_removes_the_package(): void
    {
        $this->expectUninstall('acme', 'rocket');

        $this->artisan('packager:remove', ['vendor' => 'acme', 'name' => 'rocket'])
            ->expectsConfirmation('Do you want to remove the vendor directory? [y|N]', 'no')
            ->expectsOutputToContain('Package removed successfully!')
            ->assertExitCode(0);

        $this->assertDirectoryDoesNotExist($this->packagePath('acme', 'rocket'));
        $this->assertDirectoryExists(dirname($this->packagePath('acme', 'rocket')));
    }

    public function test_it_accepts_vendor_and_name_separated_by_a_slash(): void
    {
        $this->expectUninstall('acme', 'rocket');

        $this->artisan('packager:remove', ['vendor' => 'acme/rocket'])
            ->expectsConfirmation('Do you want to remove the vendor directory? [y|N]', 'no')
            ->assertExitCode(0);

        $this->assertDirectoryDoesNotExist($this->packagePath('acme', 'rocket'));
    }

    public function test_it_removes_an_empty_vendor_directory_when_confirmed(): void
    {
        $this->expectUninstall('acme', 'rocket');

        $this->artisan('packager:remove', ['vendor' => 'acme', 'name' => 'rocket'])
            ->expectsConfirmation('Do you want to remove the vendor directory? [y|N]', 'yes')
            ->expectsOutputToContain('removing vendor directory...')
            ->assertExitCode(0);

        $this->assertDirectoryDoesNotExist(dirname($this->packagePath('acme', 'rocket')));
    }

    public function test_it_keeps_a_vendor_directory_with_other_packages(): void
    {
        mkdir($this->packagePath('acme', 'anvil'), 0777, true);
        $this->expectUninstall('acme', 'rocket');

        $this->artisan('packager:remove', ['vendor' => 'acme', 'name' => 'rocket'])
            ->expectsConfirmation('Do you want to remove the vendor directory? [y|N]', 'yes')
            ->expectsOutputToContain('vendor directory is not empty, continuing...')
            ->assertExitCode(0);

        $this->assertDirectoryExists($this->packagePath('acme', 'anvil'));
    }

    public function test_it_does_not_crash_when_the_package_directory_is_already_gone(): void
    {
        $this->expectUninstall('acme', 'comet');

        $this->artisan('packager:remove', ['vendor' => 'acme', 'name' => 'comet'])
            ->expectsConfirmation('Do you want to remove the vendor directory? [y|N]', 'no')
            ->expectsOutputToContain('Package removed successfully!')
            ->assertExitCode(0);
    }

    public function test_it_requires_a_package_name(): void
    {
        $this->expectExceptionMessage('Please provide a package name');

        $this->artisan('packager:remove', ['vendor' => 'acme'])->run();
    }

    private function expectUninstall(string $vendor, string $package): void
    {
        $this->commandRunner->expectRun(['composer', 'remove', $vendor.'/'.$package]);
        $this->commandRunner->expectRun(['composer', 'config', '--unset', 'repositories.'.$vendor.'/'.$package]);
    }
}
