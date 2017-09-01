# Changelog

All Notable changes to Packager will be documented in this file.

## Version 1.6.x

### Added
- Compatability for Laravel 5.5.

## Version 1.5.x

### Added
- The `new` command now also accepts an option `--i` To interactively make a package and change all Skeleton placholders.
- Composer autoloads are dumped after installing or creating a package.
- The `packager:check` function to check the composer lockfile for security vulnerabilities.

### Fixed
- Replacing of the Skeleton placeholders.
- Replaced `packager:tests` path creation function.

### Updated
- The readme is now up to date with information on all commands.

## Version 1.4

### Added
- Added command to move package test files to the Laravel app tests folder.

### Fixed
- 'App' is no longer showing in the package list command.

## Version 1.3

### Added
- Added command to list locally installed packages.

## Version 1.2

### Added
- Added command to download package with its git repository.

## Version 1.1

### Added
- Added command to remove packages.

### Fixed
- Missing certificate for cURL.
- Fixed replacing in composer.json file.
- More flexible naming of vendor and package name.

## Version 1.0
First stable release. Everything is brand new!