<?php

declare(strict_types=1);

namespace JeroenG\Packager\Commands;

use Illuminate\Console\Command;
use JeroenG\Packager\Conveyor;
use JeroenG\Packager\FileHandlerInterface;
use JeroenG\Packager\ProgressBar;
use JeroenG\Packager\Wrapping;

/**
 * Get an existing package from a remote git repository.
 *
 * @author JeroenG
 **/
class GetPackage extends Command
{
    use ProgressBar;

    protected $signature = 'packager:get
                            {url : The url of the repository}
                            {vendor? : The vendor part of the namespace}
                            {name? : The name of package for the namespace}
                            {--branch=master : The branch to download}';

    protected $description = 'Retrieve an existing package from Github or Bitbucket.';

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

        // Common variables
        $origin = mb_strtolower(rtrim($this->argument('url'), '/')).'/archive/'.$this->option('branch').'.zip';
        $pieces = explode('/', $origin);
        if (is_null($this->argument('vendor')) || is_null($this->argument('name'))) {
            $this->conveyor->vendor($pieces[3]);
            $this->conveyor->package($pieces[4]);
        } else {
            $this->conveyor->vendor($this->argument('vendor'));
            $this->conveyor->package($this->argument('name'));
        }

        // Start creating the package
        $this->info('Creating package '.$this->conveyor->vendor().'\\'.$this->conveyor->package().'...');
        $this->fileHandler->checkIfPackageExists($this->conveyor->vendor(), $this->conveyor->package());
        $this->makeProgress();

        // Create the package directory
        $this->info('Creating packages directory...');
        $this->fileHandler->makeDir($this->fileHandler->packagesPath());
        $this->makeProgress();

        // Create the vendor directory
        $this->info('Creating vendor...');
        $this->fileHandler->makeDir($this->fileHandler->vendorPath($this->conveyor->vendor()));
        $this->makeProgress();

        $this->info('Downloading from Github...');
        $this->conveyor->downloadFromGithub($origin, $pieces[4], $this->option('branch'));

        $this->makeProgress();

        // Install the package
        $this->info('Installing package...');
        $this->conveyor->installPackage();
        $this->makeProgress();

        // Finished creating the package, end of the progress bar
        $this->finishProgress('Package downloaded successfully!');
    }
}
