<?php

namespace JeroenG\Packager;

use RuntimeException;

class Conveyor
{
    use FileHandler, ComposerHandler;

    /**
     * Package vendor namespace.
     * @var string
     */
    protected $vendor;

    /**
     * Package name.
     * @var string
     */
    protected $package;

    /**
     * Set or get the package vendor namespace.
     *
     * @param  string  $vendor
     * @return string|RuntimeException
     */
    public function vendor($vendor = null)
    {
        if ($vendor !== null) {
            return $this->vendor = $vendor;
        }
        if ($this->vendor === null) {
            throw new RuntimeException('Please provide a vendor');
        }

        return $this->vendor;
    }

    /**
     * Set or get the package name.
     *
     * @param  string  $package
     * @return string|RuntimeException
     */
    public function package($package = null)
    {
        if ($package !== null) {
            return $this->package = $package;
        }
        if ($this->package === null) {
            throw new RuntimeException('Please provide a package name');
        }

        return $this->package;
    }

    public static function fetchSkeleton(string $source, string $destination): void
    {
        $zipFilePath = tempnam(getcwd(), 'package');
        (new self())->download($zipFilePath, $source)
            ->extract($zipFilePath, $destination)
            ->cleanUp($zipFilePath);
    }

    /**
     * Download the skeleton package.
     *
     * @return void
     */
    public function downloadSkeleton(): void
    {
        $useCached = config('packager.cache_skeleton');
        $cachePath = self::getSkeletonCachePath();
        $cacheExists = $this->pathExists($cachePath);
        if ($useCached && $cacheExists) {
            $this->copyDir($cachePath, $this->vendorPath());
        } else {
            self::fetchSkeleton(config('packager.skeleton'), $this->vendorPath());
        }
        $temporaryPath = $this->vendorPath().'/packager-skeleton-master';
        if ($useCached && ! $cacheExists) {
            $this->copyDir($temporaryPath, $cachePath);
        }
        $this->rename($temporaryPath, $this->packagePath());
    }

    /**
     * Download the package from Github.
     *
     * @param  string  $origin  The Github URL
     * @param $piece
     * @param  string  $branch  The branch to download
     * @return void
     */
    public function downloadFromGithub($origin, $piece, $branch): void
    {
        $this->download($zipFile = $this->makeFilename(), $origin)
            ->extract($zipFile, $this->vendorPath())
            ->cleanUp($zipFile);
        $this->rename($this->vendorPath().'/'.$piece.'-'.$branch, $this->packagePath());
    }

    public function getPackageName(): string
    {
        return $this->vendor.'/'.$this->package;
    }

    public function installPackageFromPath(): void
    {
        $this->addComposerRepository($this->getPackageName(), 'path', $this->packagePath());
        $this->requirePackage($this->getPackageName(), null, false);
    }

    public function installPackageFromVcs($url, $version): void
    {
        $this->addComposerRepository($this->getPackageName(), 'vcs', $url);
        $success = $this->requirePackage($this->getPackageName(), $version);
        if (!$success) {
            $this->removeComposerRepository($this->getPackageName());
            $message = 'No package named '.$this->getPackageName().' with version '.$version.' was found in '.$url;
            throw new RuntimeException($message);
        }
    }

    public function symlinkInstalledPackage(): bool
    {
        $sourcePath = $this->findInstalledPath($this->getPackageName());
        return $this->createSymlink($sourcePath, $this->packagePath());
    }

    public function uninstallPackage(): void
    {
        $this->removePackage($this->getPackageName());
        $this->removeComposerRepository($this->getPackageName());
    }

    public static function getSkeletonCachePath(): string
    {
        return __DIR__.'/../skeleton-cache';
    }
}
