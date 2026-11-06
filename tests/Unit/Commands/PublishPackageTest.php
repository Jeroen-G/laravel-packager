<?php

declare(strict_types=1);

namespace JeroenG\Packager\Tests\Unit\Commands;

use JeroenG\Packager\Tests\CommandTestCase;

final class PublishPackageTest extends CommandTestCase
{
    private const URL = 'https://github.com/acme/rocket.git';

    private string $path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->path = $this->packagePath('acme', 'rocket');
        mkdir($this->path, 0777, true);
    }

    public function test_it_initialises_git_adds_the_remote_and_pushes(): void
    {
        $this->commandRunner->expectRun(['git', 'init'], true, $this->path);
        $this->commandRunner->expectRun(['git', 'add', '--all'], true, $this->path);
        $this->commandRunner->expectRun(['git', 'commit', '-m', 'Initial commit'], true, $this->path);
        $this->commandRunner->expectOutput(['git', 'remote', 'get-url', 'origin'], null, $this->path);
        $this->commandRunner->expectRun(['git', 'remote', 'add', 'origin', self::URL], true, $this->path);
        $this->commandRunner->expectRun(['git', 'push', '-u', 'origin', 'HEAD'], true, $this->path);

        $this->command('packager:publish', ['vendor' => 'acme', 'name' => 'rocket', 'url' => self::URL])
            ->expectsOutputToContain('Package published successfully!')
            ->assertExitCode(0);
    }

    public function test_it_reuses_an_existing_repository_and_updates_the_remote(): void
    {
        mkdir($this->path.'/.git');

        $this->commandRunner->expectOutput(['git', 'remote', 'get-url', 'origin'], 'https://example.com/old.git', $this->path);
        $this->commandRunner->expectRun(['git', 'remote', 'set-url', 'origin', self::URL], true, $this->path);
        $this->commandRunner->expectRun(['git', 'push', '-u', 'origin', 'HEAD'], true, $this->path);

        $this->command('packager:publish', ['vendor' => 'acme', 'name' => 'rocket', 'url' => self::URL])
            ->assertExitCode(0);
    }

    public function test_it_stops_when_git_cannot_be_initialised(): void
    {
        $this->commandRunner->expectRun(['git', 'init'], true, $this->path);
        $this->commandRunner->expectRun(['git', 'add', '--all'], true, $this->path);
        $this->commandRunner->expectRun(['git', 'commit', '-m', 'Initial commit'], false, $this->path);

        $this->command('packager:publish', ['vendor' => 'acme', 'name' => 'rocket', 'url' => self::URL])
            ->expectsOutputToContain('Unable to initialise a git repository for the package.')
            ->assertExitCode(1);
    }

    public function test_it_stops_when_the_remote_cannot_be_set(): void
    {
        mkdir($this->path.'/.git');

        $this->commandRunner->expectOutput(['git', 'remote', 'get-url', 'origin'], null, $this->path);
        $this->commandRunner->expectRun(['git', 'remote', 'add', 'origin', self::URL], false, $this->path);

        $this->command('packager:publish', ['vendor' => 'acme', 'name' => 'rocket', 'url' => self::URL])
            ->expectsOutputToContain('Unable to set the remote repository.')
            ->assertExitCode(1);
    }

    public function test_it_fails_when_the_push_fails(): void
    {
        mkdir($this->path.'/.git');

        $this->commandRunner->expectOutput(['git', 'remote', 'get-url', 'origin'], self::URL, $this->path);
        $this->commandRunner->expectRun(['git', 'remote', 'set-url', 'origin', self::URL], true, $this->path);
        $this->commandRunner->expectRun(['git', 'push', '-u', 'origin', 'HEAD'], false, $this->path);

        $this->command('packager:publish', ['vendor' => 'acme', 'name' => 'rocket', 'url' => self::URL])
            ->expectsOutputToContain('Unable to push to the remote repository')
            ->doesntExpectOutputToContain('Package published successfully!')
            ->assertExitCode(1);
    }

    public function test_it_fails_when_the_package_does_not_exist(): void
    {
        $this->command('packager:publish', ['vendor' => 'acme', 'name' => 'comet', 'url' => self::URL])
            ->expectsOutputToContain('The package acme/comet does not exist.')
            ->assertExitCode(1);
    }
}
