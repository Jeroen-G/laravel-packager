<?php

declare(strict_types=1);

namespace JeroenG\Packager;

use Symfony\Component\Process\Process;

final class CommandRunner implements CommandRunnerInterface
{
    public function run(array $command, ?string $workingDirectory = null): bool
    {
        return $this->execute($command, $workingDirectory)->isSuccessful();
    }

    public function output(array $command, ?string $workingDirectory = null): ?string
    {
        $process = $this->execute($command, $workingDirectory);

        return $process->isSuccessful() ? trim($process->getOutput()) : null;
    }

    private function execute(array $command, ?string $workingDirectory): Process
    {
        $process = new Process($command, $workingDirectory ?? base_path());
        $process->setTimeout((float) config('packager.timeout'));
        $process->run();

        return $process;
    }
}
