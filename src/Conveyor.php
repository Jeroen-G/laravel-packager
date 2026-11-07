<?php

declare(strict_types=1);

namespace JeroenG\Packager;

use Illuminate\Support\Str;
use RuntimeException;

final class Conveyor
{
    private string $vendor;

    private string $package;

    private CommandRunnerInterface $commandRunner;

    private FileHandlerInterface $fileHandler;

    public function __construct(
        CommandRunnerInterface $commandRunner,
        FileHandlerInterface $fileHandler,
    ) {
        $this->commandRunner = $commandRunner;
        $this->fileHandler = $fileHandler;
    }

    public function vendor(?string $vendor = null): string
    {
        if ($vendor !== null) {
            return $this->vendor = $vendor;
        }

        if (!isset($this->vendor)) {
            throw new RuntimeException('Please provide a vendor name');
        }

        return $this->vendor;
    }

    public function vendorStudly(): string
    {
        return Str::studly($this->vendor());
    }

    public function vendorKebab(): string
    {
        return Str::kebab($this->vendor());
    }

    public function package(?string $package = null): string
    {
        if ($package !== null) {
            return $this->package = $package;
        }

        if (!isset($this->package)) {
            throw new RuntimeException('Please provide a package name');
        }

        return $this->package;
    }

    public function packageStudly(): string
    {
        return Str::studly($this->package());
    }

    public function packageKebab(): string
    {
        return Str::kebab($this->package());
    }

    public function downloadSkeleton(?string $skeletonArchiveUrl = null): void
    {
        $this->downloadArchive($skeletonArchiveUrl ?? config('packager.skeleton'));
    }

    public function downloadFromGithub(string $archiveUrl): void
    {
        $this->downloadArchive($archiveUrl);
    }

    public function installPackage(): void
    {
        $this->addPathRepository();
        $this->requirePackage();
    }

    public function uninstallPackage(): void
    {
        $this->removePackage();
        $this->removePathRepository();
    }

    /**
     * Download and extract an archive, and move the directory it contains to the package path.
     */
    private function downloadArchive(string $archiveUrl): void
    {
        $tempPath = $this->fileHandler->tempPath($this->vendor());
        $archive = $this->fileHandler->makeFilename($this->fileHandler->getArchiveExtension($archiveUrl));

        // Remove leftovers of an interrupted run, which would otherwise end up in this package.
        $this->fileHandler->removeDir($tempPath);

        $this->fileHandler->download($archive, $archiveUrl)
            ->extract($archive, $tempPath)
            ->cleanUp($archive);

        // Archives from GitHub contain a single directory (tarballs may also hold a pax_global_header file).
        $extracted = array_values(array_filter(
            array_diff(scandir($tempPath) ?: [], ['.', '..']),
            static fn (string $entry): bool => is_dir($tempPath.DIRECTORY_SEPARATOR.$entry),
        ));

        if (count($extracted) !== 1) {
            $this->fileHandler->removeDir($tempPath);

            throw new RuntimeException('The archive should contain exactly one directory');
        }

        rename($tempPath.DIRECTORY_SEPARATOR.$extracted[0], $this->fileHandler->packagePath($this->vendor(), $this->package()));
        $this->fileHandler->removeDir($tempPath);
    }

    /**
     * @param  list<string>  $command
     */
    private function runProcess(array $command): bool
    {
        return $this->commandRunner->run($command);
    }

    private function addPathRepository(): void
    {
        $params = json_encode([
            'type' => 'path',
            'url' => $this->fileHandler->packagePath($this->vendor(), $this->package()),
            'options' => [
                'symlink' => true,
            ],
        ], JSON_THROW_ON_ERROR);
        $command = [
            'composer',
            'config',
            'repositories.'.Str::slug($this->vendor).'/'.Str::slug($this->package),
            $params,
            '--file',
            'composer.json',
        ];

        $this->runProcess($command);
    }

    private function removePathRepository(): void
    {
        $this->runProcess([
            'composer',
            'config',
            '--unset',
            'repositories.'.Str::slug($this->vendor).'/'.Str::slug($this->package),
        ]);
    }

    private function requirePackage(): void
    {
        $this->runProcess([
            'composer',
            'require',
            $this->vendor.'/'.$this->package.':@dev',
        ]);
    }

    private function removePackage(): void
    {
        $this->runProcess([
            'composer',
            'remove',
            $this->vendor.'/'.$this->package,
        ]);
    }
}
