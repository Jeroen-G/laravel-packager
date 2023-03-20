<?php

declare(strict_types=1);

namespace JeroenG\Packager;

interface CommandRunnerInterface
{
    public function run(array $commandInput): bool;
}
