<?php

namespace JeroenG\Packager;

use Illuminate\Support\ServiceProvider;

/**
 * This is the service provider.
 *
 * Place the line below in the providers array inside app/config/app.php
 * <code>'JeroenG\Packager\PackagerServiceProvider',</code>
 *
 * @package Packager
 * @author JeroenG
 * 
 **/
class PackagerServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * The console commands.
     *
     * @var bool
     */
    protected $commands = [
        'JeroenG\Packager\PackagerNewCommand',
        'JeroenG\Packager\PackagerRemoveCommand',
        'JeroenG\Packager\PackagerGetCommand',
        'JeroenG\Packager\PackagerGitCommand',
        'JeroenG\Packager\PackagerListCommand',
        'JeroenG\Packager\PackagerTestsCommand',
        'JeroenG\Packager\PackagerCheckCommand',
    ];

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        // Not really anything to boot.
    }

    /**
     * Register the command.
     *
     * @return void
     */
    public function register()
    {
        $this->commands($this->commands);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['packager'];
    }
}
