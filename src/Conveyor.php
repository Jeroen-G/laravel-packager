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
     * Download the skeleton package.
     */
    public function downloadSkeleton()
    {
        $archiveUrl = config('packager.skeleton');
        $extension = $this->getArchiveExtension($archiveUrl);

        $tempDir = $this->tempPath().'/'.uniqid();

        $this->download($archive = $this->makeFilename($extension), $archiveUrl)
            ->extract($archive, $tempDir)
            ->cleanUp($archive);

        // Before move files to vendor/package folder, ensure that we have non-wrapped skeleton.
        // There are two options:
        // 1. Many files in archive root
        // 2. Single folder with files in archive root
        $tempDirFilesList = scandir($tempDir);

        $directoryToMove = $tempDir;

        // 3 because '.', '..', and one file or directory
        if (count($tempDirFilesList) === 3) {
            $maybeRealSkeletonDir = $tempDir.'/'.$tempDirFilesList[2];

            if (is_dir($maybeRealSkeletonDir)) {
                $directoryToMove = $maybeRealSkeletonDir;
            }
        }

        rename($directoryToMove, $this->packagePath());

        // Delete temp dir if exists
        if (is_dir($tempDir)) {
            rmdir($tempDir);
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
            $this->vendor.'/'.$this->package,
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
        $process->run();

        return $process->getExitCode() === 0;
    }
}
