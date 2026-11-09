<?php

declare(strict_types=1);

namespace JeroenG\Packager\Commands;

use Illuminate\Console\Command;
use JeroenG\Packager\Conveyor;
use JeroenG\Packager\ProgressBar;

/**
 * remove an existing package.
 *
 * @author JeroenG
 **/
class DisablePackage extends Command
{
    use InteractsWithStringInput;
    use ProgressBar;
    use ReportsComposerFailures;

    protected $signature = 'packager:disable {vendor} {name}';

    protected $description = 'Remove a package from composer.json and the providers config.';

    protected Conveyor $conveyor;

    public function __construct(Conveyor $conveyor)
    {
        parent::__construct();
        $this->conveyor = $conveyor;
    }

    public function handle(): int
    {
        // Start the progress bar
        $this->startProgressBar(2);

        // Defining vendor/package
        $this->conveyor->vendor($this->requiredArgument('vendor'));
        $this->conveyor->package($this->requiredArgument('name'));

        // Start removing the package
        $this->info('Disabling package '.$this->conveyor->vendor().'\\'.$this->conveyor->package().'...');
        $this->makeProgress();

        // Uninstall the package
        $this->info('Uninstalling package...');
        if (!$this->conveyor->uninstallPackage()) {
            return $this->composerFailed('Composer could not remove the package from composer.json.');
        }
        $this->makeProgress();

        // Finished removing the package, end of the progress bar
        $this->finishProgress('Package disabled successfully!');

        return self::SUCCESS;
    }
}
