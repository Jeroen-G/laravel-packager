# Laravel Packager

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![Build Status][ico-travis]][link-travis]
[![StyleCI][ico-styleci]][link-styleci]

This package provides you with a simple tool to set up a new package and it will let you focus on the development of the package instead of the boilerplate. If you like a visual explanation [check out this video by Jeffrey Way on Laracasts](https://laracasts.com/series/building-laracasts/episodes/3).

## Installation

Via Composer

```bash
$ composer require jeroen-g/laravel-packager --dev
```

If you do not run Laravel 5.5 (or higher), then add the service provider in `config/app.php`:

```php
JeroenG\Packager\PackagerServiceProvider::class,
```

If you do run the package on Laravel 5.5+, [package auto-discovery](https://medium.com/@taylorotwell/package-auto-discovery-in-laravel-5-5-ea9e3ab20518) takes care of the magic of adding the service provider.
Be aware that the auto-discovery also means that this package is loaded in your production environment. Therefore you may [disable auto-discovery](https://laravel.com/docs/5.5/packages#package-discovery) and instead put in your `AppServiceProvider` something like this:

```php
if ($this->app->environment('local')) {
    $this->app->register('JeroenG\Packager\PackagerServiceProvider');
}
```

Optional you can publish the configuration to provide a different service provider stub. The default is [here](https://github.com/jeroen-g/packager-skeleton).

```bash
$ php artisan vendor:publish --provider="JeroenG\Packager\PackagerServiceProvider"
```

## Available commands

### New
**Command:**
```bash
$ php artisan packager:new MyVendor MyPackage
```

**Result:**
The command will handle practically everything for you. It will create a packages directory, creates the vendor and package directory in it, pulls in a skeleton package, sets up composer.json and creates a service provider.

**Options:**
```bash
$ php artisan packager:new MyVendor MyPackage --i
$ php artisan packager:new --i
```
The package will be created interactively, allowing to configure everything in the package's `composer.json`, such as the license and package description.

```bash
$ php artisan packager:new MyVendor/MyPackage
```
Alternatively you may also define your vendor and name with a forward slash instead of a space.

**Remarks:**
The new package will be based on [this custom skeleton](https://github.com/jeroen-g/packager-skeleton). If you want to use a different package skeleton, you can either:
- (A) publish the configuration file and change the default skeleton that will be used by all `packager:new` calls.
- (B) use the flag `--skeleton="http://github.com/path/to/archive/master.zip"` with your own skeleton to use the given skeleton for this one run instead of the one in the configuration.

### Get & Git
**Command:**
``` bash
$ php artisan packager:get https://github.com/author/repository
$ php artisan packager:git https://github.com/author/repository
```

**Result:**
This will register the package in the app's `composer.json` file.
If the `packager:git` command is used, the entire Git repository is cloned. If `packager:get` is used, the package will be downloaded, without a repository. This also works with Bitbucket repositories, but you have to provide the flag `--host=bitbucket` for the `packager:get` command.

**Options:**
```bash
$ php artisan packager:get https://github.com/author/repository --branch=develop
$ php artisan packager:get https://github.com/author/repository MyVendor MyPackage
$ php artisan packager:git https://github.com/author/repository MyVendor MyPackage
```
It is possible to specify a branch with the `--branch` option. If you specify a vendor and name directly after the url, those will be used instead of the pieces of the url.

### Tests
**Command:**
```bash
$ php artisan packager:tests
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
$ php artisan packager:tests MyVendor MyPackage
```

**Remarks:**
If a tests folder exists, the files will be copied to a dedicated folder in the Laravel App tests folder. This allows you to use all of Laravel's own testing functions without any hassle.

### List
**Command:**
```bash
$ php artisan packager:list
```

**Result:**
An overview of all packages in the `/packages` directory.

**Options:**
```bash
$ php artisan packager:list --git
```
The packages are displayed with information on the git status (branch, commit difference with origin) if it is a git repository.

### Remove
**Command:**
```bash
$ php artisan packager:remove MyVendor MyPackage
```

**Result:**
The `MyVendor\MyPackage` package is deleted, including its references in `composer.json` and `config/app.php`.

### Publish
**Command:**
```bash
$ php artisan packager:publish MyVendor MyPackage https://github.com/myvendor/mypackage
```

**Result:**
The `MyVendor\MyPackage` package will be published to Github using the provided url.

### Check
**Command:**
```bash
$ php artisan packager:check MyVendor MyPackage
```

**Result:**
The `MyVendor\MyPackage` package will be checked for security vulnerabilities using SensioLabs security checker.

**Remarks**
You first need to run

```bash
$ composer require sensiolabs/security-checker
```


## Issues with cURL SSL certificate
It turns out that, especially on Windows, there might arise some problems with the downloading of the skeleton, due to a file regarding SSL certificates missing on the OS. This can be solved by opening up your .env file and putting this in it:
```
CURL_VERIFY=false
```
Of course this means it will be less secure, but then again you are not supposed to run this package anywhere near a production environment.

## Issues with timeout
If you are having problems with timeouts when creating new packages, you can now change the config variable timeout in config/packager.php to fix this.

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
[ico-travis]: https://img.shields.io/travis/Jeroen-G/laravel-packager/master.svg?style=flat-square
[ico-styleci]: https://styleci.io/repos/37218114/shield

[link-packagist]: https://packagist.org/packages/jeroen-g/laravel-packager
[link-downloads]: https://packagist.org/packages/jeroen-g/laravel-packager
[link-travis]: https://travis-ci.org/Jeroen-G/laravel-packager
[link-styleci]: https://styleci.io/repos/37218114
[link-author]: https://github.com/Jeroen-G
[link-contributors]: ../../contributors
