<?php

declare(strict_types=1);

namespace JeroenG\Packager;

interface CommandRunnerInterface
{
    /**
     * @param  list<string>  $command
     * @param  string|null  $workingDirectory  Defaults to the application's base path
     */
    public function run(array $command, ?string $workingDirectory = null): bool;

    /**
     * @param  list<string>  $command
     * @param  string|null  $workingDirectory  Defaults to the application's base path
     */
    public function output(array $command, ?string $workingDirectory = null): ?string;
}
