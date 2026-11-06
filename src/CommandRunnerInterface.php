<?php

declare(strict_types=1);

namespace JeroenG\Packager;

interface CommandRunnerInterface
{
    /**
     * Run a command and report whether it succeeded.
     *
     * @param  string[]  $command
     * @param  string|null  $workingDirectory  Defaults to the application's base path
     */
    public function run(array $command, ?string $workingDirectory = null): bool;

    /**
     * Run a command and return its trimmed output, or null when it failed.
     *
     * @param  string[]  $command
     * @param  string|null  $workingDirectory  Defaults to the application's base path
     */
    public function output(array $command, ?string $workingDirectory = null): ?string;
}
