<?php

declare(strict_types=1);

namespace JeroenG\Packager\Commands;

use Illuminate\Console\Command;
use JeroenG\Packager\Conveyor;
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

    public function __construct(Conveyor $conveyor)
    {
        parent::__construct();
        $this->conveyor = $conveyor;
    }

    public function handle(): void
    {
        // Start the progress bar
        $this->startProgressBar(5);

        // Defining vendor/package
        $this->conveyor->vendor($this->argument('vendor'));
        $this->conveyor->package($this->argument('name'));

        $this->info('Initialising Git if not already done so...');
        if (! file_exists($this->conveyor->packagePath().'/.git/')) {
            exec('cd '.$this->conveyor->packagePath().' && git init && git add --all && git commit -m "Initial commit"');
        }
        $this->makeProgress();

        $this->info('Git is set up, adding the remote repository...');
        exec('cd '.$this->conveyor->packagePath().' && git remote add origin '.$this->argument('url'));
        $this->makeProgress();

        $this->info('Pushing to Github...');
        exec('cd '.$this->conveyor->packagePath().' && git push -u origin master');
        $this->makeProgress();

        // Finished publishing the package, end of the progress bar
        $this->finishProgress('Package created successfully!');
        $this->info('Go ahead and submit it to Packagist: https://packagist.org/packages/submit');
    }
}
