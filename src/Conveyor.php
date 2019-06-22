<?php

namespace JeroenG\Packager;

use RuntimeException;
use Illuminate\Support\Facades\File;

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

    public static function fetchSkeleton(string $source, string $destination)
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
    public function downloadSkeleton()
    {
        $useCached = config('packager.cache_skeleton');
        $cachePath = self::getSkeletonCachePath();
        $cacheExists = File::exists($cachePath);
        if ($useCached && $cacheExists) {
            File::copyDirectory($cachePath, $this->vendorPath());
        } else {
            self::fetchSkeleton(config('packager.skeleton'), $this->vendorPath());
        }
        $temporaryPath = $this->vendorPath().'/packager-skeleton-master';
        if ($useCached && ! $cacheExists) {
            File::copyDirectory($temporaryPath, $cachePath);
        }
        rename($temporaryPath, $this->packagePath());
    }

    /**
     * Download the package from Github.
     *
     * @param  string  $origin  The Github URL
     * @param  string  $branch  The branch to download
     * @return void
     */
    public function downloadFromGithub($origin, $piece, $branch)
    {
        $this->download($zipFile = $this->makeFilename(), $origin)
            ->extract($zipFile, $this->vendorPath())
            ->cleanUp($zipFile);
        rename($this->vendorPath().'/'.$piece.'-'.$branch, $this->packagePath());
    }

    public function getPackageName()
    {
        return $this->vendor.'/'.$this->package;
    }

    public function installPackageFromPath()
    {
        $this->disablePackagistRepo();
        $this->addComposerRepository();
        $this->requirePackage(null, false);
        $this->enablePackagistRepo();
    }

    public function installPackageFromVcs($url, $version)
    {
        $this->disablePackagistRepo();
        $this->addComposerRepository('vcs', $url);
        $success = $this->requirePackage($version);
        $this->enablePackagistRepo();
        if (! $success) {
            $this->removeComposerRepository();
            $message = 'No package named '.$this->getPackageName().' with version '.$version.' was found in '.$url;
            throw new RuntimeException($message);
        }
    }

    public function createSymlinks()
    {
        // Find installed path
        $result = $this->runProcess(['composer', 'info', $this->getPackageName(), '--path']);
        if (preg_match('{'.$this->getPackageName().' (.*)$}m', $result['output'], $match)) {
            $path = $match[1];
            symlink($path, $this->packagePath());
        }
    }

    public function uninstallPackage()
    {
        $this->removePackage();
        $this->removeComposerRepository();
    }

    protected function addComposerRepository(string $type = 'path', string $url = null)
    {
        $command = [
            'composer',
            'config',
            'repositories.'.$this->getPackageName(),
            $type,
            $url ?: $this->packagePath(),
        ];

        return $this->runProcess($command);
    }

    protected function removeComposerRepository()
    {
        return $this->runProcess([
            'composer',
            'config',
            '--unset',
            'repositories.'.$this->getPackageName(),
        ]);
    }

    protected function requirePackage(string $version = null, bool $prefer_source = true)
    {
        $package = sprintf('%s:%s', strtolower($this->getPackageName()), $version ?? '@dev');
        $result = $this->runProcess([
            'composer',
            'require',
            $package,
            '--prefer-'.($prefer_source ? 'source' : 'dist'),
        ]);
        if (! $result['success']) {
            if (preg_match('/Could not find a matching version of package/', $result['output'])) {
                return false;
            }
        }

        return true;
    }

    protected function removePackage()
    {
        return $this->runProcess([
            'composer',
            'remove',
            strtolower($this->getPackageName()),
        ]);
    }

    /**
     * @param  array  $command
     * @return array
     */
    protected function runProcess(array $command)
    {
        $process = new \Symfony\Component\Process\Process($command, base_path());
        $output = '';
        $process->run(function ($type, $buffer) use (&$output) {
            $output .= $buffer;
        });
        $success = $process->getExitCode() === 0;
        return compact('success', 'output');
    }

    protected function disablePackagistRepo()
    {
        $result = $this->runProcess([
            'composer',
            'config',
            'repo.packagist',
            'false',
        ]);

        return $result['success'];
    }

    private function enablePackagistRepo()
    {
        $result = $this->runProcess([
            'composer',
            'config',
            'repo.packagist',
            'true',
        ]);

        return $result['success'];
    }

    public static function getSkeletonCachePath(): string
    {
        if (defined('PHPUNIT_COMPOSER_INSTALL')) {
            // Running PhpUnit
            return __DIR__.'/../testbench/skeleton-cache';
        }

        return storage_path('app/laravel-packager/cache');
    }
}
