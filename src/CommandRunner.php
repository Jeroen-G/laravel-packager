<?php

declare(strict_types=1);

namespace JeroenG\Packager;

use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

final class CommandRunner implements CommandRunnerInterface
{
    public function run(array $command, ?string $workingDirectory = null): bool
    {
        return $this->execute($command, $workingDirectory) !== null;
    }

    public function output(array $command, ?string $workingDirectory = null): ?string
    {
        $process = $this->execute($command, $workingDirectory);

        return $process === null ? null : trim($process->getOutput());
    }

    /**
     * Run the command and return the process when it succeeded, or null when it failed or timed out.
     *
     * @param  list<string>  $command
     */
    private function execute(array $command, ?string $workingDirectory): ?Process
    {
        $process = new Process($command, $workingDirectory ?? base_path());
        $process->setTimeout((float) config('packager.timeout'));

        try {
            $process->run();
        } catch (ProcessTimedOutException) {
            return null;
        }

        return $process->isSuccessful() ? $process : null;
    }
}
