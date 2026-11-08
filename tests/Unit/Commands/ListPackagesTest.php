<?php

declare(strict_types=1);

namespace JeroenG\Packager\Tests\Unit\Commands;

use JeroenG\Packager\Tests\CommandTestCase;

final class ListPackagesTest extends CommandTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->writeComposerJson([
            'repositories' => [
                'acme/rocket' => ['type' => 'path', 'url' => $this->packagePath('acme', 'rocket')],
                'acme/anvil' => ['type' => 'path', 'url' => $this->packagePath('acme', 'anvil')],
                'other' => ['type' => 'vcs', 'url' => 'https://github.com/other/package'],
            ],
        ]);
    }

    public function test_it_lists_packages_managed_by_packager(): void
    {
        $this->command('packager:list')
            ->expectsTable(['Vendor', 'Package'], [['acme', 'rocket'], ['acme', 'anvil']])
            ->assertExitCode(0);
    }

    public function test_it_lists_nothing_without_repositories(): void
    {
        $this->writeComposerJson(['name' => 'laravel/laravel']);

        $this->command('packager:list')
            ->expectsTable(['Vendor', 'Package'], [])
            ->assertExitCode(0);
    }

    public function test_it_shows_the_git_status_of_packages(): void
    {
        $rocket = $this->packagePath('acme', 'rocket');
        mkdir($rocket.'/.git', 0777, true);

        $this->commandRunner->expectRun(['git', 'fetch'], true, $rocket);
        $this->commandRunner->expectOutput(['git', 'rev-list', 'HEAD..origin', '--count'], '3', $rocket);
        $this->commandRunner->expectOutput(['git', 'rev-parse', '--abbrev-ref', 'HEAD'], 'main', $rocket);

        $this->command('packager:list', ['--git' => true])
            ->expectsTable(
                ['Vendor', 'Package', 'Commits behind', 'Branch'],
                [['acme', 'rocket', 3, 'main'], ['acme', 'anvil', '-', '-']]
            )
            ->assertExitCode(0);
    }

    public function test_it_shows_dashes_when_the_git_status_is_unknown(): void
    {
        $rocket = $this->packagePath('acme', 'rocket');
        mkdir($rocket.'/.git', 0777, true);

        $this->commandRunner->expectRun(['git', 'fetch'], false, $rocket);
        $this->commandRunner->expectOutput(['git', 'rev-list', 'HEAD..origin', '--count'], null, $rocket);
        $this->commandRunner->expectOutput(['git', 'rev-parse', '--abbrev-ref', 'HEAD'], null, $rocket);

        $this->command('packager:list', ['--git' => true])
            ->expectsTable(
                ['Vendor', 'Package', 'Commits behind', 'Branch'],
                [['acme', 'rocket', '-', '-'], ['acme', 'anvil', '-', '-']]
            )
            ->assertExitCode(0);
    }
}
