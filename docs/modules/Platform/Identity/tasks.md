# Tasks: Platform/Identity

## Sebelum Mulai

- [x] Scope dan non-scope awal ditentukan.
- [x] Dependency dan keputusan terbuka diketahui.
- [x] Focused test permission/action ditentukan.
- [x] `AGENTS.md`, `docs/SPEC.md`, `docs/ARCHITECTURE.md`, `docs/MODULE-COMMUNICATION.md`, dan WI-0004 dibaca.

## Increment 1: Dokumentasi Module

- [x] Buat README module.
  - Acceptance: boundary, public boundary, data, permission, audit, operasi, dan verifikasi utama tertulis.
  - Verification: `git diff --check`.
- [x] Buat specification module.
  - Acceptance: scope, non-scope, contract, data, authorization, audit, dependency, acceptance criteria, dan risiko terbuka tertulis.
  - Verification: `git diff --check`.
- [x] Buat implementation plan module.
  - Acceptance: increment tersusun berurutan.
  - Verification: `git diff --check`.

## Increment 2: Skeleton Module

- [x] Dry-run generator.
  - Acceptance: target file benar dan tidak ada file ditulis.
  - Verification: `php artisan module:make Platform Identity --with-tests --dry-run`.
- [x] Generate skeleton module.
  - Acceptance: `ServiceProvider.php`, `Routes/web.php`, `Database/Migrations/.gitkeep`, `Database/Seeders/IdentityDemoSeeder.php`, dan test scaffold dibuat tanpa layer kosong placeholder.
  - Verification: `php artisan module:make Platform Identity --with-tests`.

## Increment 3: Access Audit Actions

- [x] Tambahkan action sync role user.
  - Acceptance: role target tersinkron dan activity log `identity.user_roles_synced` tercatat.
  - Verification: `php artisan test tests/Feature/Modules/Platform/Identity/IdentityAccessActionTest.php`.
- [x] Tambahkan action sync permission role.
  - Acceptance: permission role tersinkron dan activity log `identity.role_permissions_synced` tercatat.
  - Verification: `php artisan test tests/Feature/Modules/Platform/Identity/IdentityAccessActionTest.php`.

## Increment 4: Shared Permission Props dan Hook

- [x] Tambahkan shared auth props roles, permissions, dan super.
  - Acceptance: Inertia shared props berisi boolean map.
  - Verification: `php artisan test tests/Feature/Modules/Platform/Identity/IdentityAccessActionTest.php`.
- [x] Tambahkan hook `use-permission.ts`.
  - Acceptance: hook menyediakan `can`, `canAny`, `hasRole`, `isSuperAdmin`, `roles`, `permissions`, dan `user`.
  - Verification: `npm run build`.

## Increment 5: Demo Seeder

- [x] Isi demo seeder identity.
  - Acceptance: seeder module memanggil baseline identity seeder dan tidak membuat data akses paralel.
  - Verification: `php artisan db:seed --class="App\\Modules\\Platform\\Identity\\Database\\Seeders\\IdentityDemoSeeder"`.

Jangan menambahkan pekerjaan baru ke checklist ini tanpa persetujuan user.
