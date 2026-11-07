<?php

declare(strict_types=1);

namespace JeroenG\Packager\Tests\Unit;

use JeroenG\Packager\CommandRunner;
use JeroenG\Packager\Tests\CommandTestCase;

final class CommandRunnerTest extends CommandTestCase
{
    public function test_it_reports_whether_a_command_succeeded(): void
    {
        $runner = new CommandRunner();

        $this->assertTrue($runner->run([PHP_BINARY, '-r', 'exit(0);']));
        $this->assertFalse($runner->run([PHP_BINARY, '-r', 'exit(1);']));
    }

    public function test_it_returns_the_trimmed_output_of_a_command(): void
    {
        $runner = new CommandRunner();

        $this->assertSame('main', $runner->output([PHP_BINARY, '-r', 'echo "  main\n";']));
        $this->assertNull($runner->output([PHP_BINARY, '-r', 'echo "main"; exit(1);']));
    }

    public function test_it_runs_in_the_base_path_or_the_given_directory(): void
    {
        $runner = new CommandRunner();
        mkdir($this->basePath.'/sub');

        $this->assertSame(realpath($this->basePath), $runner->output([PHP_BINARY, '-r', 'echo getcwd();']));
        $this->assertSame(realpath($this->basePath.'/sub'), $runner->output([PHP_BINARY, '-r', 'echo getcwd();'], $this->basePath.'/sub'));
    }

    public function test_a_command_that_times_out_has_failed(): void
    {
        config(['packager.timeout' => 0.5]);
        $runner = new CommandRunner();

        $this->assertFalse($runner->run([PHP_BINARY, '-r', 'sleep(5);']));
        $this->assertNull($runner->output([PHP_BINARY, '-r', 'sleep(5);']));
    }
}
