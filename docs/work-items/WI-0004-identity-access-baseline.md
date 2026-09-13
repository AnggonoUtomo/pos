# WI-0004: Baseline Identitas dan Akses

Status: Selesai

## Tujuan

Menyiapkan auth internal, Spatie Permission, public registration off, role/permission dasar, dan akses gudang user.

## Rujukan

- `docs/adr/ADR-0005-auth-permission-and-activity-log.md`
- `docs/SPEC.md`
- `docs/QA-AUTOMATION.md`

## Modul Target

- Category: Platform
- Module: Identity
- Namespace/path: `app/Modules/Platform/Identity`
- Jenis pekerjaan: auth internal, role, permission, dan audit access

## Scope

Masuk scope:

- Install/config Spatie Permission jika belum tersedia.
- Disable public registration.
- Seed super admin.
- Role dan permission baseline.
- Struktur akses gudang user jika warehouse module siap atau placeholder contract.

Di luar scope:

- UI access matrix lengkap.
- SSO atau external auth.

## Checklist Sebelum Coding

- [x] Starter kit auth teridentifikasi.
- [x] Route register diketahui.
- [x] Permission naming disetujui.

## Rencana Increment

| Increment | Fokus | Checklist Sebelum | Checklist Sesudah | QA automated | Status |
| --- | --- | --- | --- | --- | --- |
| INC-01 | Disable public registration | - [x] Route register dan page register ditemukan | - [x] Register publik tidak bisa diakses<br>- [x] Link register starter kit dihapus | `php artisan test --filter=RegistrationTest` | Selesai |
| INC-02 | Spatie Permission baseline | - [x] Nama role/permission disepakati | - [x] Package terpasang<br>- [x] Config/migration tersedia<br>- [x] Role/permission seed dan middleware berjalan | `php artisan test --filter=PermissionBaselineTest` | Selesai |
| INC-03 | Activity log identity | - [x] Event perubahan akses ditentukan | - [x] Package terpasang<br>- [x] Config/migration tersedia<br>- [x] Activity log identity event dapat dicatat<br>- [x] Logging perubahan role/permission pada action admin | `php artisan test tests/Feature/Modules/Platform/Identity/IdentityAccessActionTest.php` | Selesai |
| INC-04 | Shared permission props dan hook | - [x] Pola `usePermission()` disepakati | - [x] `auth.roles`, `auth.permissions`, dan `auth.super` tersedia<br>- [x] Hook `use-permission.ts` tersedia | `npm run build`, focused Identity test | Selesai |
| INC-05 | Module scaffold dan demo seeder Identity | - [x] Struktur `app/Modules` disepakati<br>- [x] Root `Modules/` legacy dihapus | - [x] Module `Platform/Identity` tersedia<br>- [x] Demo seeder module memanggil baseline identity seeder | `php artisan module:make Platform Identity --with-tests --dry-run`, `php artisan db:seed --class=...IdentityDemoSeeder` | Selesai |

## Kriteria Penerimaan

- [x] Public registration tidak bisa diakses.
- [x] Flow admin-created user tersedia atau direncanakan jelas.
- [x] Role/permission dasar berjalan.
- [x] Permission middleware/policy baseline tersedia.
- [x] Activity log mencatat perubahan role/permission penting.
- [x] Shared auth props menyediakan role, permission, dan super flag untuk frontend.
- [x] Hook permission frontend tersedia untuk UX guard.

## Verifikasi

- [x] `php artisan test --filter=Auth`
- [x] `php artisan test --filter=PermissionBaselineTest`
- [x] `php artisan test --filter=Identity`
- [x] `php artisan test`
- [x] Pemeriksaan manual route register nonaktif
- [x] `npm run build` jika UI berubah

## Catatan Implementasi

