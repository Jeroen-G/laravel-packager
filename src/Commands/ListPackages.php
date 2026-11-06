<?php

declare(strict_types=1);

namespace JeroenG\Packager\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use JeroenG\Packager\CommandRunnerInterface;
use JeroenG\Packager\FileHandlerInterface;

/**
 * List all locally installed packages.
 *
 * @author JeroenG
 **/
class ListPackages extends Command
{
    protected $signature = 'packager:list
                           {--g|git : Show Git branch status}';

    protected $description = 'List all locally installed packages.';

    protected FileHandlerInterface $fileHandler;

    protected CommandRunnerInterface $commandRunner;

    public function __construct(FileHandlerInterface $fileHandler, CommandRunnerInterface $commandRunner)
    {
        parent::__construct();
        $this->fileHandler = $fileHandler;
        $this->commandRunner = $commandRunner;
    }

    public function handle(): void
    {
        $packages = $this->getPackagesList();

        if ($this->option('git')) {
            $this->renderGitTable($packages);
        } else {
            $this->renderBasicTable($packages);
        }
    }

    /**
     * Get all of the packages installed with Packager, as [vendor, package] pairs.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    private function getPackagesList(): array
    {
        $composerFile = File::json(base_path('composer.json'), JSON_THROW_ON_ERROR);
        $pattern = '{^'.preg_quote($this->fileHandler->packagesPath().DIRECTORY_SEPARATOR, '{').'([^'.preg_quote(DIRECTORY_SEPARATOR, '{').']+)'.preg_quote(DIRECTORY_SEPARATOR, '{').'(.+)$}';
        $packages = [];

        foreach ($composerFile['repositories'] ?? [] as $info) {
            if (preg_match($pattern, $info['url'] ?? '', $match)) {
                $packages[] = [$match[1], $match[2]];
            }
        }

        return $packages;
    }

    /**
     * @param  array<int, array{0: string, 1: string}>  $packages
     */
    private function renderBasicTable(array $packages): void
    {
        $this->table(['Vendor', 'Package'], $packages);
    }

    /**
     * Render the list, but with git status if the package has git initialised.
     *
     * @param  array<int, array{0: string, 1: string}>  $packages
     */
    private function renderGitTable(array $packages): void
    {
        $gitPackages = [];
        foreach ($packages as $package) {
            $gitPackages[] = array_merge($package, $this->getGitStatus($this->fileHandler->packagePath($package[0], $package[1])));
        }

        $this->table(['Vendor', 'Package', 'Commits behind', 'Branch'], $gitPackages);
    }

    /**
     * If a package has a git history, add its status.
     *
     * @return array{0: int|string, 1: string}
     */
    private function getGitStatus(string $path): array
    {
        if (!file_exists($path.DIRECTORY_SEPARATOR.'.git')) {
            return ['-', '-'];
        }

        $this->commandRunner->run(['git', 'fetch'], $path);

        return [$this->getCommitDifference($path) ?? '-', $this->getCurrentBranch($path) ?? '-'];
    }

    /**
     * The number of commits the local package is behind on the origin remote, or null when unknown.
     */
    private function getCommitDifference(string $path): ?int
    {
        $count = $this->commandRunner->output(['git', 'rev-list', 'HEAD..origin', '--count'], $path);

        return is_numeric($count) ? (int) $count : null;
    }

    private function getCurrentBranch(string $path): ?string
    {
        return $this->commandRunner->output(['git', 'rev-parse', '--abbrev-ref', 'HEAD'], $path);
    }
}
