<?php

namespace JeroenG\Packager;

use GuzzleHttp\Client;
use JeroenG\Packager\ArchiveExtractors\Manager;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

trait FileHandler
{
    /**
     * Get the path to the packages directory.
     *
     * @return string $path
     */
    public function packagesPath(): string
    {
        return base_path('packages');
    }

    /**
     * Get the vendor path.
     *
     * @return string $path
     */
    public function vendorPath(): string
    {
        return $this->packagesPath().'/'.$this->vendor();
    }

    /**
     * Get the path to store a vendor's temporary files.
     *
     * @return string $path
     */
    public function tempPath(): string
    {
        return $this->vendorPath().'/temp';
    }

    /**
     * Get the full package path.
     *
     * @return string $path
     */
    public function packagePath(): string
    {
        return $this->vendorPath().'/'.$this->package();
    }

    /**
     * Generate a random temporary filename for the package archive file.
     *
     * @param string $extension
     *
     * @return string
     */
    public function makeFilename($extension = 'zip'): string
    {
        return getcwd().'/package'.md5(time().uniqid('', true)).'.'.$extension;
    }

    /**
     * Check if the package already exists.
     *
     * @return void    Throws error if package exists, aborts process
     */
    public function checkIfPackageExists(): void
    {
        if (is_dir($this->packagePath())) {
            throw new RuntimeException('Package already exists');
        }
    }

    /**
     * Create a directory if it doesn't exist.
     *
     * @param string $path Path of the directory to make
     * @return bool
     */
    public function makeDir(string $path): bool
    {
        if (! is_dir($path)) {
            return mkdir($path, 0777, true);
        }

        return false;
    }

    /**
     * Remove a directory if it exists.
     *
     * @param string $path Path of the directory to remove.
     * @return bool
     */
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

    /**
     * Download the archive to the given file by url.
     *
     * @param string $filePath
     * @param string $sourceFileUrl
     * @return $this
     */
    public function download(string $filePath, string $sourceFileUrl): self
    {
        $client = new Client(['verify' => config('packager.curl_verify_cert')]);
        $response = $client->get($sourceFileUrl);
        file_put_contents($filePath, $response->getBody());

        return $this;
    }

    /**
     * Extract the downloaded archive into the given directory.
     *
     * @param string $archiveFilePath
     * @param string $directory
     * @return $this
     */
    public function extract(string $archiveFilePath, string $directory): self
    {
        $extension = $this->getArchiveExtension($archiveFilePath);
        $extractorManager = new Manager();
        $extractor = $extractorManager->getExtractor($extension);
        $extractor->extract($archiveFilePath, $directory);

        return $this;
    }

    /**
     * Remove the archive.
     *
     * @param string $pathToArchive
     * @return $this
     */
    public function cleanUp(string $pathToArchive): self
    {
        @chmod($pathToArchive, 0777);
        @unlink($pathToArchive);

        return $this;
    }

    /**
     * Rename generic files to package-specific ones.
     *
     * @return void
     */
    public function renameFiles(): void
    {
        $bindings = [
            ['MyVendor', 'MyPackage', 'myvendor', 'mypackage'],
            [$this->vendorStudly(), $this->packageStudly(), strtolower($this->vendor()), strtolower($this->package())],
        ];

        $files = new RecursiveDirectoryIterator($this->packagePath());
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

    /**
     * Remove the rules files if present.
     */
    public function cleanUpRules(): void
    {
        $ruleFiles = ['rules.php', 'rewriteRules.php'];

        foreach ($ruleFiles as $file) {
            if (file_exists($this->packagePath().'/'.$file)) {
                unlink($this->packagePath().'/'.$file);
            }
        }
    }

    /**
     * Based on the extension a different archive extractor is used.
     *
     * @param string $archiveFilePath
     *
     * @return string
     */
    protected function getArchiveExtension(string $archiveFilePath): string
    {
        $pathParts = pathinfo($archiveFilePath);
        $extension = $pathParts['extension'];

        // Here we check if it actually is supposed to be .tar.gz/.tar.xz
        if (in_array($extension, ['gz', 'xz'])) {
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
