<?php

declare(strict_types=1);

namespace JeroenG\Packager\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use JeroenG\Packager\Conveyor;
use JeroenG\Packager\FileHandlerInterface;
use JeroenG\Packager\ProgressBar;

/**
 * Get an existing package from a remote git repository.
 *
 * @author JeroenG
 **/
class GetPackage extends Command
{
    use InteractsWithStringInput;
    use ProgressBar;
    use ReportsComposerFailures;

    protected $signature = 'packager:get
                            {url : The url of the repository}
                            {vendor? : The vendor part of the namespace}
                            {name? : The name of package for the namespace}
                            {--branch=master : The branch to download}';

    protected $description = 'Retrieve an existing package from Github.';

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
        // Start the progress bar
        $this->startProgressBar(5);

        // Common variables
        $repository = Str::replaceEnd('.git', '', mb_strtolower(rtrim($this->requiredArgument('url'), '/')));
        $origin = $repository.'/archive/'.$this->stringOption('branch').'.zip';
        $pieces = explode('/', $origin);
        if (is_null($this->stringArgument('vendor')) || is_null($this->stringArgument('name'))) {
            $this->conveyor->vendor($pieces[3]);
            $this->conveyor->package($pieces[4]);
        } else {
            $this->conveyor->vendor($this->stringArgument('vendor'));
            $this->conveyor->package($this->stringArgument('name'));
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
        $this->conveyor->downloadFromGithub($origin);

        $this->makeProgress();

        // Install the package
        $this->info('Installing package...');
        if (!$this->conveyor->installPackage()) {
            return $this->installFailed($this->fileHandler->packagePath($this->conveyor->vendor(), $this->conveyor->package()));
        }
        $this->makeProgress();

        // Finished creating the package, end of the progress bar
        $this->finishProgress('Package downloaded successfully!');

        return self::SUCCESS;
    }
}
