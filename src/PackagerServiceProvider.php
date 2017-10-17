<?php

namespace JeroenG\Packager;

use Illuminate\Support\ServiceProvider;

/**
 * This is the service provider.
 *
 * Place the line below in the providers array inside app/config/app.php
 * <code>'JeroenG\Packager\PackagerServiceProvider',</code>
 *
 * @author JeroenG
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
        'JeroenG\Packager\Commands\NewPackage',
        'JeroenG\Packager\Commands\RemovePackage',
        'JeroenG\Packager\Commands\GetPackage',
        'JeroenG\Packager\Commands\GitPackage',
        'JeroenG\Packager\Commands\ListPackages',
        'JeroenG\Packager\Commands\MoveTests',
        'JeroenG\Packager\Commands\CheckPackage',
        'JeroenG\Packager\Commands\PublishPackage',
    ];

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/packager.php' => config_path('packager.php'),
        ]);
    }

    /**
     * Register the command.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/config/packager.php', 'packager');

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
