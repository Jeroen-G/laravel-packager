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
}
