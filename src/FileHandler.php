<?php

namespace JeroenG\Packager;

use GuzzleHttp\Client;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Log;
use JeroenG\Packager\ArchiveExtractors\Manager;
use JeroenG\Packager\ArchiveExtractors\Zip;
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
     * Get the local temp dir path.
     *
     * @return string $path
     */
    public function tempPath()
    {
        $path = $this->packagesPath().'/temp';

        // Ensure that temp dir exists
        if (! is_dir($path)) {
            mkdir($path);
        }

        return $path;
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
     * Extract the zip file into the given directory.
     *
     * @param string $archiveFilePath
     * @param string $directory
     * @return $this
     */
    public function extract($archiveFilePath, $directory)
    {
        $extension = $this->getArchiveExtension($archiveFilePath);

        try {
            /** @var Manager $extractorManager */
            $extractorManager = app()->make(Manager::class);
            $extractor = $extractorManager->getExtractor($extension);
        } catch (BindingResolutionException $e) {
            Log::error('Can not get extractor manager. Falling back with using zip extractor');

            $extractor = new Zip();
        }

        $extractor->extract($archiveFilePath, $directory);

        return $this;
    }

    /**
     * Clean-up the archive file.
     *
     * @param  string  $pathToArchiveFile
     * @return $this
     */
    public function cleanUp($pathToArchiveFile)
    {
        @chmod($pathToArchiveFile, 0777);
        @unlink($pathToArchiveFile);

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
            [$this->vendor(), $this->package(), strtolower($this->vendor()), strtolower($this->package())],
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
     * @param string $archiveFilePath
     *
     * @return string
     */
    protected function getArchiveExtension($archiveFilePath)
    {
        $pathParts = pathinfo($archiveFilePath);
        $extension = $pathParts['extension'];

        // Hack for complex file extensions
        if (in_array($extension, ['gz', 'xz'])) {
            // Check child extension
            $childExtension = pathinfo($pathParts['filename'], PATHINFO_EXTENSION);

            if ($childExtension) {
                $extension = implode('.', [
                    $childExtension,
                    $extension,
                ]);
            }
        }

        return $extension;
    }
}
