<?php

declare(strict_types=1);

namespace JeroenG\Packager\Tests\Support;

use JeroenG\Packager\CommandRunnerInterface;
use Mockery;
use Mockery\ExpectationInterface;
use Mockery\MockInterface;

final class CommandRunnerExpectation
{
    private CommandRunnerInterface|MockInterface $mock;

    private function __construct()
    {
        $this->mock = Mockery::mock(CommandRunnerInterface::class);
    }

    public static function create(): self
    {
        return new CommandRunnerExpectation();
    }

    public function getMock(): CommandRunnerInterface|MockInterface
    {
        return $this->mock;
    }

    public function expectRun(array $commandInput, bool $success = true, ?string $workingDirectory = null): ExpectationInterface
    {
        $arguments = $workingDirectory === null ? [$commandInput] : [$commandInput, $workingDirectory];

        return $this->mock
            ->expects('run')
            ->with(...$arguments)
            ->andReturn($success);
    }

    public function expectOutput(array $commandInput, ?string $output, string $workingDirectory): ExpectationInterface
    {
        return $this->mock
            ->expects('output')
            ->with($commandInput, $workingDirectory)
            ->andReturn($output);
    }
}
