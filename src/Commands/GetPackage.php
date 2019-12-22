<?php

namespace JeroenG\Packager\Commands;

use Illuminate\Console\Command;
use JeroenG\Packager\Conveyor;
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

    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'packager:get
                            {url : The url of the repository}
                            {vendor? : The vendor part of the namespace}
                            {name? : The name of package for the namespace}
                            {--host=github : Download from github or bitbucket?}
                            {--branch=master : The branch to download}';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Retrieve an existing package from Github or Bitbucket.';

    /**
     * Packages roll off of the conveyor.
     * @var object \JeroenG\Packager\Conveyor
     */
    protected $conveyor;

    /**
     * Packages are packed in wrappings to personalise them.
     * @var object \JeroenG\Packager\Wrapping
     */
    protected $wrapping;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Conveyor $conveyor, Wrapping $wrapping)
    {
        parent::__construct();
        $this->conveyor = $conveyor;
        $this->wrapping = $wrapping;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Start the progress bar
        $this->startProgressBar(4);

        // Common variables
        if ($this->option('host') == 'bitbucket') {
            $origin = rtrim(strtolower($this->argument('url')), '/').'/branch/'.$this->option('branch').'.zip';
        } else {
            $origin = rtrim(strtolower($this->argument('url')), '/').'/archive/'.$this->option('branch').'.zip';
        }
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
        $this->conveyor->checkIfPackageExists();
        $this->makeProgress();

        // Create the package directory
        $this->info('Creating packages directory...');
        $this->conveyor->makeDir($this->conveyor->packagesPath());
        $this->makeProgress();

        // Create the vendor directory
        $this->info('Creating vendor...');
        $this->conveyor->makeDir($this->conveyor->vendorPath());
        $this->makeProgress();

        // Get the repo from Github or Bitbucket
        if ($this->option('host') == ' bitbucket') {
            $this->info('Downloading from Bitbucket...');
            $this->conveyor->downloadFromBitbucket($origin, $pieces[4], $this->option('branch'));
        } else {
            $this->info('Downloading from Github...');
            $this->conveyor->downloadFromGithub($origin, $pieces[4], $this->option('branch'));
        }
        $this->makeProgress();

        // Install the package
        $this->info('Installing package...');
        $this->conveyor->installPackage();
        $this->makeProgress();

        // Finished creating the package, end of the progress bar
        $this->finishProgress('Package downloaded successfully!');
    }
}
