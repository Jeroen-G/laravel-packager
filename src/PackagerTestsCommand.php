<?php

namespace JeroenG\Packager;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

/**
 * Move the packages tests to the Laravel app tests folder.
 *
 * @package Packager
 * @author JeroenG
 * 
 **/
class PackagerTestsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'packager:tests
                            {vendor? : The package for which to move the tests}
                            {name? : The package for which to move the tests}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Move test files to the Laravel app tests folder';

    /**
     * The filesystem handler.
     * 
     * @var object
     */
    protected $files;

    /**
     * Create a new instance.
     * @param Illuminate\Filesystem\Filesystem $files
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if(is_null($this->argument('vendor')) || is_null($this->argument('name'))) {
            $this->info('Moving tests for all local packages');

            $composer = json_decode(file_get_contents('composer.json'), true);

            foreach ($composer['autoload']['psr-4'] as $package => $path) {
                if($package !== 'App\\' && $package !== 'Tests\\') {
                    $packages[] = [rtrim($package, '\\'), $path];
                }
            }

            foreach ($packages as $package) {
                $path = dirname(getcwd().'/'.$package[1]).'/tests';

                if($this->files->exists($path)) {
                    $this->info('Moving tests for the package: '.$package[0]);
                    $this->files->copyDirectory($path, base_path('tests/packages/'.$package[0]));
                }
                else {
                    $this->info('No tests found for: '.$package[0]);
                }
            }
        }
        else {
            $vendor = $this->argument('vendor');
            $name = $this->argument('name');
            $path = base_path('packages/'.$vendor.'/'.$name.'/tests');

            if($this->files->exists($path)) {   
                $this->info('Moving tests for the package: '.$vendor.'/'.$name);
                $this->files->copyDirectory($path, base_path('tests/packages/'.$vendor.'/'.$name));
            }
            else {
                $this->info('No tests found for: '.$vendor.'/'.$name);
            }
        }
        $this->info('Done!');
    }
}
