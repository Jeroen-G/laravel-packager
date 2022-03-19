<?php

declare(strict_types=1);

namespace JeroenG\Packager\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use JeroenG\Packager\Conveyor;
use JeroenG\Packager\ProgressBar;
use JeroenG\Packager\Wrapping;

/**
 * Get an existing package from a remote git repository with its VCS.
 *
 * @author JeroenG
 **/
class GitPackage extends Command
{
    use ProgressBar;

    protected $signature = 'packager:git
                            {url : The url of the git repository}
                            {vendor? : The vendor part of the namespace}
                            {name? : The name of package for the namespace}';

    protected $description = 'Retrieve an existing package with git.';

    /**
     * Packages roll off of the conveyor.
     */
    protected Conveyor $conveyor;

    /**
     * Packages are packed in wrappings to personalise them.
     */
    protected Wrapping $wrapping;

    public function __construct(Conveyor $conveyor, Wrapping $wrapping)
    {
        parent::__construct();
        $this->conveyor = $conveyor;
        $this->wrapping = $wrapping;
    }

    public function handle(): void
    {
        // Start the progress bar
        $this->startProgressBar(4);

        // Common variables
        $source = $this->argument('url');
        $origin = mb_strtolower(rtrim($source, '/'));

        if (is_null($this->argument('vendor')) || is_null($this->argument('name'))) {
            $this->setGitVendorAndPackage($origin);
        } else {
            $this->conveyor->vendor($this->argument('vendor'));
            $this->conveyor->package($this->argument('name'));
        }

        // Start creating the package
        $this->info('Creating package '.$this->conveyor->vendor().'\\'.$this->conveyor->package().'...');
        $this->conveyor->checkIfPackageExists();
        $this->makeProgress();

        // Create the package directory
        $this->info('Creating packages directory...');
        $this->conveyor->makeDir($this->conveyor->packagesPath());

        // Clone the repository
        $this->info('Cloning repository...');
        exec("git clone -q $source ".$this->conveyor->packagePath(), $output, $exit_code);

        if ($exit_code !== 0) {
            $this->error('Unable to clone repository');
            $this->warn('Please check credentials and try again');

            return;
        }

        $this->makeProgress();

        // Create the vendor directory
        $this->info('Creating vendor...');
        $this->conveyor->makeDir($this->conveyor->vendorPath());
        $this->makeProgress();

        $this->info('Installing package...');
        $this->conveyor->installPackage();
        $this->makeProgress();

        // Finished creating the package, end of the progress bar
        $this->finishProgress('Package cloned successfully!');
    }

    protected function setGitVendorAndPackage($origin): void
    {
        $pieces = explode('/', $origin);

        if (Str::contains($origin, 'https')) {
            $vendor = $pieces[3];
            $package = $pieces[4];
        } else {
            $vendor = explode(':', $pieces[0])[1];
            $package = rtrim($pieces[1], '.git');
        }

        $this->conveyor->vendor($vendor);
        $this->conveyor->package($package);
    }
}
