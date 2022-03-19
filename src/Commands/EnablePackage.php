<?php

declare(strict_types=1);

namespace JeroenG\Packager\Commands;

use Illuminate\Console\Command;
use JeroenG\Packager\Conveyor;
use JeroenG\Packager\ProgressBar;
use JeroenG\Packager\Wrapping;

/**
 * Enable an existing package.
 *
 * @author JeroenG
 **/
class EnablePackage extends Command
{
    use ProgressBar;

    protected $signature = 'packager:enable {vendor} {name}';

    protected $description = 'Add a package to composer.json and the providers config.';

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
        $this->startProgressBar(2);

        // Defining vendor/package
        $this->conveyor->vendor($this->argument('vendor'));
        $this->conveyor->package($this->argument('name'));

        // Start removing the package
        $this->info('Enabling package '.$this->conveyor->vendor().'\\'.$this->conveyor->package().'...');
        $this->makeProgress();

        // Install the package
        $this->info('Installing package...');
        $this->conveyor->installPackage();
        $this->makeProgress();

        // Finished removing the package, end of the progress bar
        $this->finishProgress('Package enabled successfully!');
    }
}
