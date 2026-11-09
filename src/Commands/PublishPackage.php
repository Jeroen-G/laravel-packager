<?php

declare(strict_types=1);

namespace JeroenG\Packager\Commands;

use Illuminate\Console\Command;
use JeroenG\Packager\CommandRunnerInterface;
use JeroenG\Packager\Conveyor;
use JeroenG\Packager\FileHandlerInterface;
use JeroenG\Packager\ProgressBar;

/**
 * Publish a local package to a remote Github repository with git.
 *
 * @author JeroenG
 **/
class PublishPackage extends Command
{
    use InteractsWithStringInput;
    use ProgressBar;

    protected $signature = 'packager:publish
                            {vendor : The vendor part of the namespace}
                            {name : The name of package for the namespace}
                            {url : The url of the Github repository}';

    protected $description = 'Publish your package to Github with git.';

    protected Conveyor $conveyor;

    protected FileHandlerInterface $fileHandler;

    protected CommandRunnerInterface $commandRunner;

    public function __construct(Conveyor $conveyor, FileHandlerInterface $fileHandler, CommandRunnerInterface $commandRunner)
    {
        parent::__construct();
        $this->conveyor = $conveyor;
        $this->fileHandler = $fileHandler;
        $this->commandRunner = $commandRunner;
    }

    public function handle(): int
    {
        // Start the progress bar
        $this->startProgressBar(3);

        // Defining vendor/package
        $this->conveyor->vendor($this->requiredArgument('vendor'));
        $this->conveyor->package($this->requiredArgument('name'));
        $path = $this->fileHandler->packagePath($this->conveyor->vendor(), $this->conveyor->package());
        $url = $this->requiredArgument('url');

        if (!is_dir($path)) {
            return $this->abort('The package '.$this->conveyor->vendor().'/'.$this->conveyor->package().' does not exist.');
        }

        $this->info('Initialising Git if not already done so...');
        if (!file_exists($path.DIRECTORY_SEPARATOR.'.git')) {
            $initialised = $this->git(['init'], $path)
                && $this->git(['add', '--all'], $path)
                && $this->git(['commit', '-m', 'Initial commit'], $path);

            if (!$initialised) {
                return $this->abort('Unable to initialise a git repository for the package.');
            }
        }
        $this->makeProgress();

        $this->info('Git is set up, adding the remote repository...');
        $remote = $this->commandRunner->output(['git', 'remote', 'get-url', 'origin'], $path) === null
            ? ['remote', 'add', 'origin', $url]
            : ['remote', 'set-url', 'origin', $url];

        if (!$this->git($remote, $path)) {
            return $this->abort('Unable to set the remote repository.');
        }
        $this->makeProgress();

        $this->info('Pushing to Github...');
        if (!$this->git(['push', '-u', 'origin', 'HEAD'], $path)) {
            return $this->abort('Unable to push to the remote repository, please check your credentials and try again.');
        }
        $this->makeProgress();

        // Finished publishing the package, end of the progress bar
        $this->finishProgress('Package published successfully!');
        $this->info('Go ahead and submit it to Packagist: https://packagist.org/packages/submit');

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $arguments
     */
    private function git(array $arguments, string $path): bool
    {
        return $this->commandRunner->run(['git', ...$arguments], $path);
    }

    private function abort(string $message): int
    {
        $this->abortProgress();
        $this->error($message);

        return self::FAILURE;
    }
}
