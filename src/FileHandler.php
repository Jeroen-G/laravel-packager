<?php

declare(strict_types=1);

namespace JeroenG\Packager;

use GuzzleHttp\Client;
use JeroenG\Packager\ArchiveExtractors\Manager;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

class FileHandler implements FileHandlerInterface
{
    public function packagesPath(): string
    {
        return base_path('packages');
    }

    public function vendorPath(string $vendor): string
    {
        return $this->packagesPath().'/'.$vendor;
    }

    public function tempPath($vendor): string
    {
        return $this->vendorPath($vendor).'/temp';
    }

    public function packagePath($vendor, $package): string
    {
        return $this->vendorPath($vendor).'/'.$package;
    }

    public function makeFilename($extension = 'zip'): string
    {
        return getcwd().'/package'.md5(time().uniqid('', true)).'.'.$extension;
    }

    public function checkIfPackageExists($vendor, $package): void
    {
        if (is_dir($this->packagePath($vendor, $package))) {
            throw new RuntimeException('Package already exists');
        }
    }

    public function makeDir(string $path): bool
    {
        if (! is_dir($path)) {
            return mkdir($path, 0777, true);
        }

        return false;
    }

    public function removeDir(string $path): bool
    {
        if ($path === 'packages' || $path === '/') {
            return false;
        }

        $files = array_diff(scandir($path), ['.', '..']);
        foreach ($files as $file) {
            if (is_dir("$path/$file")) {
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
        $client = new Client(['verify' => config('packager.curl_verify_cert')]);
        $response = $client->get($sourceFileUrl);
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
            if (! $file->isFile()) {
                continue;
            }
            $replaced = str_replace($bindings[0], $bindings[1], $file->getFilename());
            if ($replaced === $file->getFilename()) {
                continue;
            }
            rename($file->getPath().'/'.$file->getFilename(), $file->getPath().'/'.$replaced);
        }
    }

    public function cleanUpRules($vendor, $package): void
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
        $extension = $pathParts['extension'];

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
