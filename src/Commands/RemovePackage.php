<?php

declare(strict_types=1);

namespace JeroenG\Packager\Commands;

use Illuminate\Console\Command;
use JeroenG\Packager\Conveyor;
use JeroenG\Packager\FileHandlerInterface;
use JeroenG\Packager\ProgressBar;
use JeroenG\Packager\Wrapping;

/**
 * remove an existing package.
 *
 * @author JeroenG
 **/
class RemovePackage extends Command
{
    use ProgressBar;

    protected $signature = 'packager:remove {vendor} {name?}';

    protected $description = 'Remove an existing package.';

    /**
     * Packages roll off of the conveyor.
     */
    protected Conveyor $conveyor;

    /**
     * Packages are packed in wrappings to personalise them.
     */
    protected Wrapping $wrapping;

    protected FileHandlerInterface $fileHandler;

    public function __construct(Conveyor $conveyor, Wrapping $wrapping, FileHandlerInterface $fileHandler)
    {
        parent::__construct();
        $this->conveyor = $conveyor;
        $this->wrapping = $wrapping;
        $this->fileHandler = $fileHandler;
    }

    public function handle(): void
    {
        // Start the progress bar
        $this->startProgressBar(4);

        $vendor = $this->argument('vendor');
        $name = $this->argument('name');

        if (mb_strpos($vendor, '/') !== false) {
            [$vendor, $name] = explode('/', $vendor);
        }

        $this->conveyor->vendor($vendor);
        $this->conveyor->package($name);

        // Start removing the package
        $this->info('Removing package '.$this->conveyor->vendor().'\\'.$this->conveyor->package().'...');
        $this->makeProgress();

        // Uninstall the package
        $this->info('Uninstalling package...');
        $this->conveyor->uninstallPackage();
        $this->makeProgress();

        // remove the package directory
        $this->info('Removing packages directory...');
        $this->fileHandler->removeDir($this->fileHandler->packagePath($this->conveyor->vendor(), $this->conveyor->package()));
        $this->makeProgress();

        // Remove the vendor directory, if agreed to
        if ($this->confirm('Do you want to remove the vendor directory? [y|N]')) {
            if (count(scandir($this->fileHandler->vendorPath($this->conveyor->vendor()))) !== 2) {
                $this->warn('vendor directory is not empty, continuing...');
            } else {
                $this->info('removing vendor directory...');
                $this->fileHandler->removeDir($this->fileHandler->vendorPath($this->conveyor->vendor()));
            }
        } else {
            $this->info('Continuing...');
        }
        $this->makeProgress();

        // Finished removing the package, end of the progress bar
        $this->finishProgress('Package removed successfully!');
    }
}
