# Changelog

All Notable changes to Packager will be documented in this file.

## Version 3

### Breaking changes
- Requires PHP 8.2+ and Laravel 11, 12 or 13
- Removed the `packager:check` command, use `composer audit` instead
- The `FileHandler` trait is replaced by the `FileHandler` class and `FileHandlerInterface`; `vendorPath()`, `tempPath()`, `packagePath()`, `checkIfPackageExists()` and `cleanUpRules()` now take the vendor and package as arguments
- `Conveyor` is a final class with `CommandRunnerInterface` and `FileHandlerInterface` constructor dependencies
- `CommandRunnerInterface::run()` accepts a working directory and the interface has a new `output()` method
- `Conveyor::downloadFromGithub()` only takes the archive url
- `FileHandler` accepts an optional Guzzle client and its path methods have typed parameters
- `ValidClassName` implements `ValidationRule` instead of the deprecated `Rule` contract
- Commands no longer receive the `Wrapping` class when they do not use it
- `git` commands respect the `timeout` config variable and `packager:publish` pushes the current branch instead of `master`

### Updated
- Moved to GitHub actions
- Added PHPStan (Larastan, level 8) static analysis and mutation testing annotations on pull requests

### Fixed
- Class name validation accepted characters like `` ` `` and `{` and rejected multibyte names
- `packager:git` cut off package names ending in `.`, `g`, `i` or `t` when stripping `.git`
- `packager:git` no longer passes the repository url through a shell
- `packager:list` threw a TypeError when showing the commit difference of a git package
- `packager:new` returned a failure exit code on success
- `packager:publish` and `packager:git` now report failures and return a failure exit code
- `packager:publish` updates an existing `origin` remote instead of failing silently
- `packager:tests` resolves paths from the application base path instead of the current working directory
- A command that times out is reported as a failure instead of crashing with an exception
- `packager:remove` without a package name shows an error again instead of crashing
- `packager:publish` reports a missing package directory
- Removing a package whose directory is already gone no longer crashes
- `packager:get` failed for repositories with uppercase letters in their name or branches with a slash
- `packager:list` shows `-` instead of 0 when the commits behind cannot be determined, and names its columns Vendor and Package
- `packager:tests` copies tests of all packages to `tests/packages/Vendor/Package` instead of a directory named `Vendor\Package`

### Changed
- All git and composer commands run through `CommandRunner`, so they respect the `timeout` config and are unit tested

### Removed
- Unused `Downloader` classes and the legacy composer/provider helpers in `Wrapping`
- StyleCI, code style is checked by ECS in GitHub Actions

## Version 2.12

### Added
- Laravel Boost AI guidelines and skill

## Version 2.11

### Added
- Laravel 12 support

## Version 2.10

### Added
- Laravel 11 support

## Version 2.7

### Added
- Allow kebab-case package and vendor names in skeletons (#135)
- PHP8 support (#133)

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
