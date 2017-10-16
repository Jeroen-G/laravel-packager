<?php

namespace JeroenG\Packager\Commands;

use JeroenG\Packager\Conveyor;
use Illuminate\Console\Command;
use JeroenG\Packager\ProgressBar;

/**
 * Get an existing package from a remote Github repository.
 *
 * @author JeroenG
 **/
class GetPackage extends Command
{
    use ProgressBar;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'packager:get
                            {url : The url of the Github repository}
                            {vendor? : The vendor part of the namespace}
                            {name? : The name of package for the namespace}
                            {--branch=master : The branch to download}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retrieve an existing package from Github.';

    /**
     * Packages roll off of the conveyor.
     * @var object \JeroenG\Packager\Conveyor
     */
    protected $conveyor;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Conveyor $conveyor)
    {
        parent::__construct();
        $this->conveyor = $conveyor;
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
        $origin = rtrim(strtolower($this->argument('url')), '/').'/archive/'.$this->option('branch').'.zip';
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

        // Get the skeleton repo from the PHP League
        $this->info('Downloading from Github...');
        $this->conveyor->downloadFromGithub($origin, $pieces[4], $this->option('branch'));
        $this->makeProgress();

        // Composer dump-autoload to identify new service provider
        $this->info('Dumping autoloads and discovering package...');
        $this->conveyor->dumpAutoloads();
        $this->conveyor->discoverPackage();
        $this->makeProgress();

        // Finished creating the package, end of the progress bar
        $this->finishProgress('Package downloaded successfully!');
    }
}
