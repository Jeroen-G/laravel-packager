# Changelog

All Notable changes to Packager will be documented in this file.

## Version 2.7

### Added
- Allow kebab-case package and vendor names in skeletons (#135)

## Version 2.6

### Added
- The `timeout` configuration setting.
- Composer scripts for testing this package. 
- Use `vendor/package` definition in the remove command. 

### Updated
- replacing references in all files of a skeleton instead of only the hardcoded files.

### Fixed
- Not the whole package vendor is removed if there are still files left.
- `symlink` option is set to true as default for repositories in `composer.json`

## Version 2.5

### Added
- A `--skeleton` flag for the `packager:new` command (#105).

### Updated
- Support for Laravel 7 and PHPUnit 9.
- `packager:new` and `packager:remove` now also supports separating vendor and name with a forward slash.

### Fixed
- `packager:new` now also supports separating vendor and name with a forward slash.
- vendor-name and package-name not converted to StudlyCase with `packager:new`

## Version 2.4

### Added
- A `--git` flag for `packager:list`
- Skeletons may now be `.tar.gz` or `.tar` next to `.zip`.

### Updated
- Skeletons may now have different names.
- Default Skeleton is updated for Laravel 6.
- The CURL_VERIFY flag is retrieved from the config instead of the `.env`.

## Version 2.3

### Updated
- Updated requirements for Laravel 6.

### Fixed
- A bug where packages were not sluggified properly for Composer.

## Version 2.2

### Added
- package:enable and package:disable commands.
- ext-zip as a requirement.
- Check if git clone was successful before continuing installation of a package.

### Updated
- PHPUnit requirement from 7 to 8.
- Convert vendor and package to camel case when installing package from git.

## Version 2.1

### Added
- package:enable and package:disable commands.

## Version 2.0

### Added
- Tests, TravisCI, StyleCI.
- The command `packager:publish` to bring your package to Github.
- Default Laravel-style skeleton, but with option to use your own.
- Support for get/git of bitbucket repositories.

### Updated
- Moved `sensiolabs/security-checker` to suggested requirements.
- Config folder moved up one level.
- Refactored core code.
- More comprehensive readme, added todo's in the contributing file.

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
