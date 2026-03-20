# Laravel Packager

Laravel Packager helps scaffold and manage local Laravel packages so development can focus on package logic, not repetitive setup.

- Enable the `laravel-packager-development` skill whenever work involves package scaffolding, importing packages, `packager:*` Artisan commands, publishing package tests, or package lifecycle maintenance.
- Use the official repository as the primary reference for behavior: https://github.com/Jeroen-G/laravel-packager
- Before running destructive commands like `packager:remove`, verify thatthe user is certain about the exact `vendor/package` target.
- Do not run Laravel Packager operations in production environments.
