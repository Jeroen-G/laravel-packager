<?php

declare(strict_types=1);

namespace JeroenG\Packager\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use JeroenG\Packager\CommandRunnerInterface;
use JeroenG\Packager\Conveyor;
use JeroenG\Packager\FileHandlerInterface;
use JeroenG\Packager\ProgressBar;

/**
 * Get an existing package from a remote git repository with its VCS.
 *
 * @author JeroenG
 **/
class GitPackage extends Command
{
    use InteractsWithStringInput;
    use ProgressBar;
    use ReportsComposerFailures;

    protected $signature = 'packager:git
                            {url : The url of the git repository}
                            {vendor? : The vendor part of the namespace}
                            {name? : The name of package for the namespace}';

    protected $description = 'Retrieve an existing package with git.';

    /**
     * Packages roll off of the conveyor.
     */
    protected Conveyor $conveyor;

    protected FileHandlerInterface $fileHandler;

    protected CommandRunnerInterface $commandRunner;

    public function __construct(
        Conveyor $conveyor,
        FileHandlerInterface $fileHandler,
        CommandRunnerInterface $commandRunner,
    ) {
        parent::__construct();
        $this->conveyor = $conveyor;
        $this->fileHandler = $fileHandler;
        $this->commandRunner = $commandRunner;
    }

    public function handle(): int
    {
        // Start the progress bar
        $this->startProgressBar(4);

        // Common variables
        $source = $this->requiredArgument('url');
        $origin = mb_strtolower(rtrim($source, '/'));

        if (is_null($this->stringArgument('vendor')) || is_null($this->stringArgument('name'))) {
            if (!$this->setGitVendorAndPackage($origin)) {
                $this->abortProgress();
                $this->error("Unable to determine the vendor and package from {$source}, please provide them: packager:git <url> <vendor> <name>");

                return self::FAILURE;
            }
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

        // Clone the repository
        $this->info('Cloning repository...');
        $cloned = $this->commandRunner->run([
            'git',
            'clone',
            '-q',
            $source,
            $this->fileHandler->packagePath($this->conveyor->vendor(), $this->conveyor->package()),
        ]);

        if (!$cloned) {
            $this->abortProgress();
            $this->error('Unable to clone repository');
            $this->warn('Please check credentials and try again');

            return self::FAILURE;
        }

        $this->makeProgress();

        // Create the vendor directory
        $this->info('Creating vendor...');
        $this->fileHandler->makeDir($this->fileHandler->vendorPath($this->conveyor->vendor()));
        $this->makeProgress();

        $this->info('Installing package...');
        if (!$this->conveyor->installPackage()) {
            return $this->installFailed($this->fileHandler->packagePath($this->conveyor->vendor(), $this->conveyor->package()));
        }
        $this->makeProgress();

        // Finished creating the package, end of the progress bar
        $this->finishProgress('Package cloned successfully!');

        return self::SUCCESS;
    }

    /**
     * Use the last two parts of the repository path as vendor and package, as in https://host/vendor/package,
     * git@host:vendor/package.git or file:///path/to/vendor/package.git. Returns false when there are none.
     */
    protected function setGitVendorAndPackage(string $origin): bool
    {
        // Drop the scheme and host of a url, or the user@host: of the scp-like syntax.
        $path = preg_replace(['{^[a-z][a-z0-9+.-]*://[^/]*}i', '{^[^/]*:}'], '', $origin) ?? '';
        $segments = array_values(array_filter(explode('/', $path), static fn (string $segment): bool => $segment !== ''));

        if (count($segments) < 2) {
            return false;
        }

        $vendor = $segments[count($segments) - 2];
        $package = Str::replaceEnd('.git', '', $segments[count($segments) - 1]);

        if ($package === '' || in_array($vendor, ['.', '..'], true) || in_array($package, ['.', '..'], true)) {
            return false;
        }

        $this->conveyor->vendor($vendor);
        $this->conveyor->package($package);

        return true;
    }
}
