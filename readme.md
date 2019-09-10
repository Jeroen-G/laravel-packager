# Laravel Packager

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![Build Status][ico-travis]][link-travis]
[![StyleCI][ico-styleci]][link-styleci]

This package provides you with a simple tool to set up a new package and it will let you focus on the development of the package instead of the boilerplate.

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

**Remarks:**
The new package will be based on [this custom skeleton](https://github.com/jeroen-g/packager-skeleton).

### Get & Git
**Command:**
``` bash
$ php artisan packager:get https://github.com/author/repository
$ php artisan packager:git https://github.com/author/repository
```

**Result:**
This will register the package in the app's `composer.json` file.
If the `packager:git` command is used, the entire Git repository is cloned (you can optionally specify the branch/version to clone using the `--branch` option). If `packager:get` is used, the package will be downloaded, without a repository. This also works with Bitbucket repositories, but you have to provide the flag `--host=bitbucket` for the `packager:get` command.

**Options:**
```bash
$ php artisan packager:get https://github.com/author/repository --branch=develop
$ php artisan packager:get https://github.com/author/repository MyVendor MyPackage
$ php artisan packager:git https://github.com/author/repository MyVendor MyPackage
$ php artisan packager:git github-user/github-repo --branch=dev-mybranch
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

## Managing dependencies
When you install a new package using `packager:new`, `packager:get` or `packager:git`, the package dependencies will automatically be installed into the parent project's `vendor/` folder.

Installing or updating package dependencies should *not* be done directly from the `packages/` folder.

When you've edited the `composer.json` file in your package folder, you should run `composer update` from the root folder of the parent project. 

If your package was installed using the `packager:git` command, any changes you make to the package's `composer.json` file will not be detected by the parent project until the changes have been committed.

## Issues with cURL SSL certificate
It turns out that, especially on Windows, there might arise some problems with the downloading of the skeleton, due to a file regarding SSL certificates missing on the OS. This can be solved by opening up your .env file and putting this in it:
```
CURL_VERIFY=false
```
Of course this means it will be less secure, but then again you are not supposed to run this package anywhere near a production environment.

## Extensions
DelveFore started to work on a cool project to use various Artisan `make:` commands for the packages, [check it out](https://github.com/DelveFore/laravel-packager-hermes)!

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
[link-contributors]: ../../contributors]
