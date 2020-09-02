<?php

namespace JeroenG\Packager;

use GuzzleHttp\Client;
use JeroenG\Packager\ArchiveExtractors\Manager;
use RuntimeException;

trait FileHandler
{
    /**
     * Get the path to the packages directory.
     *
     * @return string $path
     */
    public function packagesPath()
    {
        return base_path('packages');
    }

    /**
     * Get the vendor path.
     *
     * @return string $path
     */
    public function vendorPath()
    {
        return $this->packagesPath().'/'.$this->vendor();
    }

    /**
     * Get the path to store a vendor's temporary files.
     *
     * @return string $path
     */
    public function tempPath()
    {
        return $this->vendorPath().'/temp';
    }

    /**
     * Get the full package path.
     *
     * @return string $path
     */
    public function packagePath()
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
    public function makeFilename($extension = 'zip')
    {
        return getcwd().'/package'.md5(time().uniqid()).'.'.$extension;
    }

    /**
     * Check if the package already exists.
     *
     * @return void    Throws error if package exists, aborts process
     */
    public function checkIfPackageExists()
    {
        if (is_dir($this->packagePath())) {
            throw new RuntimeException('Package already exists');
        }
    }

    /**
     * Create a directory if it doesn't exist.
     *
     * @param  string $path Path of the directory to make
     * @return bool
     */
    public function makeDir($path)
    {
        if (! is_dir($path)) {
            return mkdir($path, 0777, true);
        }

        return false;
    }

    /**
     * Remove a directory if it exists.
     *
     * @param  string $path Path of the directory to remove.
     * @return bool
     */
    public function removeDir($path)
    {
        if ($path == 'packages' || $path == '/') {
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
     * @param  string  $filePath
     * @param  string  $sourceFileUrl
     * @return $this
     */
    public function download($filePath, $sourceFileUrl)
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
    public function extract($archiveFilePath, $directory)
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
     * @param  string  $pathToArchive
     * @return $this
     */
    public function cleanUp($pathToArchive)
    {
        @chmod($pathToArchive, 0777);
        @unlink($pathToArchive);

        return $this;
    }

    /**
     * Rename generic files to package-specific ones.
     *
     * @param array|null $manifest
     * @return void
     **/
    public function renameFiles($manifest = null)
    {
        $bindings = [
            [':uc:vendor', ':uc:package', ':lc:vendor', ':lc:package'],
            [$this->vendorStudly(), $this->packageStudly(), strtolower($this->vendor()), strtolower($this->package())],
        ];

        $rewrites = require ($manifest === null) ? [
            'src/MyPackage.php' => 'src/:uc:package.php',
            'config/mypackage.php' => 'config/:lc:package.php',
            'src/Facades/MyPackage.php' => 'src/Facades/:uc:package.php',
            'src/MyPackageServiceProvider.php' => 'src/:uc:packageServiceProvider.php',
        ] : $manifest;

        foreach ($rewrites as $file => $name) {
            $filename = str_replace($bindings[0], $bindings[1], $name);
            rename($this->packagePath().'/'.$file, $this->packagePath().'/'.$filename);
        }
    }

    /**
     * Remove the rules files if present.
     */
    public function cleanUpRules()
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
    protected function getArchiveExtension($archiveFilePath): string
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
