# Laravel Packager

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![Software License][ico-license]](license.md)

This package provides you with a simple tool to set up a new package and it will let you focus on the development of the package instead of the boilerplate.

## Installation

Via Composer

```bash
$ composer require jeroen-g/laravel-packager
```

If you do not run Laravel 5.5 (or higher), then add the service provider in `config/app.php`:

```php
JeroenG\Packager\PackagerServiceProvider::class,
```

If you do run the package on Laravel 5.5+, [package auto-discovery](https://medium.com/@taylorotwell/package-auto-discovery-in-laravel-5-5-ea9e3ab20518) takes care of the magic of adding the service provider.

## Available commands

### New
**Command:**
```bash
$ php artisan packager:new MyVendor MyPackage
```

**Result:**
The command will handle practically everything for you. It will create a packages directory, creates the vendor and package directory in it, pulls in a skeleton package, sets up composer.json, creates a service provider, registers the package in config/app.php and the app's composer.json.

**Options:**
```bash
$ php artisan packager:new MyVendor MyPackage --i
$ php artisan packager:new --i
```
The package will be created interactively, allowing to configure everything in the package's `composer.json`, such as the license and package description.

**Remarks:**
The new package will be based on [league/skeleton](https://github.com/thephpleague/skeleton), plus a Laravel service provider.

### Get & Git
**Command:**
``` bash
$ php artisan packager:get https://github.com/author/repository
$ php artisan packager:git https://github.com/author/repository
```

**Result:**
This will register the package in `config/app.php` and in the app's `composer.json` file.
If the `packager:git` command is used, the entire Git repository is cloned. If `packager:get` is used, the package will be downloaded, without a repository.

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

### Remove
**Command:**
```bash
$ php artisan packager:remove MyVendor MyPackage
```

**Result:**
The `MyVendor\MyPackage` package is deleted, including its references in `composer.json` and `config/app.php`.

## Issues with cURL SSL certificate
It turns out that, especially on windows, there might arise some problems with the downloading of the skeleton, due to a file regarding SSL certificates missing on the OS. This can be solved by opening up your .env file and putting this in it:
```
CURL_VERIFY=false
```

## Changelog

Please see [changelog.md](changelog.md) for what has changed recently.

## Contributing

Please see [contributing.md](contributing.md) for details.

## Credits

- [JeroenG][link-author]
- [All Contributors][link-contributors]

## License

The EU Public License. Please see [license.md](license.md) for more information.


[ico-version]: https://img.shields.io/packagist/v/jeroen-g/laravel-packager.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-EUPL-yellow.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/jeroen-g/laravel-packager.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/jeroen-g/laravel-packager
[link-downloads]: https://packagist.org/packages/jeroen-g/laravel-packager
[link-author]: https://github.com/Jeroen-G
[link-contributors]: ../../contributors]
