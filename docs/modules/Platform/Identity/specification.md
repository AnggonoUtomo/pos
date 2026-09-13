# Specification: Platform/Identity

## Status

Baseline selesai untuk WI-0004.

## Tujuan dan Scope

Scope awal:

- public registration dimatikan;
- Spatie Permission dan Activitylog tersedia;
- role dan permission baseline dapat diseed;
- perubahan role user dan permission role dicatat ke activity log;
- shared auth props menyediakan user, roles, permissions, dan super flag;
- hook permission frontend tersedia untuk UX guard.

## Arsitektur

- Hexagon: `app/Modules/Platform/Identity`.
- Inbound adapter:
  - HTTP auth starter kit masih berada di controller bawaan Laravel.
  - Inertia shared data berada di `HandleInertiaRequests`.
- Use case awal:
  - `SyncUserRoles`
  - `SyncRolePermissions`
- Candidate public contract:
  - action application untuk admin access management;
  - shape `auth.roles`, `auth.permissions`, dan `auth.super` untuk Inertia.
- Composition root:
  - `app/Modules/Platform/Identity/ServiceProvider.php`.

Domain belum dibuat karena baseline identity saat ini memakai package Spatie sebagai boundary teknis dan belum memiliki rule murni di luar orchestration Application.

## Di Luar Scope

- UI access matrix lengkap.
- Warehouse access final.
- SSO atau external auth.
- Policy granular semua module bisnis.

## Contract

### Input

- `SyncUserRoles`: actor user, target user, daftar role name.
- `SyncRolePermissions`: actor user, target role, daftar permission name.

### Output

- Role/permission tersinkron.
- Activity log identity tercatat dengan state sebelum dan sesudah.

### Failure

- Role atau permission tidak valid mengikuti exception dari Spatie Permission.
- Actor tidak punya permission pada controller admin masa depan: `403`.
- Record tidak ditemukan: `404`.

## Data

Table:

- `users`
- `roles`
- `permissions`
- `model_has_roles`
- `model_has_permissions`
- `role_has_permissions`
- `activity_log`

## Authorization dan Audit

- Permission:
  - `platform.identity.users.view`
  - `platform.identity.users.create`
- Backend guard:
  - Controller Presentation memakai `HasMiddleware` dan `new Middleware('can:{permission}', only: [...])` atau policy eksplisit.
- Audit:
  - `identity.user_roles_synced`
  - `identity.role_permissions_synced`

## UI

- Page canonical akan dibuat pada work-item UI/admin berikutnya.
- UI guard memakai `resources/js/hooks/use-permission.ts` bila page menampilkan aksi berbasis permission.
- Backend permission tetap menjadi authority.

## Dependency

- Laravel starter kit auth.
- Spatie Laravel Permission.
- Spatie Laravel Activitylog.
- Inertia shared data.

## Acceptance Criteria

- [x] Public registration tidak bisa diakses.
- [x] Role/permission baseline berjalan.
- [x] Permission middleware baseline tersedia.
- [x] Activity log mencatat perubahan role user.
- [x] Activity log mencatat perubahan permission role.
- [x] Inertia shared auth menyediakan `roles`, `permissions`, dan `super`.
- [x] Hook `usePermission()` tersedia.

## Risiko Terbuka

- Warehouse access final menunggu module Inventory/Warehouse.
- UI access matrix lengkap perlu work-item terpisah agar tidak mencampur backend baseline dan layout admin.
