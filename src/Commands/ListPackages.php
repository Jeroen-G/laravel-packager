<?php

namespace JeroenG\Packager\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
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
    protected $signature = 'packager:list {--g|git}';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'List all locally installed packages.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $composer = json_decode(file_get_contents(base_path('composer.json')), true);
        $packages_path = base_path('packages/');
        $repositories = $composer['repositories'] ?? [];
        $packages = [];
        foreach ($repositories as $name => $info) {
            $path = $info['url'];
            $pattern = '{'.addslashes($packages_path).'(.*)$}';
            if (preg_match($pattern, $path, $match)) {
                $packages[] = explode(DIRECTORY_SEPARATOR, $match[1]);
            }
        }

        if ($this->option('git')) {
            return $this->renderGitPackages($packages, $packages_path);
        }

        $headers = ['Package', 'Path'];
        $this->table($headers, $packages);
    }

    private function renderGitPackages($packages, $packages_path)
    {
        $gitPackages = collect($packages)
            ->map(function ($package) use ($packages_path) {
                return [
                    'vendor' => $package[0],
                    'name'   => $package[1],
                    'path'   => $packages_path.implode(DIRECTORY_SEPARATOR, [$package[0], $package[1]]),
                ];
            })
            // Filter out none-git packages
            ->filter(function ($package) {
                return file_exists($package['path'].DIRECTORY_SEPARATOR.'.git');
            })
            ->map(function ($package) {
                // Always run fetch first to get the latest repo state
                (new Process('git fetch', $package['path']))->disableOutput()->run();

                // get the amount of commits difference
                $commitDifference = $this->getCommitDifferenceAmount($package['path']);

                // Get the current branch
                $branch = $this->getBranchForPackage($package['path']);

                return [
                    $package['vendor'],
                    $package['name'],
                    $commitDifference,
                    $branch,
                ];
            });

        $headers = ['Package', 'Path', 'Commits behind', 'Branch'];

        $this->table($headers, $gitPackages->toArray());
    }

    /**
     * Compares the local package against the repo and returns the difference in commits.
     * A possitive number means the local package is x commits behind the repo.
     * 
     * @param $path
     *
     * @return int
     */
    private function getCommitDifferenceAmount($path)
    {
        $commitDifference = 0;

        (new Process('git rev-list HEAD..origin --count', $package['path']))
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
    private function getBranchForPackage($path)
    {
        $branch = null;

        // This command lists all branches
        (new Process('git branch', $package['path']))
            ->run(function ($type, $buffer) use (&$branch) {
                // The current branch is prefixed with an asterisk
                if (Str::startsWith($buffer, '*')) {
                    $branch = str_replace(["\n", "\r", ' ', '*'], '', $buffer);
                }
            });

        return $branch;
    }
}
