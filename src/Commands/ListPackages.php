<?php

namespace JeroenG\Packager\Commands;

use Illuminate\Console\Command;

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
    protected $signature = 'packager:list';

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
            if ($info['type'] === 'path'){
                $path = $info['url'];
                $pattern = '{'.addslashes($packages_path).'(.*)$}';
                if (preg_match($pattern, $path, $match)) {
                    $packages[] = [$name, 'packages/'.$match[1]];
                }
            }
            else if ($info['type'] === 'vcs'){
                $path = $packages_path . $name;
                if (file_exists($path)){
                    $pattern = '{'.addslashes($packages_path).'(.*)$}';
                    if (preg_match($pattern, $path, $match)) {
                        $packages[] = [$name, 'packages/'.$match[1]];
                    }
                }
            }
        }
        $headers = ['Package', 'Path'];
        $this->table($headers, $packages);
    }

    protected function getGitStatus($path)
    {
        $command = sprintf('git --work-tree=%s status', realpath($path));
    }
}
