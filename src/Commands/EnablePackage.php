<?php

namespace JeroenG\Packager\Commands;

use JeroenG\Packager\Conveyor;
use JeroenG\Packager\Wrapping;
use Illuminate\Console\Command;
use JeroenG\Packager\ProgressBar;

/**
 * remove an existing package.
 *
 * @author JeroenG
 **/
class EnablePackage extends Command
{
    use ProgressBar;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'packager:enable {vendor} {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a package to composer.json and the providers config.';

    /**
     * Packages roll off of the conveyor.
     *
     * @var object \JeroenG\Packager\Conveyor
     */
    protected $conveyor;

    /**
     * Packages are packed in wrappings to personalise them.
     *
     * @var object \JeroenG\Packager\Wrapping
     */
    protected $wrapping;

    /**
     * Create a new command instance.
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
        $this->startProgressBar(2);

        // Defining vendor/package
        $this->conveyor->vendor($this->argument('vendor'));
        $this->conveyor->package($this->argument('name'));

        // Start removing the package
        $this->info('Enabling package '.$this->conveyor->vendor().'\\'.$this->conveyor->package().'...');
        $this->makeProgress();

        // Composer dump-autoload to remove service provider
        $this->info('Dumping autoloads and undiscovering package...');
        $this->wrapping->addToComposer($this->conveyor->vendor(), $this->conveyor->package());
        $this->wrapping->addToProviders($this->conveyor->vendor(), $this->conveyor->package());
        $this->conveyor->dumpAutoloads();
        $this->makeProgress();

        // Finished removing the package, end of the progress bar
        $this->finishProgress('Package enabled successfully!');
    }
}
