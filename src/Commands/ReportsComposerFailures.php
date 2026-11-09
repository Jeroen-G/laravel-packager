<?php

declare(strict_types=1);

namespace JeroenG\Packager\Commands;

use RuntimeException;

/**
 * Stop a command with a clear message when a download or composer command failed.
 *
 * @property \JeroenG\Packager\Conveyor $conveyor
 */
trait ReportsComposerFailures
{
    protected function composerFailed(string $message): int
    {
        $this->abortProgress();
        $this->error($message);
        $this->line('Run the composer command yourself to see what went wrong, or increase the timeout in config/packager.php.');

        return self::FAILURE;
    }

    protected function downloadFailed(string $url, RuntimeException $exception): int
    {
        $this->abortProgress();
        $this->error("Unable to download {$url}");
        $this->line(strtok($exception->getMessage(), "\n") ?: $exception::class);

        return self::FAILURE;
    }

    protected function installFailed(string $packagePath): int
    {
        $vendor = $this->conveyor->vendor();
        $package = $this->conveyor->package();

        return $this->composerFailed(
            "Composer could not install the package. Its files are kept in {$packagePath}, "
            ."run `php artisan packager:enable {$vendor} {$package}` after fixing the problem."
        );
    }
}
