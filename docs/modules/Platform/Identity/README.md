# Platform/Identity

## Status

Active

## Tujuan

Module ini mengelola baseline akses aplikasi: role, permission, shared auth
permission untuk Inertia, sinkronisasi role user, sinkronisasi permission role,
dan demo seeder identity.

## Boundary

- Memiliki: role/permission baseline, authorization helper backend, shared auth
  permission, demo seeder akses.
- Tidak memiliki: registrasi publik, profil user lanjutan, multi company,
  payroll, customer/supplier identity, atau audit ledger.

## Public Boundary

- Contracts: belum ada contract lintas module.
- DTOs: belum ada DTOs publik.
- Events: belum ada event publik lintas module.
- Routes: route module berada di
  `app/Modules/Platform/Identity/Routes/web.php`.

## Dependency

- Laravel authentication starter kit.
- Spatie Laravel Permission untuk role dan permission.
- Spatie Laravel Activitylog untuk audit mutation akses.

## Permission

Permission mengikuti key English technical naming. Permission baseline harus
diamankan di backend; frontend hanya menyembunyikan atau menampilkan UI.

## Data Dan Seeder

- Migration: belum ada migration khusus module selain scaffold folder.
- Seeder demo:
  `app/Modules/Platform/Identity/Database/Seeders/IdentityDemoSeeder.php`.
- Soft delete: tidak relevan untuk role/permission baseline saat ini; perubahan
  akses dicatat melalui audit.

## Operasi

- Shared auth Inertia menyediakan `roles`, `permissions`, dan `superSystem`.
- Hook UX berada di `resources/js/hooks/use-permission.ts`.
- Public registration harus tetap dimatikan.

## Verifikasi Utama

```powershell
php artisan test --filter=Identity
php artisan test --filter=PermissionBaselineTest
```
