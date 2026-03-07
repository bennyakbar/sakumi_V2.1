# Tasks: foundation-setup

## Prerequisite
- [x] Ensure implementation repo contains Laravel app baseline (`composer.json`, `artisan`, `app/`, `routes/`, `config/`, `tests/`).

## Implementation
- [x] Install/configure auth scaffolding
  - Target files: `composer.json`, `routes/web.php`, `resources/views/auth/*`, `app/Http/Controllers/Auth/*`
  - Commands: `composer require laravel/breeze --dev`, `php artisan breeze:install blade`
  - Acceptance: login/logout/password reset routes exist and render successfully.
- [x] Install/configure RBAC package and seed roles
  - Target files: `composer.json`, `config/permission.php`, `database/seeders/RolePermissionSeeder.php`
  - Commands: `composer require spatie/laravel-permission`, `php artisan vendor:publish --provider=\"Spatie\\Permission\\PermissionServiceProvider\"`
  - Roles to seed: `super_admin`, `bendahara`, `kepala_sekolah`, `operator_tu`, `auditor`
  - Acceptance: roles are created and assignable to users.
- [x] Add base middleware skeletons and registration
  - Target files: `app/Http/Middleware/CheckRole.php`, `app/Http/Middleware/AuditLog.php`, `app/Http/Middleware/CheckInactivity.php`, bootstrap middleware registration file for current Laravel version
  - Acceptance: middleware aliases are registered and can be applied to route groups.
- [x] Prepare `.env.example` baseline for environments
  - Target files: `.env.example`, optional `config/*.php` updates
  - Required keys: app URL/debug/env, DB runtime user, queue connection, cache/session driver, mail placeholders
  - Acceptance: new environment can bootstrap with only `.env` copy + key generation.
- [x] Configure CI test workflow
  - Target files: `.github/workflows/ci.yml` (or existing CI provider file)
  - Pipeline stages: install deps, lint/static checks (if configured), run tests, fail on non-zero exit
  - Acceptance: CI executes on push/PR and reports pass/fail status.

## Validation
- [x] Add auth and authorization smoke tests
  - Target files: `tests/Feature/Auth/*`, `tests/Feature/Authorization/*`
  - Minimum cases:
    - guest can view login page
    - valid user can login/logout
    - unauthorized role gets forbidden on protected route
    - authorized role can access protected route
- [ ] Verify CI pipeline passes
  - Command baseline: `php artisan test`
  - Acceptance: tests pass locally and CI status is green.
