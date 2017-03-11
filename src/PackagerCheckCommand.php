<?php

namespace JeroenG\Packager;

use SensioLabs\Security\SecurityChecker;
use SensioLabs\Security\Formatters\SimpleFormatter;

use Illuminate\Console\Command;

/**
 * List all locally installed packages.
 *
 * @package Packager
 * @author JeroenG
 *
 **/
class PackagerCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'packager:check {vendor} {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the composer.lock for security vulnerabilities.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Using the SensioLabs Security Checker the composer.lock of the package is scanned for known security vulnerabilities in the dependencies.');
        $this->info('Make sure you have a composer.lock file first (for example by running "composer install" in the folder');

        $checker = new SecurityChecker();
        $formatter = new SimpleFormatter($this->getHelperSet()->get('formatter'));
        $vendor = $this->argument('vendor');
        $name = $this->argument('name');
        $lockfile = getcwd().'/packages/'.$vendor.'/'.$name.'/composer.lock';
        $vulnerabilities = $checker->check($lockfile);
        
        return $formatter->displayResults($this->output, $lockfile, $vulnerabilities);
    }
}