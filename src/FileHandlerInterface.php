<?php

declare(strict_types=1);

namespace JeroenG\Packager;

interface FileHandlerInterface
{
    public function packagesPath(): string;

    public function vendorPath(string $vendor): string;

    /**
     * A new, unique temporary directory path inside the vendor directory on every call.
     */
    public function tempPath(string $vendor): string;

    public function packagePath(string $vendor, string $package): string;

    public function makeFilename(string $extension = 'zip'): string;

    public function checkIfPackageExists(string $vendor, string $package): void;

    /**
     * Whether a file or directory exists at the path.
     */
    public function exists(string $path): bool;

    /**
     * Whether the path is a directory without any entries; false when it is not a directory.
     */
    public function isEmptyDir(string $path): bool;

    /**
     * The names of the directories directly inside the path; empty when it is not a directory.
     *
     * @return list<string>
     */
    public function directories(string $path): array;

    /**
     * The contents of a file, or null when it is not a readable file.
     */
    public function read(string $path): ?string;

    /**
     * Move (rename) a file or directory. Returns whether it succeeded.
     */
    public function move(string $from, string $to): bool;

    public function makeDir(string $path): bool;

    public function removeDir(string $path): bool;

    public function download(string $filePath, string $sourceFileUrl): self;

    public function extract(string $archiveFilePath, string $directory): self;

    public function cleanUp(string $pathToArchive): self;

    public function renameFiles(string $vendorStudly, string $packageStudly, string $vendor, string $package): void;

    public function cleanUpRules(string $vendor, string $package): void;

    public function getArchiveExtension(string $archiveFilePath): string;
}
