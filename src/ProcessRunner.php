<?php

namespace JeroenG\Packager;

trait ProcessRunner
{

    /**
     * @param  array  $command
     * @param  string|null  $cwd
     * @return array
     */
    protected static function runProcess(array $command, string $cwd = null): array
    {
        if ($cwd === null){
            $cwd = base_path();
        }
        $process = new \Symfony\Component\Process\Process($command, $cwd);
        $process->setTimeout(null);
        $output = '';
        $process->run(static function ($type, $buffer) use (&$output) {
            $output .= $buffer;
        });
        $success = $process->getExitCode() === 0;
        return compact('success', 'output');
    }
}
