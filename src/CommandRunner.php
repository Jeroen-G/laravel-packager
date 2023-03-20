<?php

declare(strict_types=1);

namespace JeroenG\Packager;

use Symfony\Component\Process\Process;

final class CommandRunner implements CommandRunnerInterface
{
    public function run(array $commandInput): bool
    {
        $process = new Process($commandInput, base_path());
        $process->setTimeout((float) config('packager.timeout'));
        $process->run();

        return $process->getExitCode() === 0;
    }
}
