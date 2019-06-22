<?php

namespace JeroenG\Packager\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
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
            if ($info['type'] === 'path') {
                $path = $info['url'];
                $pattern = '{'.addslashes($packages_path).'(.*)$}';
                if (preg_match($pattern, $path, $match)) {
                    $status = $this->getGitStatus($path);
                    $packages[] = [$name, 'packages/'.$match[1], $status];
                }
            } elseif ($info['type'] === 'vcs') {
                $path = $packages_path.$name;
                if (file_exists($path)) {
                    $pattern = '{'.addslashes($packages_path).'(.*)$}';
                    if (preg_match($pattern, $path, $match)) {
                        $status = $this->getGitStatus($path);
                        $packages[] = [$name, 'packages/'.$match[1], $status];
                    }
                }
            }
        }
        $headers = ['Package', 'Path', 'Git status'];
        $this->table($headers, $packages);
    }

    protected function getGitStatus($path)
    {
        if (! File::exists($path.'/.git')) {
            return 'Not initialized';
        }
        $status = '<info>Up to date</info>';
        (new Process(['git', 'fetch', '--all'], $path))->run();
        (new Process(['git', '--git-dir='.$path.'/.git', '--work-tree='.$path, 'status', '-sb'], $path))->run(function (
            $type,
            $buffer
        ) use (&$status) {
            if (preg_match('/^##/', $buffer)) {
                if (preg_match('/\[(.*)\]$/', $buffer, $match)) {
                    $status = '<comment>'.ucfirst($match[1]).'</comment>';
                }
            }
        });
        return $status;
    }
}
