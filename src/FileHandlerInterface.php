<?php

declare(strict_types=1);

namespace JeroenG\Packager;

interface FileHandlerInterface
{
    public function packagesPath(): string;

    public function vendorPath(string $vendor): string;

    public function tempPath($vendor): string;

    public function packagePath($vendor, $package): string;

    public function makeFilename($extension = 'zip'): string;

    public function checkIfPackageExists($vendor, $package): void;

    public function makeDir(string $path): bool;

    public function removeDir(string $path): bool;

    public function download(string $filePath, string $sourceFileUrl): self;

    public function extract(string $archiveFilePath, string $directory): self;

    public function cleanUp(string $pathToArchive): self;

    public function renameFiles(string $vendorStudly, string $packageStudly, string $vendor, string $package): void;

    public function cleanUpRules($vendor, $package): void;

    public function getArchiveExtension(string $archiveFilePath): string;
}
