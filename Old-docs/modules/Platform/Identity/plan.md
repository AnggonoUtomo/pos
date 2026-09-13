# Implementation Plan: Platform/Identity

## Scope

Pekerjaan ini menyelesaikan baseline identitas dan akses untuk fase awal POS: auth internal, permission baseline, shared permission props, hook permission frontend, dan audit perubahan role/permission.

## Increment 1: Dokumentasi Module

- Perubahan:
  - `docs/modules/Platform/Identity/README.md`
  - `docs/modules/Platform/Identity/specification.md`
  - `docs/modules/Platform/Identity/plan.md`
  - `docs/modules/Platform/Identity/tasks.md`
- Dependency:
  - WI-0004 aktif.
- Acceptance:
  - boundary, non-scope, acceptance criteria, dependency, risiko, dan verifikasi tertulis.
- Verifikasi:
  - `git diff --check`

## Increment 2: Skeleton Module

- Perubahan:
  - generate `app/Modules/Platform/Identity/` dengan generator.
- Dependency:
  - ModuleRuntime tersedia.
- Acceptance:
  - `ServiceProvider.php`, `Routes/web.php`, `Database/Migrations/.gitkeep`, dan `Database/Seeders/IdentityDemoSeeder.php` tersedia.
- Verifikasi:
  - `php artisan module:make Platform Identity --with-tests --dry-run`
  - `php artisan module:make Platform Identity --with-tests`
  - `php artisan test --filter=MakeModuleCommandTest`

## Increment 3: Access Audit Actions

- Perubahan:
  - `SyncUserRoles`
  - `SyncRolePermissions`
  - feature test activity log.
- Dependency:
  - Spatie Permission dan Activitylog tersedia.
- Acceptance:
  - perubahan role user tercatat;
  - perubahan permission role tercatat;
  - audit menyimpan before/after tanpa secret.
- Verifikasi:
  - `php artisan test tests/Feature/Modules/Platform/Identity/IdentityAccessActionTest.php`

## Increment 4: Shared Permission Props dan Hook

- Perubahan:
  - `HandleInertiaRequests`
  - `resources/js/types/index.ts`
  - `resources/js/hooks/use-permission.ts`
- Dependency:
  - role/permission user tersedia.
- Acceptance:
  - `auth.roles`, `auth.permissions`, dan `auth.superSystem` tersedia;
  - hook menyediakan `can`, `canAny`, dan `hasRole`;
  - build frontend lulus.
- Verifikasi:
  - `php artisan test tests/Feature/Modules/Platform/Identity/IdentityAccessActionTest.php`
  - `npm run build`

## Increment 5: Demo Seeder

- Perubahan:
  - `IdentityDemoSeeder`.
- Dependency:
  - `IdentityAccessSeeder` tersedia.
- Acceptance:
  - seeder module memanggil baseline identity seeder.
- Verifikasi:
  - `php artisan db:seed --class="App\\Modules\\Platform\\Identity\\Database\\Seeders\\IdentityDemoSeeder"`

## Batas Berhenti

Pekerjaan berhenti pada baseline backend dan shared frontend permission. UI access matrix lengkap, warehouse access final, dan CRUD user/role admin dikerjakan pada work-item berikutnya.

## Rollback

- Revert commit WI-0004.
- Rollback migration package hanya jika environment disposable.
- Jangan menghapus user/role production tanpa prosedur data migration eksplisit.
