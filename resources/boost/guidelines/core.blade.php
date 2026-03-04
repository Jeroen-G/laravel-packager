{{-- Laravel Packager Guidelines for AI Code Assistants --}}
{{-- Source: https://github.com/Jeroen-G/laravel-packager --}}

## Laravel Packager

- Laravel Packager helps scaffold and manage local Laravel packages so development can focus on package logic, not repetitive setup.
- Enable the `laravel-packager-development` skill whenever work involves package scaffolding, importing packages, `packager:*` Artisan commands, publishing package tests, or package lifecycle maintenance.
- Use the official repository as the primary reference for behavior and command support: https://github.com/Jeroen-G/laravel-packager
- Prefer `packager:new` for new packages instead of creating package folders and files manually.
- Use `packager:git` when full commit history is needed, and use `packager:get` for faster archive-based imports.
- After publishing tests with `packager:tests`, ensure the app test suite includes `tests/packages` in `phpunit.xml`.
- Before running destructive commands like `packager:remove`, verify the exact `vendor/package` target.
- Do not run package management operations in production environments.

### Usage Examples

- Create a package: `php artisan packager:new my-vendor my-package`
- Import with history: `php artisan packager:git https://github.com/author/repository`
- Import quickly (no git history): `php artisan packager:get https://github.com/author/repository`
- Publish package tests: `php artisan packager:tests`
- Remove a package (after verification): `php artisan packager:remove my-vendor my-package`
