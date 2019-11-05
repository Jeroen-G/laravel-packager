<?php

namespace JeroenG\Packager;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\ServiceProvider;
use JeroenG\Packager\ArchiveExtractors\Manager as ExtractorManager;
use JeroenG\Packager\ArchiveExtractors\Tar;
use JeroenG\Packager\ArchiveExtractors\TarGz;
use JeroenG\Packager\ArchiveExtractors\Zip;

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
        'JeroenG\Packager\Commands\EnablePackage',
        'JeroenG\Packager\Commands\DisablePackage',
    ];

    /**
     * Bootstrap the application events.
     * @throws BindingResolutionException
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/packager.php' => config_path('packager.php'),
        ]);

        // Define
        $this->app->singleton(ExtractorManager::class, function () {
            return new ExtractorManager();
        });

        /** @var ExtractorManager $manager */
        $manager = $this->app->make(ExtractorManager::class);

        $manager->extend('zip', new Zip())
            ->extend('tar', new Tar())
            ->extend('tar.gz', new TarGz());
    }

    /**
     * Register the command.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/packager.php', 'packager');

        $this->commands($this->commands);
    }
}
