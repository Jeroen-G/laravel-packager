# Packager

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Jeroen-G/packager/badges/quality-score.png)](https://scrutinizer-ci.com/g/Jeroen-G//)
[![Latest Version](https://img.shields.io/github/release/jeroen-g/packager.svg?style=flat)](https://github.com/jeroen-g/packager/releases)
[![License](https://img.shields.io/badge/License-EUPL--1.1-blue.svg?style=flat)](license.md)

This package provides you with a simple tool to set up a new package. Nothing more, nothing less.

## Installation

Via Composer

    $ composer require jeroen-g/packager

Then add the service provider in `config/app.php`:

    'JeroenG\Packager\PackagerServiceProvider',

## Usage

``` bash
$ artisan packager:new MyVendor MyPackage
```

If you would like to use [league/skeleton](https://github.com/thephpleague/skeleton), add the `--skeleton` flag to it.

## Contributing

Please see [contributing.md](contributing.md) for details.

## License

The EU Public License. Please see [license.md](license.md) for more information.

## Changelog

Please see [changelog.md](changelog.md) for the changes made.