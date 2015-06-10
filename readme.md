# Laravel Packager

[![Latest Version](https://img.shields.io/github/release/jeroen-g/laravel-packager.svg?style=flat)](https://github.com/jeroen-g/laravel-packager/releases)
[![License](https://img.shields.io/badge/License-EUPL--1.1-blue.svg?style=flat)](license.md)

This package provides you with a simple tool to set up a new package. Nothing more, nothing less.

## Installation

Via Composer

    $ composer require jeroen-g/laravel-packager

Then add the service provider in `config/app.php`:

    'JeroenG\Packager\PackagerServiceProvider',

## Usage

### New package
The command will handle practically everything for you. It will create a packages directory, creates the vendor and package directory in it, pulls in a skeleton package, sets up composer.json, creates a service provider, registers the package in config/app.php and the app's composer.json. So you can start right away with only this command:
``` bash
$ artisan packager:new MyVendor MyPackage
```

The new package will be based on [league/skeleton](https://github.com/thephpleague/skeleton), plus a Laravel service provider.

### Existing package
If you already have your package on Github, it is possible to download that:
``` bash
$ artisan packager:get https://github.com/author/repository
```
This will too register the package in config/app.php and the app's composer.json file.

## Contributing

Please see [contributing.md](contributing.md) for details.

## License

The EU Public License. Please see [license.md](license.md) for more information.

## Changelog

Please see [changelog.md](changelog.md) for the changes made.