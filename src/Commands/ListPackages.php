<?php

namespace JeroenG\Packager\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

/**
 * List all locally installed packages.
 *
 * @author JeroenG
 **/
class ListPackages extends Command
{
    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'packager:list
                           {--g|git : Show Git branch status}';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'List all locally installed packages.';

    /**
     * Execute the console command.
     *
     * @return void
     */
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
     * Get all of the packages installed with Packager.
     *
     * @return array
     */
    private function getPackagesList(): array
    {
        $composerFile = json_decode(file_get_contents(base_path('composer.json')), true);
        $packagesPath = base_path('packages/');
        $repositories = $composerFile['repositories'] ?? [];
        $packages = [];
        foreach ($repositories as $name => $info) {
            $path = $info['url'];
            $pattern = '{'.addslashes($packagesPath).'(.*)$}';
            if (preg_match($pattern, $path, $match)) {
                $packages[] = explode(DIRECTORY_SEPARATOR, $match[1]);
            }
        }

        return $packages;
    }

    /**
     * Render the list as a simple table.
     *
     * @param array $packages
     */
    private function renderBasicTable(array $packages): void
    {
        $headers = ['Package', 'Path'];
        $this->table($headers, $packages);
    }

    /**
     * Render the list, but with git status if the package has git initialised.
     *
     * @param $packages
     */
    private function renderGitTable($packages): void
    {
        $gitPackages = [];
        foreach ($packages as $package) {
            $gitPackages[] = array_merge($package, $this->getGitStatus($package[1]));
        }

        $headers = ['Package', 'Path', 'Commits behind', 'Branch'];

        $this->table($headers, $gitPackages);
    }

    /**
     * If a package has a git history, add its status.
     *
     * @param string $path
     *
     * @return array
     */
    private function getGitStatus(string $path): array
    {
        if (file_exists($path.DIRECTORY_SEPARATOR.'.git')) {
            (new Process(['git fetch'], $path))->disableOutput()->run();

            $commitDifference = $this->getCommitDifference($path);
            $branch = $this->getCurrentBranchForPackage($path);

            return [$commitDifference, $branch];
        }

        return ['-', '-'];
    }

    /**
     * Compare the local git history with the origin remote.
     * It returns the difference in commits as a positive or negative integer.
     * A positive number means the local package is behind. Otherwise it is ahead.
     *
     * @param string $path
     *
     * @return int
     */
    private function getCommitDifference(string $path): int
    {
        $commitDifference = 0;

        (new Process(['git rev-list HEAD..origin --count'], $path))
            ->run(function ($type, $buffer) use (&$commitDifference) {
                $commitDifference = str_replace(["\n", "\r"], '', $buffer);
            });

        return $commitDifference;
    }

    /**
     * Gets the branch name for a package.
     *
     * @param $path
     *
     * @return string|null
     */
    private function getCurrentBranchForPackage($path): ?string
    {
        $branch = null;

        // This command lists all branches
        (new Process(['git branch'], $path))
            ->run(function ($type, $buffer) use (&$branch) {
                // The current branch is prefixed with an asterisk
                if (Str::startsWith($buffer, '*')) {
                    $branch = str_replace(["\n", "\r", ' ', '*'], '', $buffer);
                }
            });

        return $branch;
    }
}
