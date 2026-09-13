# Specification: Platform/Identity

## Status

Implemented sebagian, pending validasi lanjutan bila UI manajemen akses dibuat.

## Tujuan Dan Scope

Platform/Identity menyediakan fondasi authorization aplikasi POS. Scope saat ini
meliputi shared auth permission, role/permission seed baseline, action sinkron
role user, action sinkron permission role, dan audit mutation akses.

## Arsitektur

- Hexagon: `Platform/Identity`.
- Inbound adapter: HTTP route/controller bila UI identity dikembangkan,
  database seeder untuk baseline akses.
- Use case/inbound port: action sync role dan sync permission.
- Outbound port: belum diperlukan.
- Outbound adapter: Spatie Permission dan Spatie Activitylog dipakai melalui
  layer yang relevan.
- Composition root:
  `app/Modules/Platform/Identity/ServiceProvider.php`.

## Di Luar Scope

- Public registration.
- Full user profile management.
- Customer/supplier master.
- Multi company authorization.

## Contract

- Input: user id, role list, role id, permission list sesuai use case.
- Output: state role/permission tersinkron dan activity log tercatat.
- Failure: validation/authorization error dari Laravel atau exception use case.

## Data

- Role dan permission memakai tabel package Spatie.
- Primary key tabel utama project tetap mengikuti ULID bila migration baru dibuat.
- Activity log memakai tabel package Spatie.

## Seeder Demo

Seeder demo identity mengisi baseline role/permission yang relevan untuk
pengembangan. Seeder tidak boleh memberi akses berlebih tanpa alasan.

## Authorization Dan Audit

- Permission backend wajib menjadi authority.
- Shared frontend permission hanya untuk UX.
- Mutation role/permission wajib audited.
- Super user pada shared auth disebut `superSystem`.

## UI

- Page: belum final.
- Komponen: bila dibuat, gunakan `resources/js/pages/platform/identity`.
- Toast: operasi CRUD/sync memakai Sonner.
- State: loading, empty, error, dan disabled wajib tersedia.
- Browser QA: admin sidebar desktop dan responsive dasar bila UI dibuat.

## Dependency

- Laravel auth.
- Spatie Permission.
- Spatie Activitylog.

## Acceptance Criteria

- [x] Shared Inertia auth memuat permission map.
- [x] Shared Inertia auth memakai `superSystem`.
- [x] Hook `usePermission()` tersedia untuk UX frontend.
- [x] Focused identity test lulus.
- [ ] UI manajemen akses selesai bila masuk scope work item berikutnya.

## Risiko Terbuka

- Struktur UI identity belum menjadi baseline final.
- Permission matrix seluruh module bisnis belum lengkap karena module bisnis
  masih planned.
