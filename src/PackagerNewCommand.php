<?php

namespace JeroenG\Packager;

use Illuminate\Console\Command;
use JeroenG\Packager\PackagerHelper;

class PackagerNewCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "packager:new {vendor} {name}";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new package.';

    protected $helper;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(PackagerHelper $helper)
    {
        parent::__construct();
        $this->helper = $helper;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Start the progress bar
        $bar = $this->helper->barSetup($this->output->createProgressBar(7));
        $bar->start();

        // Common variables
        $vendor = $this->argument('vendor');
        $name = $this->argument('name');
        $path = getcwd().'/packages/';
        $fullPath = $path.$vendor.'/'.$name;
        $cVendor = ucfirst($vendor);
        $cName = ucfirst($name);
        $requireSupport = '"illuminate/support": "~5.1",
        "php"';
        $requirement = '"psr-4": {
            "'.$cVendor.'\\\\'.$cName.'\\\\": "packages/'.$vendor.'/'.$name.'/src",';
        $appConfigLine = 'App\Providers\RouteServiceProvider::class,

        '.ucfirst($vendor).'\\'.ucfirst($name).'\\'.ucfirst($name).'ServiceProvider::class,';

        // Start creating the package        
        $this->info('Creating package '.$vendor.'\\'.$name.'...');
            $this->helper->checkExistingPackage($path, $vendor, $name);
        $bar->advance();

        // Create the package directory
        $this->info('Creating packages directory...');
            $this->helper->makeDir($path);
        $bar->advance();

        // Create the vendor directory
        $this->info('Creating vendor...');
         $this->helper->makeDir($path.$vendor);
        $bar->advance();

        // Get the skeleton repo from the PHP League
        $this->info('Downloading skeleton...');
            $this->helper->download($zipFile = $this->helper->makeFilename(), 'http://github.com/thephpleague/skeleton/archive/master.zip')
                 ->extract($zipFile, $path.$vendor)
                 ->cleanUp($zipFile);
            rename($path.$vendor.'/skeleton-master', $fullPath);
        $bar->advance();

        // Creating a Laravel Service Provider in the src directory
        $this->info('Creating service provider...');
            $newProvider = $fullPath.'/src/'.ucfirst($name).'ServiceProvider.php';
            $this->helper->replaceAndSave(__DIR__.'/ServiceProvider.stub', ['{{vendor}}', '{{name}}'], [$cVendor, $cName], $newProvider);
        $bar->advance();

        // Replacing skeleton namespaces
        $this->info('Replacing skeleton namespaces...');
            $this->helper->replaceAndSave($fullPath.'/src/SkeletonClass.php', 'namespace League\Skeleton;', 'namespace '.$cVendor.'\\'.$cName);
            $search = ['league/:package_name',  '"php"',            'League\\\\Skeleton\\\\',       'League\\\\Skeleton\\\\Test\\\\'];
            $replace = [$vendor.'/'.$name,      $requireSupport,    $cVendor.'\\\\'.$cName.'\\\\',  $cVendor.'\\\\'.$cName.'\\\\Test\\\\'];
            $this->helper->replaceAndSave($fullPath.'/composer.json', $search, $replace);
        $bar->advance();

        // Add it to composer.json
        $this->info('Adding package to composer and app...');
            $this->helper->replaceAndSave(getcwd().'/composer.json', '"psr-4": {', $requirement);
            // And add it to the providers array in config/app.php
            $this->helper->replaceAndSave(getcwd().'/config/app.php', 'App\Providers\RouteServiceProvider::class,', $appConfigLine);
        $bar->advance();

        // Finished creating the package, end of the progress bar
        $bar->finish();
        $this->info('Package created successfully!');
        $this->output->newLine(2);
        $bar = null;
    }
}
