<?php

declare(strict_types=1);

namespace JeroenG\Packager\Tests\Unit\Commands;

use JeroenG\Packager\Tests\CommandTestCase;

final class EnableDisablePackageTest extends CommandTestCase
{
    public function test_it_enables_a_package(): void
    {
        $path = $this->packagePath('acme', 'rocket');

        $this->commandRunner->expectRun([
            'composer',
            'config',
            'repositories.acme/rocket',
            json_encode(['type' => 'path', 'url' => $path, 'options' => ['symlink' => true]], JSON_THROW_ON_ERROR),
            '--file',
            'composer.json',
        ]);
        $this->commandRunner->expectRun(['composer', 'require', 'acme/rocket:@dev']);

        $this->command('packager:enable', ['vendor' => 'acme', 'name' => 'rocket'])
            ->expectsOutputToContain('Enabling package acme\rocket...')
            ->assertExitCode(0);
    }

    public function test_it_disables_a_package(): void
    {
        $this->commandRunner->expectRun(['composer', 'remove', 'acme/rocket']);
        $this->commandRunner->expectRun(['composer', 'config', '--unset', 'repositories.acme/rocket']);

        $this->command('packager:disable', ['vendor' => 'acme', 'name' => 'rocket'])
            ->expectsOutputToContain('Disabling package acme\rocket...')
            ->assertExitCode(0);
    }

    public function test_it_reports_a_failing_enable(): void
    {
        $this->commandRunner->getMock()->allows('run')->andReturnFalse();

        $this->command('packager:enable', ['vendor' => 'acme', 'name' => 'rocket'])
            ->expectsOutputToContain('Composer could not install the package.')
            ->doesntExpectOutputToContain('Package enabled successfully!')
            ->assertExitCode(1);
    }

    public function test_it_reports_a_failing_disable(): void
    {
        $this->commandRunner->getMock()->allows('run')->andReturnFalse();

        $this->command('packager:disable', ['vendor' => 'acme', 'name' => 'rocket'])
            ->expectsOutputToContain('Composer could not remove the package from composer.json.')
            ->doesntExpectOutputToContain('Package disabled successfully!')
            ->assertExitCode(1);
    }

    public function test_it_uses_a_lowercase_composer_package_name(): void
    {
        $this->commandRunner->getMock()->allows('run')->with(\Mockery::on(static fn (array $command): bool => $command[1] === 'config'))->andReturnTrue();
        $this->commandRunner->expectRun(['composer', 'require', 'myvendor/mypackage:@dev']);

        $this->command('packager:enable', ['vendor' => 'MyVendor', 'name' => 'MyPackage'])
            ->assertExitCode(0);
    }
}
