<?php

declare(strict_types=1);

namespace JeroenG\Packager;

interface FileHandlerInterface
{
    public function packagesPath(): string;

    public function vendorPath(string $vendor): string;

    public function tempPath(string $vendor): string;

    public function packagePath(string $vendor, string $package): string;

    public function makeFilename(string $extension = 'zip'): string;

    public function checkIfPackageExists(string $vendor, string $package): void;

    public function makeDir(string $path): bool;

    public function removeDir(string $path): bool;

    public function download(string $filePath, string $sourceFileUrl): self;

    public function extract(string $archiveFilePath, string $directory): self;

    public function cleanUp(string $pathToArchive): self;

    public function renameFiles(string $vendorStudly, string $packageStudly, string $vendor, string $package): void;

    public function cleanUpRules(string $vendor, string $package): void;

    public function getArchiveExtension(string $archiveFilePath): string;
}
