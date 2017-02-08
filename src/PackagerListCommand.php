<?php

namespace JeroenG\Packager;

use Illuminate\Console\Command;

/**
 * List all locally installed packages.
 *
 * @package Packager
 * @author JeroenG
 *
 **/
class PackagerListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'packager:list';

    /**
     * The console command description.
     *
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
        $composer = json_decode(file_get_contents('composer.json'), true);
        $packages = [];

        foreach ($composer['autoload']['psr-4'] as $package => $path) {
            if($package !== 'App\\') {
                $packages[] = [rtrim($package, '\\'), $path];
            }
        }

        $headers = ['Package', 'Path'];
        $this->table($headers, $packages);
    }
}
