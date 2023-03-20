<?php

declare(strict_types=1);

namespace JeroenG\Packager;

use Illuminate\Support\ServiceProvider;
use JeroenG\Packager\Commands\DisablePackage;
use JeroenG\Packager\Commands\EnablePackage;
use JeroenG\Packager\Commands\GetPackage;
use JeroenG\Packager\Commands\GitPackage;
use JeroenG\Packager\Commands\ListPackages;
use JeroenG\Packager\Commands\MoveTests;
use JeroenG\Packager\Commands\NewPackage;
use JeroenG\Packager\Commands\PublishPackage;
use JeroenG\Packager\Commands\RemovePackage;

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
    protected array $commands = [
        NewPackage::class,
        RemovePackage::class,
        GetPackage::class,
        GitPackage::class,
        ListPackages::class,
        MoveTests::class,
        PublishPackage::class,
        EnablePackage::class,
        DisablePackage::class,
    ];

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/packager.php' => config_path('packager.php'),
        ]);

        $this->app->bind(CommandRunnerInterface::class, CommandRunner::class);
        $this->app->bind(FileHandlerInterface::class, FileHandler::class);
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/packager.php', 'packager');

        $this->commands($this->commands);
    }
}
