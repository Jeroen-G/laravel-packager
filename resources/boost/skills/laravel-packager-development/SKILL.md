---
name: laravel-packager-development
description: Use this skill when working with jeroen-g/laravel-packager to create, import, manage, test, publish, or remove local Laravel packages in a monorepo-style /packages workflow. Trigger for requests about packager:* Artisan commands, package skeleton setup, package test publishing, and package lifecycle operations.
---

# Laravel Packager Development

Use this skill to execute package lifecycle operations with `jeroen-g/laravel-packager`.

## Canonical Source

- Treat the official repository as source of truth: https://github.com/Jeroen-G/laravel-packager
- If instructions in local notes conflict with upstream docs, follow the official repository README/changelog.
- Before suggesting uncommon flags or behavior, verify they match upstream documentation.

## Scope

- Use this skill for `packager:*` workflows and local package lifecycle tasks.
- Use this skill when creating, importing, listing, testing, removing, checking, or publishing managed packages.
- Use this skill when changing skeleton behavior or packager configuration.

## Out of Scope

- Do not use this skill for generic Laravel app features unrelated to package management.
- For framework-level package architecture questions, defer to Laravel package documentation first.

## Prerequisites

- Install as dev dependency:

```bash
composer require jeroen-g/laravel-packager --dev
```

- For older Laravel versions (without auto-discovery), register:
`JeroenG\Packager\PackagerServiceProvider::class`.

- Optional config publish:

```bash
php artisan vendor:publish --provider="JeroenG\Packager\PackagerServiceProvider"
```

## Core Workflow

1. Create or import package.
2. Verify registration in app `composer.json`.
3. Publish/collect package tests into app testsuite when needed.
4. List and inspect package state.
5. Remove or publish package safely.

## Commands Reference

### Create a new package

```bash
php artisan packager:new my-vendor my-package
```

Alternatives:

```bash
php artisan packager:new my-vendor/my-package
php artisan packager:new --i
php artisan packager:new my-vendor my-package --i
```

Notes:
- `--i` enables interactive composer metadata setup.
- `--skeleton="https://.../master.zip"` overrides skeleton for one run.

### Import existing package

Download archive (no git history):

```bash
php artisan packager:get https://github.com/author/repository
```

Clone full git repository:

```bash
php artisan packager:git https://github.com/author/repository
```

Common options:

```bash
php artisan packager:get https://github.com/author/repository --branch=develop
php artisan packager:get https://github.com/author/repository my-vendor my-package
php artisan packager:git https://github.com/author/repository my-vendor my-package
```

Bitbucket download:

```bash
php artisan packager:get https://bitbucket.org/author/repository --host=bitbucket
```

### Publish package tests to app test suite

All maintained packages:

```bash
php artisan packager:tests
```

Single package:

```bash
php artisan packager:tests my-vendor my-package
```

Add in `phpunit.xml`:

```xml
<testsuite name="Packages">
    <directory suffix="Test.php">./tests/packages</directory>
</testsuite>
```

### List managed packages

```bash
php artisan packager:list
php artisan packager:list --git
```

### Remove package

```bash
php artisan packager:remove my-vendor my-package
```

Expected behavior:
- Deletes package directory.
- Removes references from app `composer.json` (and `config/app.php` in legacy setups).

### Publish package repository

```bash
php artisan packager:publish my-vendor my-package https://github.com/my-vendor/my-package
```

### Security check package

```bash
composer require sensiolabs/security-checker
php artisan packager:check my-vendor my-package
```

## AI Agent Guardrails

- Use `packager:new` for new local package scaffolding instead of manual folder creation.
- Prefer `packager:git` when repository history is required.
- Prefer `packager:get` for fast non-git imports.
- After `packager:tests`, verify `phpunit.xml` includes the `Packages` testsuite.
- Before destructive operations (`packager:remove`), verify vendor/package target explicitly.
- Do not run package management commands in production environments.
- When behavior is unclear, check the official repository README first and align command guidance with it.

## Agent Checklist

- Confirm the request is in scope (`packager:*` or package lifecycle work).
- Choose the right command path (`new`, `get`, or `git`) based on required history and speed.
- Validate side effects in config files (`composer.json`, `phpunit.xml`, legacy `config/app.php` when relevant).
- Before destructive actions, restate and verify the exact `vendor/package`.
- If uncertain, resolve ambiguity against the official repository docs.

## Troubleshooting

### SSL/cURL download issues (common on Windows)

Set:

```env
CURL_VERIFY=false
```

Use only in local/dev contexts.

### Timeout issues

Adjust timeout in `config/packager.php` after publishing config.
