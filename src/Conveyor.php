<?php

namespace JeroenG\Packager;

use Illuminate\Support\Str;
use RuntimeException;

class Conveyor
{
    use FileHandler;

    /**
     * Package vendor namespace.
     *
     * @var string
     */
    protected $vendor;

    /**
     * Package name.
     *
     * @var string
     */
    protected $package;

    /**
     * Set or get the package vendor namespace.
     *
     * @param string $vendor
     *
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
     * Get the vendor name converted to StudlyCase.
     *
     * @return string|RuntimeException
     */
    public function vendorStudly()
    {
        return Str::studly($this->vendor());
    }

    /**
     * Get the vendor name converted to kebab-case.
     *
     * @return string|RuntimeException
     */
    public function vendorKebab()
    {
        return Str::kebab($this->vendor());
    }

    /**
     * Set or get the package name.
     *
     * @param string $package
     *
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

    /**
     * Get the package name converted to StudlyCase.
     *
     * @return string|RuntimeException
     */
    public function packageStudly()
    {
        return Str::studly($this->package());
    }

    /**
     * Get the package name converted to kebab-case.
     *
     * @return string|RuntimeException
     */
    public function packageKebab()
    {
        return Str::kebab($this->package());
    }

    /**
     * Download the skeleton package.
     */
    public function downloadSkeleton($skeletonArchiveUrl = null)
    {
        $skeletonArchiveUrl = $skeletonArchiveUrl ?? config('packager.skeleton');
        $extension = $this->getArchiveExtension($skeletonArchiveUrl);

        $this->download($archive = $this->makeFilename($extension), $skeletonArchiveUrl)
            ->extract($archive, $this->tempPath())
            ->cleanUp($archive);

        $firstInDirectory = scandir($this->tempPath())[2];
        $extractedSkeletonLocation = $this->tempPath().'/'.$firstInDirectory;
        rename($extractedSkeletonLocation, $this->packagePath());

        if (is_dir($this->tempPath())) {
            rmdir($this->tempPath());
        }
    }

    /**
     * Download the package from Github.
     *
     * @param string $origin The Github URL
     * @param string $branch The branch to download
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
     */
    public function dumpAutoloads()
    {
        shell_exec('composer dump-autoload');
    }

    public function installPackage()
    {
        $this->addPathRepository();
        $this->requirePackage();
    }

    public function uninstallPackage()
    {
        $this->removePackage();
        $this->removePathRepository();
    }

    public function addPathRepository()
    {
        $params = json_encode([
            'type' => 'path',
            'url' => $this->packagePath(),
            'options' => [
                'symlink' => true,
            ],
        ]);
        $command = [
            'composer',
            'config',
            'repositories.'.Str::slug($this->vendor).'/'.Str::slug($this->package),
            $params,
            '--file',
            'composer.json',
        ];

        return $this->runProcess($command);
    }

    public function removePathRepository()
    {
        return $this->runProcess([
            'composer',
            'config',
            '--unset',
            'repositories.'.Str::slug($this->vendor).'/'.Str::slug($this->package),
        ]);
    }

    public function requirePackage()
    {
        return $this->runProcess([
            'composer',
            'require',
            $this->vendor.'/'.$this->package.':@dev',
        ]);
    }

    public function removePackage()
    {
        return $this->runProcess([
            'composer',
            'remove',
            $this->vendor.'/'.$this->package,
        ]);
    }

    /**
     * @return bool
     */
    protected function runProcess(array $command)
    {
        $process = new \Symfony\Component\Process\Process($command, base_path());
        $process->setTimeout(config('packager.timeout'));
        $process->run();

        return $process->getExitCode() === 0;
    }
}
