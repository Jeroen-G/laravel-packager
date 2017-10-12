<?php

namespace JeroenG\Packager;

use Illuminate\Console\Command;
use JeroenG\Packager\PackagerHelper;

/**
 * Get an existing package from a remote Github repository with its git repository.
 *
 * @package Packager
 * @author JeroenG
 * 
 **/
class PackagerPublishCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'packager:publish
                            {url : The url of the Github repository}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish your package to Github with git.';

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
        $bar = $this->helper->barSetup($this->output->createProgressBar(5));
        $bar->start();

        $this->info('Initialising Git if not already done so...');
        if ( ! file_exists('.git/')) {
            exec('git init && git add --all && git commit -m "Initial commit"');
        }
        $bar->advance();

        $this->info('Git is set up, adding the remote repository...');
        exec('git remote add origin '.$this->argument('url'));
        $bar->advance();

        $this->info('Pushing to Github...');
        exec('git push origin master');
        $bar->advance();

        // Finished creating the package, end of the progress bar
        $bar->finish();
        $this->info('Package published successfully!');
        $this->info('Go ahead and submit it to Packagist: https://packagist.org/packages/submit');
        $this->output->newLine(2);
        $bar = null;
    }
}
