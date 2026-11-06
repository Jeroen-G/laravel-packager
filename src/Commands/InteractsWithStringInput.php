<?php

declare(strict_types=1);

namespace JeroenG\Packager\Commands;

use InvalidArgumentException;

/**
 * Typed access to command input, which Symfony Console returns as mixed.
 */
trait InteractsWithStringInput
{
    protected function stringArgument(string $key): ?string
    {
        $value = $this->argument($key);

        return is_string($value) ? $value : null;
    }

    protected function requiredArgument(string $key): string
    {
        return $this->stringArgument($key) ?? throw new InvalidArgumentException("The {$key} argument is required.");
    }

    protected function stringOption(string $key): ?string
    {
        $value = $this->option($key);

        return is_string($value) ? $value : null;
    }
}
