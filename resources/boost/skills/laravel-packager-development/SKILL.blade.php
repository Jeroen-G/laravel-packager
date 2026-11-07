---
name: laravel-packager-development
description: Use this skill when working with jeroen-g/laravel-packager to create, import, manage, test, publish, or remove local Laravel packages in a monorepo-style workflow. Trigger for requests about packager:* Artisan commands, package skeleton setup, package test publishing, and package lifecycle operations.
---
@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Laravel Packager Development

## When to apply

Activate whenever referencing Packager, local packages or `jeroen-g/laravel-packager`.

- Use this skill for `packager:*` workflows and local package lifecycle tasks.
- Use this skill when creating, importing, listing, testing, enabling, disabling, removing, or publishing managed packages.
- Use this skill when changing skeleton behavior or packager configuration.
- Do not use this skill for generic Laravel app features unrelated to package management.
- For framework-level package architecture questions, defer to Laravel package documentation first.

## Canonical Source

- Treat the official repository as source of truth: https://github.com/Jeroen-G/laravel-packager
- If instructions in local notes conflict with upstream docs, follow the official repository README/changelog.
- Before suggesting uncommon flags or behavior, verify they match upstream documentation.

## Workflow

1. Create or import package.
2. Verify registration in app `composer.json`.
3. Publish/collect package tests into app testsuite when needed.
4. List and inspect package state.
5. Remove or publish package safely.

## Commands Reference

For all commands, assume `my-vendor` and `my-package` to be placeholders for names provided by the user.

### Create a new package

```bash
{{ $assist->artisanCommand('packager:new my-vendor my-package') }}
```

Notes:
- `--i` enables interactive composer metadata setup.
- `--skeleton="https://.../master.zip"` overrides skeleton for one run.

### Import existing package

Clone it with its git history:

```bash
{{ $assist->artisanCommand('packager:git https://github.com/username/repository my-vendor my-package') }}
```

Or download it from GitHub without git history (`--branch` defaults to the repository's default branch):

```bash
{{ $assist->artisanCommand('packager:get https://github.com/username/repository my-vendor my-package --branch=main') }}
```

### Temporarily disable or re-enable a package

```bash
{{ $assist->artisanCommand('packager:disable my-vendor my-package') }}
{{ $assist->artisanCommand('packager:enable my-vendor my-package') }}
```

`packager:disable` removes the package from app `composer.json` but keeps its files.

### Publish package tests

```bash
{{ $assist->artisanCommand('packager:tests my-vendor my-package') }}
```

Add in `phpunit.xml`:

```xml
<testsuite name="Packages">
    <directory suffix="Test.php">./tests/packages</directory>
</testsuite>
```

### List managed packages

```bash
{{ $assist->artisanCommand('packager:list') }}
```

### Remove package

```bash
{{ $assist->artisanCommand('packager:remove my-vendor my-package') }}
```

Expected behavior:
- Deletes package directory.
- Removes the package from app `composer.json` with `composer remove`.
- Optionally removes the vendor directory when it is empty.

### Publish package repository

```bash
{{ $assist->artisanCommand('packager:publish my-vendor my-package https://github.com/author/repository') }}
```

## Troubleshooting

### SSL/cURL download issues (common on Windows)

Set in the local `.env`:

```env
CURL_VERIFY=false
```

### Timeout issues

Adjust timeout in `config/packager.php` after publishing config.
