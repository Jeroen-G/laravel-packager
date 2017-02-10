<?php

namespace JeroenG\Packager;

use Illuminate\Console\Command;
use JeroenG\Packager\PackagerHelper;

/**
 * remove an existing package.
 *
 * @package Packager
 * @author JeroenG
 * 
 **/
class PackagerRemoveCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'packager:remove {vendor} {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove an existing package.';

    /**
     * Packager helper class.
     * @var object
     */
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
        $bar = $this->helper->barSetup($this->output->createProgressBar(4));
        $bar->start();

        // Common variables
        $vendor = $this->argument('vendor');
        $name = $this->argument('name');
        $path = getcwd().'/packages/';
        $fullPath = $path.$vendor.'/'.$name;
        $requirement = '"'.$vendor.'\\\\'.$name.'\\\\": "packages/'.$vendor.'/'.$name.'/src",';
        $appConfigLine = $vendor.'\\'.$name.'\\'.$name.'ServiceProvider::class,';

        // Start removing the package
        $this->info('Removing package '.$vendor.'\\'.$name.'...');
        $bar->advance();

        // remove the package directory
        $this->info('Removing packages directory...');
            $this->helper->removeDir($fullPath);
        $bar->advance();

        // Remove the vendor directory, if agreed to
        if ($this->confirm('Do you want to remove the vendor directory? [y|N]')) {
            $this->info('removing vendor directory...');
            $this->helper->removeDir($path.$vendor);
        } else {
            $this->info('Continuing...');
        }
        $bar->advance();

        // Remove it from composer.json and app config
        $this->info('Removing package from composer and app config...');
            $this->helper->replaceAndSave(getcwd().'/composer.json', $requirement, '');
            $this->helper->replaceAndSave(getcwd().'/config/app.php', $appConfigLine, '');
        $bar->advance();

        // Finished removing the package, end of the progress bar
        $bar->finish();
        $this->info('Package removed successfully!');
        $this->output->newLine(2);
        $bar = null;

        // Composer dump-autoload to identify new MyPackageServiceProvider
        $this->helper->dumpAutoloads();
    }
}
