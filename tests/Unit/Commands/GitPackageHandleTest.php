<?php

declare(strict_types=1);

namespace JeroenG\Packager\Tests\Unit\Commands;

use JeroenG\Packager\Tests\CommandTestCase;

final class GitPackageHandleTest extends CommandTestCase
{
    private const URL = 'https://github.com/Acme/Rocket.git';

    public function test_it_clones_and_installs_the_package(): void
    {
        $path = $this->packagePath('acme', 'rocket');

        $this->commandRunner->expectRun(['git', 'clone', '-q', self::URL, $path], true);
        $this->commandRunner->getMock()->allows('run')->andReturnTrue();

        $this->command('packager:git', ['url' => self::URL])
            ->expectsOutputToContain('Creating package acme\rocket...')
            ->expectsOutputToContain('Package cloned successfully!')
            ->assertExitCode(0);
    }

    public function test_it_uses_the_given_vendor_and_name(): void
    {
        $path = $this->packagePath('MyVendor', 'MyPackage');

        $this->commandRunner->expectRun(['git', 'clone', '-q', self::URL, $path], true);
        $this->commandRunner->getMock()->allows('run')->andReturnTrue();

        $this->command('packager:git', ['url' => self::URL, 'vendor' => 'MyVendor', 'name' => 'MyPackage'])
            ->expectsOutputToContain('Creating package MyVendor\MyPackage...')
            ->assertExitCode(0);
    }

    public function test_it_asks_for_vendor_and_name_when_the_url_has_none(): void
    {
        $this->command('packager:git', ['url' => 'file:///rocket.git'])
            ->expectsOutputToContain('Unable to determine the vendor and package from file:///rocket.git, please provide them: packager:git <url> <vendor> <name>')
            ->doesntExpectOutputToContain('Creating package')
            ->assertExitCode(1);

        $this->assertDirectoryDoesNotExist($this->basePath.'/packages');
    }

    public function test_it_clones_a_local_repository_by_its_path(): void
    {
        $path = $this->packagePath('acme', 'rocket');

        $this->commandRunner->expectRun(['git', 'clone', '-q', 'file:///repositories/acme/rocket.git', $path], true);
        $this->commandRunner->getMock()->allows('run')->andReturnTrue();

        $this->command('packager:git', ['url' => 'file:///repositories/acme/rocket.git'])
            ->expectsOutputToContain('Package cloned successfully!')
            ->assertExitCode(0);
    }

    public function test_it_fails_when_the_repository_cannot_be_cloned(): void
    {
        $path = $this->packagePath('acme', 'rocket');

        $this->commandRunner->expectRun(['git', 'clone', '-q', self::URL, $path], false);

        $this->command('packager:git', ['url' => self::URL])
            ->expectsOutputToContain('Unable to clone repository')
            ->doesntExpectOutputToContain('Package cloned successfully!')
            ->assertExitCode(1);
    }

    public function test_it_reports_a_failing_composer_install(): void
    {
        $path = $this->packagePath('acme', 'rocket');

        $this->commandRunner->expectRun(['git', 'clone', '-q', self::URL, $path], true);
        $this->commandRunner->getMock()->allows('run')->andReturnFalse();

        $this->command('packager:git', ['url' => self::URL])
            ->expectsOutputToContain('Composer could not install the package.')
            ->doesntExpectOutputToContain('Package cloned successfully!')
            ->assertExitCode(1);
    }
}
