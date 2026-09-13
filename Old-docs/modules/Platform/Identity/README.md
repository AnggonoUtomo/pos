# Platform/Identity

## Identitas

- Category: `Platform`
- Module: `Identity`
- Source: `app/Modules/Platform/Identity/`
- Frontend: `resources/js/hooks/use-permission.ts`
- Status: Baseline selesai untuk auth internal, role, permission, shared auth props, dan audit perubahan akses.

## Tujuan

Module Identity menjadi boundary untuk akses aplikasi internal: user dibuat admin, public registration mati, role/permission memakai Spatie Permission, dan perubahan akses penting tercatat di activity log.

## Boundary

Memiliki:

- action sinkronisasi role user;
- action sinkronisasi permission role;
- shared auth props untuk Inertia;
- hook permission frontend untuk UX guard;
- demo seeder baseline identity.

Tidak memiliki:

- UI access matrix lengkap;
- SSO atau external auth;
- warehouse access final;
- manajemen menu/navigation berbasis permission.

## Public Boundary

Candidate consumer:

- module UI admin yang perlu membaca `auth.permissions`, `auth.roles`, dan `auth.superSystem`;
- controller/admin action yang mengelola role dan permission.

Candidate public boundary:

- `Application/Actions/SyncUserRoles`
- `Application/Actions/SyncRolePermissions`
- `resources/js/hooks/use-permission.ts`

## Data dan Identifier

- Table package Spatie Permission:
  - `roles`
  - `permissions`
  - `model_has_roles`
  - `model_has_permissions`
  - `role_has_permissions`
- Table audit:
  - `activity_log`
- User table tetap milik starter kit:
  - `users`

## Permission dan Audit

- Permission:
  - permission baseline mengikuti `docs/SPEC.md`.
- Backend permission guard:
  - route/controller admin berikutnya wajib memakai middleware `can:{permission}` atau policy eksplisit.
- Frontend permission guard:
  - `resources/js/hooks/use-permission.ts` untuk UX guard.
- Audit mutation:
  - `identity.user_roles_synced`
  - `identity.role_permissions_synced`

Metadata audit tidak boleh menyimpan secret, credential, token, atau payload sensitif yang tidak relevan.

## Operasi

- Route module berada di `app/Modules/Platform/Identity/Routes/`.
- Migration module berada di `app/Modules/Platform/Identity/Database/Migrations/`.
- Demo seeder module berada di `app/Modules/Platform/Identity/Database/Seeders/IdentityDemoSeeder.php`.
- `ServiceProvider.php` menjadi composition root dan tidak berisi business logic.
- Demo seeder memanggil `Database\Seeders\IdentityAccessSeeder` agar data role, permission, dan super admin baseline tetap satu sumber.

## Verifikasi Utama

```bash
php artisan test --filter=Identity
php artisan test --filter=PermissionBaselineTest
php artisan db:seed --class="App\\Modules\\Platform\\Identity\\Database\\Seeders\\IdentityDemoSeeder"
npm run build
git diff --check
```
