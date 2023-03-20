<?php

declare(strict_types=1);

namespace JeroenG\Packager\Commands;

use Illuminate\Console\Command;
use JeroenG\Packager\Conveyor;
use JeroenG\Packager\FileHandlerInterface;
use JeroenG\Packager\ProgressBar;

/**
 * Get an existing package from a remote Github repository with its git repository.
 *
 * @author JeroenG
 **/
class PublishPackage extends Command
{
    use ProgressBar;

    protected $signature = 'packager:publish
                            {vendor : The vendor part of the namespace}
                            {name : The name of package for the namespace}
                            {url : The url of the Github repository}';

    protected $description = 'Publish your package to Github with git.';

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

    public function handle(): void
    {
        // Start the progress bar
        $this->startProgressBar(5);

        // Defining vendor/package
        $this->conveyor->vendor($this->argument('vendor'));
        $this->conveyor->package($this->argument('name'));

        $this->info('Initialising Git if not already done so...');
        if (! file_exists($this->fileHandler->packagePath($this->conveyor->vendor(), $this->conveyor->package()).'/.git/')) {
            exec('cd '.$this->fileHandler->packagePath($this->conveyor->vendor(), $this->conveyor->package()).' && git init && git add --all && git commit -m "Initial commit"');
        }
        $this->makeProgress();

        $this->info('Git is set up, adding the remote repository...');
        exec('cd '.$this->fileHandler->packagePath($this->conveyor->vendor(), $this->conveyor->package()).' && git remote add origin '.$this->argument('url'));
        $this->makeProgress();

        $this->info('Pushing to Github...');
        exec('cd '.$this->fileHandler->packagePath($this->conveyor->vendor(), $this->conveyor->package()).' && git push -u origin master');
        $this->makeProgress();

        // Finished publishing the package, end of the progress bar
        $this->finishProgress('Package created successfully!');
        $this->info('Go ahead and submit it to Packagist: https://packagist.org/packages/submit');
    }
}
