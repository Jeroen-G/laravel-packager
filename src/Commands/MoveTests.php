<?php

namespace JeroenG\Packager\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

/**
 * Move the packages tests to the Laravel app tests folder.
 *
 * @author JeroenG
 **/
class MoveTests extends Command
{
    protected $signature = 'packager:tests
                            {vendor? : The package for which to move the tests}
                            {name? : The package for which to move the tests}';

    protected $description = 'Move test files to the Laravel app tests folder';

    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle(): void
    {
        if (is_null($this->argument('vendor')) || is_null($this->argument('name'))) {
            $this->info('Moving tests for all local packages');

            $composer = json_decode(file_get_contents('composer.json'), true, 512, JSON_THROW_ON_ERROR);

            $packages = [];
            foreach ($composer['autoload']['psr-4'] as $package => $path) {
                if ($package !== 'App\\' && $package !== 'Tests\\') {
                    $packages[] = [rtrim($package, '\\'), $path];
                }
            }

            foreach ($packages as $package) {
                $path = dirname(getcwd().'/'.$package[1]).'/tests';

                if ($this->files->exists($path)) {
                    $this->info('Moving tests for the package: '.$package[0]);
                    $this->files->copyDirectory($path, base_path('tests/packages/'.$package[0]));
                } else {
                    $this->info('No tests found for: '.$package[0]);
                }
            }
        } else {
            $vendor = $this->argument('vendor');
            $name = $this->argument('name');
            $path = base_path('packages/'.$vendor.'/'.$name.'/tests');

            if ($this->files->exists($path)) {
                $this->info('Moving tests for the package: '.$vendor.'/'.$name);
                $this->files->copyDirectory($path, base_path('tests/packages/'.$vendor.'/'.$name));
            } else {
                $this->info('No tests found for: '.$vendor.'/'.$name);
            }
        }
        $this->info('Done!');
    }
}
