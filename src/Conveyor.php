<?php

namespace JeroenG\Packager;

use RuntimeException;

class Conveyor
{
    use FileHandler;

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
     * @param  string $vendor
     * @return $vendor|RuntimeException
     */
    public function vendor($vendor = null)
    {
        if ($vendor != null) {
            return $this->vendor = $vendor;
        }
        if($this->vendor == null) {
            throw new RuntimeException('Please provide a vendor');
        }
        return $this->vendor;
    }

     /**
     * Set or get the package name.
     * @param  string $package
     * @return $package|RuntimeException
     */
    public function package($package = null)
    {
       if ($package != null) {
            return $this->package = $package;
        }
        if($this->package == null) {
            throw new RuntimeException('Please provide a package name');
        }
        return $this->package;
    }

    /**
     * Download the skeleton package.
     * @return void
     */
    public function downloadSkeleton()
    {
        $this->download($zipFile = $this->makeFilename(), 'http://github.com/Jeroen-G/packager-skeleton/archive/master.zip')
             ->extract($zipFile, $this->vendorPath())
             ->cleanUp($zipFile);
        rename($this->vendorPath().'/packager-skeleton-master', $this->packagePath());
    }

    /**
     * Download the package from Github.
     * @param  string $origin The Github URL
     * @param  string $branch The branch to download
     * @return void
     */
    public function downloadFromGithub($origin, $piece, $branch)
    {
        $this->download($zipFile = $this->makeFilename(), $origin)
             ->extract($zipFile, $this->vendorPath())
             ->cleanUp($zipFile);
        rename($this->vendorPath().'/'.$piece.'-'.$branch, $this->packagePath());
    }

    /**
     * Dump Composer's autoloads.
     *
     * @return void
     */
    public function dumpAutoloads()
    {
        shell_exec('composer dump-autoload');
    }

    /**
     * Run Laravel's package:discover command.
     *
     * @return void
     **/
    public function discoverPackage()
    {
        shell_exec('php artisan package:discover');   
    }
}