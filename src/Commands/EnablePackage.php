<?php

declare(strict_types=1);

namespace JeroenG\Packager\Commands;

use Illuminate\Console\Command;
use JeroenG\Packager\Conveyor;
use JeroenG\Packager\FileHandlerInterface;
use JeroenG\Packager\ProgressBar;

/**
 * Enable an existing package.
 *
 * @author JeroenG
 **/
class EnablePackage extends Command
{
    use InteractsWithStringInput;
    use ProgressBar;
    use ReportsComposerFailures;

    protected $signature = 'packager:enable {vendor} {name}';

    protected $description = 'Add a package to composer.json and the providers config.';

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
        $this->startProgressBar(2);

        // Defining vendor/package
        $this->conveyor->vendor($this->requiredArgument('vendor'));
        $this->conveyor->package($this->requiredArgument('name'));

        // Composer fails on every command while a path repository points to a missing directory.
        $path = $this->fileHandler->packagePath($this->conveyor->vendor(), $this->conveyor->package());
        if (!$this->fileHandler->exists($path)) {
            $this->abortProgress();
            $this->error("The package {$this->conveyor->vendor()}/{$this->conveyor->package()} does not exist in {$path}.");

            return self::FAILURE;
        }

        // Start removing the package
        $this->info('Enabling package '.$this->conveyor->vendor().'\\'.$this->conveyor->package().'...');
        $this->makeProgress();

        // Install the package
        $this->info('Installing package...');
        if (!$this->conveyor->installPackage()) {
            return $this->composerFailed('Composer could not install the package.');
        }
        $this->makeProgress();

        // Finished removing the package, end of the progress bar
        $this->finishProgress('Package enabled successfully!');

        return self::SUCCESS;
    }
}