- WI-0001 menemukan public registration masih aktif.
- WI-0001 menemukan Spatie Permission dan Spatie Activitylog belum terpasang.
- Langkah awal WI-0004 memasang package Spatie Permission dan Activitylog sebelum disable public registration dan konfigurasi role/permission.
- Package terpasang:
  - `spatie/laravel-permission` 8.3.0.
  - `spatie/laravel-activitylog` 5.1.1.
- Config dan migration package dipublish ke `config/permission.php`, `config/activitylog.php`, dan `database/migrations`.
- `App\Models\User` memakai trait `Spatie\Permission\Traits\HasRoles`.
- Middleware alias `role`, `permission`, dan `role_or_permission` tersedia di `bootstrap/app.php`.
- Seeder `IdentityAccessSeeder` membuat role `super-admin`, permission baseline dari `docs/SPEC.md`, dan super admin lokal berbasis env:
  - `SEED_SUPER_ADMIN_EMAIL`, default `admin@example.com`.
  - `SEED_SUPER_ADMIN_NAME`, default `Super Admin`.
  - `SEED_SUPER_ADMIN_PASSWORD`, default `password`.
- Register route `GET|POST register` dimatikan, dan link register di welcome/login starter kit dihapus.
- Module canonical `app/Modules/Platform/Identity` dibuat melalui generator module.
- Root folder legacy `Modules/` dihapus karena canonical module berada di `app/Modules`.
- Action `SyncUserRoles` mencatat activity log `identity.user_roles_synced` dengan before/after role.
- Action `SyncRolePermissions` mencatat activity log `identity.role_permissions_synced` dengan before/after permission.
- Shared Inertia auth props sekarang menyediakan `user`, `roles`, `permissions`, dan `super`.
- Hook frontend `resources/js/hooks/use-permission.ts` menyediakan `can`, `canAny`, `hasRole`, `isSuperAdmin`, `roles`, `permissions`, dan `user`.
- `IdentityDemoSeeder` memanggil `IdentityAccessSeeder` agar demo data akses tetap satu sumber.

## Bukti

```text
Command: composer require spatie/laravel-permission spatie/laravel-activitylog
Hasil: PASS
Catatan: Permission 8.3.0 dan Activitylog 5.1.1 terpasang.

Command: php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
Hasil: PASS
Catatan: config/permission.php dan migration permission tables terbit.

Command: php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider"
Hasil: PASS
Catatan: config/activitylog.php dan migration activity_log terbit.

Command: php artisan migrate
Hasil: PASS
Catatan: permission tables dan activity_log table berjalan di database lokal.

Command: php artisan db:seed --class=IdentityAccessSeeder
Hasil: PASS
Catatan: role super-admin dan permission baseline tersedia.

Command: php artisan permission:show
Hasil: PASS
Catatan: super-admin memiliki 8 permission baseline.

Command: php artisan route:list --except-vendor
Hasil: PASS
Catatan: 21 route baseline tampil; route register tidak ada.

Command: php artisan test --filter=RegistrationTest
Hasil: PASS
Catatan: 2 tests, 3 assertions.

Command: php artisan test --filter=PermissionBaselineTest
Hasil: PASS
Catatan: 3 tests, 7 assertions.

Command: php artisan test --filter=Identity
Hasil: PASS
Catatan: 6 tests, 23 assertions.

Command: php artisan test tests/Feature/Modules/Platform/Identity/IdentityAccessActionTest.php
Hasil: PASS
Catatan: 3 tests, 17 assertions.

Command: php artisan db:seed --class="App\Modules\Platform\Identity\Database\Seeders\IdentityDemoSeeder"
Hasil: PASS
Catatan: IdentityDemoSeeder memanggil IdentityAccessSeeder.

Command: php artisan test
Hasil: PASS
Catatan: 36 tests, 107 assertions.

Command: npm audit
Hasil: PASS
Catatan: found 0 vulnerabilities.

Command: npm run build
Hasil: PASS
Catatan: Vite build selesai.

Command: npm run lint
Hasil: PASS
Catatan: eslint --fix selesai.
```
