<?php

declare(strict_types=1);

namespace JeroenG\Packager\Commands;

use Illuminate\Console\Command;
use JeroenG\Packager\Conveyor;
use JeroenG\Packager\FileHandlerInterface;
use JeroenG\Packager\ProgressBar;

/**
 * remove an existing package.
 *
 * @author JeroenG
 **/
class RemovePackage extends Command
{
    use InteractsWithStringInput;
    use ProgressBar;
    use ReportsComposerFailures;

    protected $signature = 'packager:remove {vendor} {name?}';

    protected $description = 'Remove an existing package.';

    /**
     * Packages roll off of the conveyor.
     */
    protected Conveyor $conveyor;

    protected FileHandlerInterface $fileHandler;

    public function __construct(Conveyor $conveyor, FileHandlerInterface $fileHandler)
    {
        parent::__construct();
        $this->conveyor = $conveyor;
        $this->fileHandler = $fileHandler;
    }

    public function handle(): int
    {
        $vendor = $this->requiredArgument('vendor');
        $name = $this->stringArgument('name');

        if (mb_strpos($vendor, '/') !== false) {
            [$vendor, $name] = explode('/', $vendor, 2);
        }

        if ($name === null || $name === '') {
            $this->error('Please provide a package name, e.g. packager:remove my-vendor my-package');

            return self::FAILURE;
        }

        // Start the progress bar
        $this->startProgressBar(4);

        $this->conveyor->vendor($vendor);
        $this->conveyor->package($name);

        // Start removing the package
        $this->info('Removing package '.$this->conveyor->vendor().'\\'.$this->conveyor->package().'...');
        $this->makeProgress();

        // Uninstall the package
        $this->info('Uninstalling package...');
        if (!$this->conveyor->uninstallPackage()) {
            return $this->composerFailed('Composer could not remove the package from composer.json, so its files were kept.');
        }
        $this->makeProgress();

        // remove the package directory
        $this->info('Removing packages directory...');
        $packagePath = $this->fileHandler->packagePath($this->conveyor->vendor(), $this->conveyor->package());
        if (!$this->fileHandler->removeDir($packagePath) && $this->fileHandler->exists($packagePath)) {
            $this->abortProgress();
            $this->error("The package was uninstalled, but its directory {$packagePath} could not be removed.");

            return self::FAILURE;
        }
        $this->makeProgress();

        // Remove the vendor directory, if agreed to
        if ($this->confirm('Do you want to remove the vendor directory? [y|N]')) {
            $vendorPath = $this->fileHandler->vendorPath($this->conveyor->vendor());

            if (!$this->fileHandler->exists($vendorPath)) {
                $this->info('vendor directory does not exist, continuing...');
            } elseif (!$this->fileHandler->isEmptyDir($vendorPath)) {
                $this->warn('vendor directory is not empty, continuing...');
            } else {
                $this->info('removing vendor directory...');
                $this->fileHandler->removeDir($vendorPath);
            }
        } else {
            $this->info('Continuing...');
        }
        $this->makeProgress();

        // Finished removing the package, end of the progress bar
        $this->finishProgress('Package removed successfully!');

        return self::SUCCESS;
    }
}
