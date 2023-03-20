<?php

declare(strict_types=1);

namespace JeroenG\Packager;

use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

final class Conveyor
{
    protected string $vendor;

    protected string $package;

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
        $skeletonArchiveUrl = $skeletonArchiveUrl ?? config('packager.skeleton');
        $extension = $this->fileHandler->getArchiveExtension($skeletonArchiveUrl);

        $this->fileHandler->download($archive = $this->fileHandler->makeFilename($extension), $skeletonArchiveUrl)
            ->extract($archive, $this->fileHandler->tempPath($this->vendor()))
            ->cleanUp($archive);

        $firstInDirectory = scandir($this->fileHandler->tempPath($this->vendor()))[2];
        $extractedSkeletonLocation = $this->fileHandler->tempPath($this->vendor()).'/'.$firstInDirectory;
        rename($extractedSkeletonLocation, $this->fileHandler->packagePath($this->vendor(), $this->package()));

        if (is_dir($this->fileHandler->tempPath($this->vendor()))) {
            rmdir($this->fileHandler->tempPath($this->vendor()));
        }
    }

    public function downloadFromGithub(string $origin, string $piece, string $branch): void
    {
        $this->fileHandler->download($zipFile = $this->fileHandler->makeFilename(), $origin)
            ->extract($zipFile, $this->fileHandler->vendorPath($this->vendor()))
            ->cleanUp($zipFile);

        rename($this->fileHandler->vendorPath($this->vendor()).'/'.$piece.'-'.$branch, $this->fileHandler->packagePath($this->vendor(), $this->package()));
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

    protected function runProcess(array $command): bool
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
            'repositories.' . Str::slug($this->vendor) . '/' . Str::slug($this->package),
        ]);
    }

    private function requirePackage(): void
    {
        $this->runProcess([
            'composer',
            'require',
            $this->vendor . '/' . $this->package . ':@dev',
        ]);
    }

    private function removePackage(): void
    {
        $this->runProcess([
            'composer',
            'remove',
            $this->vendor . '/' . $this->package,
        ]);
    }
}
