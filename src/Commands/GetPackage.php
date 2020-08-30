<?php

namespace JeroenG\Packager\Commands;

use Illuminate\Console\Command;
use JeroenG\Packager\Conveyor;
use JeroenG\Packager\PackageRepository;
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
                            {url : The url of the repository or package name}
                            {vendor? : The vendor part of the namespace}
                            {name? : The name of package for the namespace}
                            {--branch=master : The branch to download}';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Retrieve an existing package.';

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
     * Package repository url parser
     * @var object \JeroenG\Packager\PackageRepository
     */
    protected $packageRepository;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Conveyor $conveyor, Wrapping $wrapping, PackageRepository $packageRepository)
    {
        parent::__construct();
        $this->conveyor = $conveyor;
        $this->wrapping = $wrapping;
        $this->packageRepository = $packageRepository;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Start the progress bar
        $this->startProgressBar(6);

        $this->info('Get package information...');
        $packageRepository = $this->packageRepository->parse($this->argument('url'));

        if (is_null($this->argument('vendor')) || is_null($this->argument('name'))) {
            $this->conveyor->vendor($packageRepository->vendor);
            $this->conveyor->package($packageRepository->name);
        } else {
            $this->conveyor->vendor($this->argument('vendor'));
            $this->conveyor->package($this->argument('name'));
        }
        $this->makeProgress();

        // Create the package directory
        $this->info('Creating packages directory...');
        $this->conveyor->makeDir($this->conveyor->packagesPath());
        $this->makeProgress();

        // Start creating the package
        $this->info('Creating package '.$this->conveyor->vendor().'\\'.$this->conveyor->package().'...');
        $this->conveyor->checkIfPackageExists();
        $this->makeProgress();

        // Create the vendor directory
        $this->info('Creating vendor...');
        $this->conveyor->makeDir($this->conveyor->vendorPath());
        $this->makeProgress();

        // Get the repo
        $this->info(sprintf('Downloading zip file from %s...', $packageRepository->host));
        $this->conveyor->downloadZipFile($packageRepository, $this->option('branch'));
        $this->makeProgress();

        // Install the package (composer require)
        $this->info('Installing package...');
        $this->conveyor->installPackage();
        $this->makeProgress();

        // Finished creating the package, end of the progress bar
        $this->finishProgress('Package downloaded successfully!');
    }
}
