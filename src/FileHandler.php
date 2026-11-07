<?php

declare(strict_types=1);

namespace JeroenG\Packager;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use JeroenG\Packager\ArchiveExtractors\Manager;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

class FileHandler implements FileHandlerInterface
{
    private ClientInterface $client;

    public function __construct(?ClientInterface $client = null)
    {
        $this->client = $client ?? new Client();
    }

    public function packagesPath(): string
    {
        return base_path('packages');
    }

    public function vendorPath(string $vendor): string
    {
        return $this->packagesPath().DIRECTORY_SEPARATOR.$vendor;
    }

    public function tempPath(string $vendor): string
    {
        // Hidden and unique, so it never clashes with a package or a previous run.
        return $this->vendorPath($vendor).DIRECTORY_SEPARATOR.'.packager-temp-'.bin2hex(random_bytes(8));
    }

    public function packagePath(string $vendor, string $package): string
    {
        return $this->vendorPath($vendor).DIRECTORY_SEPARATOR.$package;
    }

    public function makeFilename(string $extension = 'zip'): string
    {
        return getcwd().'/package'.md5(time().uniqid('', true)).'.'.$extension;
    }

    public function checkIfPackageExists(string $vendor, string $package): void
    {
        if (is_dir($this->packagePath($vendor, $package))) {
            throw new RuntimeException('Package already exists');
        }
    }

    public function exists(string $path): bool
    {
        return file_exists($path);
    }

    public function isEmptyDir(string $path): bool
    {
        $entries = is_dir($path) ? scandir($path) : false;

        return $entries !== false && array_diff($entries, ['.', '..']) === [];
    }

    public function directories(string $path): array
    {
        $entries = is_dir($path) ? scandir($path) : false;

        if ($entries === false) {
            return [];
        }

        return array_values(array_filter(
            array_diff($entries, ['.', '..']),
            static fn (string $entry): bool => is_dir($path.DIRECTORY_SEPARATOR.$entry),
        ));
    }

    public function read(string $path): ?string
    {
        $contents = is_file($path) ? file_get_contents($path) : false;

        return $contents === false ? null : $contents;
    }

    public function move(string $from, string $to): bool
    {
        return rename($from, $to);
    }

    public function makeDir(string $path): bool
    {
        if (!is_dir($path)) {
            return mkdir($path, 0777, true);
        }

        return false;
    }

    /**
     * Recursively remove a directory, but only one inside the packages directory.
     */
    public function removeDir(string $path): bool
    {
        $realPath = realpath($path);
        $packagesPath = realpath($this->packagesPath());

        if (
            !is_dir($path)
            || $realPath === false
            || $packagesPath === false
            || !str_starts_with($realPath, $packagesPath.DIRECTORY_SEPARATOR)
        ) {
            return false;
        }

        $files = array_diff(scandir($path) ?: [], ['.', '..']);
        foreach ($files as $file) {
            // Never follow symlinks: remove the link itself, not what it points to.
            if (is_dir("$path/$file") && !is_link("$path/$file")) {
                $this->removeDir("$path/$file");
            } else {
                @chmod("$path/$file", 0777);
                @unlink("$path/$file");
            }
        }

        return rmdir($path);
    }

    public function download(string $filePath, string $sourceFileUrl): self
    {
        $response = $this->client->request('GET', $sourceFileUrl, [
            'verify' => config('packager.curl_verify_cert'),
        ]);
        file_put_contents($filePath, $response->getBody());

        return $this;
    }

    public function extract(string $archiveFilePath, string $directory): self
    {
        $extension = $this->getArchiveExtension($archiveFilePath);
        $extractorManager = new Manager();
        $extractor = $extractorManager->getExtractor($extension);
        $extractor->extract($archiveFilePath, $directory);

        return $this;
    }

    public function cleanUp(string $pathToArchive): self
    {
        @chmod($pathToArchive, 0777);
        @unlink($pathToArchive);

        return $this;
    }

    public function renameFiles(string $vendorStudly, string $packageStudly, string $vendor, string $package): void
    {
        $bindings = [
            ['MyVendor', 'MyPackage', 'myvendor', 'mypackage'],
            [$vendorStudly, $packageStudly, mb_strtolower($vendor), mb_strtolower($package)],
        ];

        $files = new RecursiveDirectoryIterator($this->packagePath($vendor, $package));
        foreach (new RecursiveIteratorIterator($files) as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $replaced = str_replace($bindings[0], $bindings[1], $file->getFilename());
            if ($replaced === $file->getFilename()) {
                continue;
            }
            rename($file->getPath().'/'.$file->getFilename(), $file->getPath().'/'.$replaced);
        }
    }

    public function cleanUpRules(string $vendor, string $package): void
    {
        $ruleFiles = ['rules.php', 'rewriteRules.php'];

        foreach ($ruleFiles as $file) {
            if (file_exists($this->packagePath($vendor, $package).'/'.$file)) {
                unlink($this->packagePath($vendor, $package).'/'.$file);
            }
        }
    }

    public function getArchiveExtension(string $archiveFilePath): string
    {
        $pathParts = pathinfo($archiveFilePath);
        $extension = $pathParts['extension'] ?? '';

        // Here we check if it actually is supposed to be .tar.gz/.tar.xz
        if (in_array($extension, ['gz', 'xz'], true)) {
            $subExtension = pathinfo($pathParts['filename'], PATHINFO_EXTENSION);

            if ($subExtension) {
                $extension = implode('.', [
                    $subExtension,
                    $extension,
                ]);
            }
        }

        return $extension;
    }
}
