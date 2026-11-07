<?php

declare(strict_types=1);

namespace JeroenG\Packager\Tests\Support;

use Illuminate\Testing\PendingCommand;
use LogicException;

/**
 * A typed wrapper around artisan(), which returns an int when console output is not mocked.
 */
trait RunsCommands
{
    /**
     * @param  array<string, mixed>  $parameters
     */
    protected function command(string $command, array $parameters = []): PendingCommand
    {
        $pending = $this->artisan($command, $parameters);

        if (!$pending instanceof PendingCommand) {
            throw new LogicException('Console output must be mocked to test commands.');
        }

        return $pending;
    }
}
