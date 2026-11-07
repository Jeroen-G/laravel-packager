# Laravel Packager

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![Build Status][ico-actions]][link-actions]

This package provides you with a simple tool to set up a new package and it will let you focus on the development of the package instead of the boilerplate. If you like a visual explanation [check out this video by Jeffrey Way on Laracasts](https://laracasts.com/series/building-laracasts/episodes/3).

## Requirements

Packager 3 requires PHP 8.2 or higher and Laravel 11, 12 or 13. Use Packager 2 for older versions.

## Installation

Via Composer

```bash
composer require jeroen-g/laravel-packager --dev
```

[Package auto-discovery](https://laravel.com/docs/packages#package-discovery) takes care of registering the service provider.
Be aware that the auto-discovery also means that this package is loaded in your production environment. Therefore you may [disable auto-discovery](https://laravel.com/docs/packages#package-discovery) and instead put in your `AppServiceProvider` something like this:

```php
if ($this->app->environment('local')) {
    $this->app->register('JeroenG\Packager\PackagerServiceProvider');
}
```

Optional you can publish the configuration to provide a different service provider stub. The default is [here](https://github.com/jeroen-g/packager-skeleton).

```bash
php artisan vendor:publish --provider="JeroenG\Packager\PackagerServiceProvider"
```

## Available commands

### New
**Command:**
```bash
php artisan packager:new my-vendor my-package
```

**Result:**
The command will handle practically everything for you. It will create a packages directory, creates the vendor and package directory in it, pulls in a skeleton package, sets up composer.json and creates a service provider.

**Options:**
```bash
php artisan packager:new my-vendor my-package --i
php artisan packager:new --i
```
The package will be created interactively, allowing to configure everything in the package's `composer.json`, such as the license and package description.

```bash
php artisan packager:new my-vendor/my-package
```
Alternatively you may also define your vendor and name with a forward slash instead of a space.

**Remarks:**
The new package will be based on [this custom skeleton](https://github.com/jeroen-g/packager-skeleton). If you want to use a different package skeleton, you can either:
- (A) publish the configuration file and change the default skeleton that will be used by all `packager:new` calls.
- (B) use the flag `--skeleton="http://github.com/path/to/archive/master.zip"` with your own skeleton to use the given skeleton for this one run instead of the one in the configuration.

### Get & Git
**Command:**
```bash
php artisan packager:get https://github.com/author/repository
php artisan packager:git https://github.com/author/repository
```

**Result:**
This will register the package in the app's `composer.json` file.
If the `packager:git` command is used, the entire Git repository is cloned. If `packager:get` is used, the package will be downloaded, without a repository.

**Options:**
```bash
php artisan packager:get https://github.com/author/repository --branch=develop
php artisan packager:get https://github.com/author/repository my-vendor my-package
php artisan packager:git https://github.com/author/repository my-vendor my-package
```
It is possible to specify a branch with the `--branch` option; `packager:get` downloads the default branch of the repository otherwise. If you specify a vendor and name directly after the url, those will be used for the directory in `packages/` instead of the last two parts of the url (`author/repository` in the examples above). `packager:git` accepts any url that git can clone, such as a `git@github.com:author/repository.git` or a local `file:///path/to/author/repository.git`; provide the vendor and name when its path has fewer than two parts. Composer installs the package by the `name` in its `composer.json`.

### Tests
**Command:**
```bash
php artisan packager:tests
```

**Result:**
Packager will go through all maintaining packages (in `packages/`) and publish their tests to `tests/packages`.
Add the following to phpunit.xml (under the other testsuites) in order to run the tests from the packages:
```xml
<testsuite name="Packages">
    <directory suffix="Test.php">./tests/packages</directory>
</testsuite>
```

**Options:**
```bash
php artisan packager:tests my-vendor my-package
```

**Remarks:**
If a tests folder exists, the files will be copied to a dedicated folder in the Laravel App tests folder. This allows you to use all of Laravel's own testing functions without any hassle.

### List
**Command:**
```bash
php artisan packager:list
```

**Result:**
An overview of all packages in the `/packages` directory.

**Options:**
```bash
php artisan packager:list --git
```
The packages are displayed with information on the git status (branch, commit difference with origin) if it is a git repository.

### Remove
**Command:**
```bash
php artisan packager:remove my-vendor my-package
```

**Result:**
The `my-vendor\my-package` package is removed from the app's `composer.json` (with `composer remove`) and its directory is deleted. You will be asked whether the vendor directory should be removed as well, which only happens when it is empty.

### Enable & Disable
**Command:**
```bash
php artisan packager:disable my-vendor my-package
php artisan packager:enable my-vendor my-package
```

**Result:**
`packager:disable` removes the package from the app's `composer.json` without deleting its files, so you can temporarily work without it. `packager:enable` registers it again as a path repository and requires it.

### Publish
**Command:**
```bash
php artisan packager:publish my-vendor my-package https://github.com/my-vendor/my-package
```

**Result:**
The `my-vendor\my-package` package will be published to Github using the provided url.

## Issues with cURL SSL certificate
It turns out that, especially on Windows, there might arise some problems with the downloading of the skeleton, due to a file regarding SSL certificates missing on the OS. This can be solved by opening up your .env file and putting this in it:
```
CURL_VERIFY=false
```
Of course this means it will be less secure, but then again you are not supposed to run this package anywhere near a production environment.

## Issues with timeout
All `composer` and `git` commands that Packager runs (for example installing a package or cloning and pushing a repository) are stopped after the number of seconds in the `timeout` config variable, which defaults to 60. On slow connections or for large repositories you can increase it in config/packager.php.

## Upgrading from 2.x to 3.0
- Packager now requires PHP 8.2+ and Laravel 11, 12 or 13.
- The `packager:check` command has been removed, because the SensioLabs security checker it relied on is abandoned. Run `composer audit` in the package directory instead.
- `git` commands of `packager:git` and `packager:publish` now respect the `timeout` config variable.
- `packager:publish` pushes the current branch instead of `master`.
- `packager:get` downloads the default branch of the repository instead of `master` when no `--branch` is given.
- Bitbucket is no longer supported: the `--host` option of `packager:get` has been removed.
- If you extended Packager's classes: the `FileHandler` trait is now a `FileHandler` class implementing `FileHandlerInterface`, its path methods take the vendor and package as arguments, the interface has new methods that your own implementation must provide, and `Conveyor` is a final class that is resolved from the container. See [pull request #152](https://github.com/Jeroen-G/laravel-packager/pull/152) for all changes.

## Changelog

Please see [changelog.md](changelog.md) for what has changed recently.

## Contributing

Please see [contributing.md](contributing.md) for details and a todolist.

## Credits

- [JeroenG][link-author]
- [All Contributors][link-contributors]

## License

The EU Public License. Please see [license.md](license.md) for more information.


[ico-version]: https://poser.pugx.org/jeroen-g/laravel-packager/v/stable?format=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/jeroen-g/laravel-packager.svg?style=flat-square
[ico-actions]: https://img.shields.io/github/actions/workflow/status/Jeroen-G/laravel-packager/main.yml?branch=master&style=flat-square

[link-packagist]: https://packagist.org/packages/jeroen-g/laravel-packager
[link-downloads]: https://packagist.org/packages/jeroen-g/laravel-packager
[link-actions]: https://github.com/Jeroen-G/laravel-packager/actions
[link-author]: https://github.com/Jeroen-G
[link-contributors]: ../../contributors
