# Specification: Platform/Identity

## Status

Implemented untuk fondasi authorization dan UI akses pengguna existing.
Detail eksekusi berada pada [work item UI akses](work-items/access-management/README.md).

## Tujuan Dan Scope

Platform/Identity menyediakan fondasi authorization aplikasi POS. Scope saat ini
meliputi shared auth permission, seed permission, CRUD role biasa, katalog
permission read-only, assignment role pengguna existing dan audit mutation akses.

## Arsitektur

- Hexagon: `Platform/Identity`.
- Inbound adapter: AccessController/AccessRequest dan CLI migrasi role lama.
- Use case: ManageAccess dan ListAccess; helper SyncUserRoles/SyncRolePermissions
  existing menjadi wrapper kompatibel yang menerapkan authorization baru.
- Outbound port internal: Application/Contracts/AccessRepository.
- Outbound adapter: SpatieAccessRepository menangani persistence, audit dan cache.
- Application tidak melakukan mutation lewat model atau adapter konkret.
- Composition root:
  `app/Modules/Platform/Identity/ServiceProvider.php`.

## Di Luar Scope

- Public registration.
- Full user profile management.
- CRUD akun/password, key permission bebas dan direct user grants.
- Customer/supplier master.
- Multi company authorization.

## Contract

- GET `/platform/identity`: tab roles/permissions/users, search maksimum 150
  karakter dan page integer positif maksimum 2147483647; 25 record/page.
- Mutation: create/rename/delete role, sync permission role, sync role user.
  Response JSON `{id}` (201 create, 200 lainnya) atau redirect web.
- Role name di-trim/lowercase, maksimum 64, pola `[a-z][a-z0-9_-]*` dan unik
  per guard web. Guard/name/id/payload tambahan tidak dapat mengubah ownership.
- Roles/permissions adalah list distinct maksimum 1000 key/nama terdaftar web;
  unknown, duplicate sesudah trim atau guard lain ditolak. List kosong valid.
- Katalog pilihan maksimum 1000 role/permission. Overflow menghasilkan error
  eksplisit, bukan clipping diam-diam; pencarian remote belum masuk scope.
- Failure: 401/redirect login untuk guest, 403 authorization, 404 target di luar
  guard/tidak ditemukan, 422 validation/konflik unik tanpa detail SQL.
- Contract HTTP rinci berada pada [API](../../../API.md).

## Data

- Role dan permission memakai tabel package Spatie.
- User/Role/Permission existing integer tetap dipertahankan; tabel utama bisnis
  baru mengikuti ULID. Tidak ada schema baru untuk UI akses.
- Activity log memakai tabel package Spatie.
- Role biasa tanpa referensi memakai hard delete audited sebagai pengecualian
  soft delete package; role sistem atau terpakai tidak boleh dihapus.
- Props pengguna hanya id/name/email/roles/direct_permissions_count. Password,
  remember token dan credential tidak dikirim ke UI. Direct grants/guard lain
  tidak diubah oleh assignment web.

## Seeder Demo

IdentityPermissionsSeeder hanya mendaftarkan dua permission Identity; tidak
mengubah akun, password atau role assignment. IdentityRolesDemoSeeder hanya
local/testing, membutuhkan permission modul terdaftar dan membuat role yang
belum ada saja. Repeat tidak menimpa konfigurasi existing; tidak membuat akun.
Jangan menjalankan ulang seeder akun existing sebagai cara rollout UI akses.

## Authorization Dan Audit

- Permission backend wajib menjadi authority.
- Shared frontend permission hanya untuk UX.
- Mutation role/permission wajib audited.
- ManageAccess memeriksa actor fresh di dalam transaction. Semua mutation UI
  mengambil lock role web dengan urutan ID konsisten; assignment juga lock user.
  Audit failure membatalkan mutation. No-op tidak menghasilkan audit ganda.
  Cache permission dibersihkan setelah transaction selesai.
- Role super-system/super-admin reserved untuk CRUD dan sync permission.
- Hanya super-system dapat mengubah user yang memiliki atau akan menerima role
  super-system; minimal satu pemegang harus tersisa. Gate bypass tidak melewati
  invariant ini. Pengaman ini khusus assignment, bukan semua jalur CRUD akun.
- Manager biasa hanya memberi permission/role dalam permission efektifnya;
  tidak bisa mengedit/menghapus role dengan permission melebihi miliknya.
- Super user pada shared auth disebut `superSystem`.
- Role super-system guard web mendapat bypass Gate::before sesuai ADR-010.
  User lain memakai permission normal; validation/invariant tidak dibypass.

## UI

- Page: `resources/js/pages/platform/identity/index.tsx`.
- Komponen: access-table, role-form-dialog dan access-editor-dialog di folder
  components lokal page; tipe lokal pada types.ts.
- Tab role, permission dan akses pengguna; server search/pagination; matrix
  checkbox per prefix; dialog konfirmasi delete dan editor role pengguna.
- Toast: operasi CRUD/sync memakai Sonner.
- State: loading, empty, error, dan disabled wajib tersedia.
- Shortcut dropdown di atas workspace: Alt+F, Ctrl/Cmd+Shift+N,
  Ctrl/Cmd+Enter dan Escape. Navigasi sidebar/palette mengarah ke route nyata.
- Browser QA: desktop expanded/collapsed, tablet 1024x768/768x1024,
  light/dark/clean, readonly/delegated manager. Theme existing tidak diubah.

## Migrasi Role Lama

Operator CLI memakai `identity:migrate-super-role` untuk preview nonmutating,
dan `--apply` hanya setelah persetujuan rename super-admin web ke super-system.
Application MigrateLegacySuperRole menjalankan transaction/lock/audit; command
adalah inbound adapter terdaftar di ServiceProvider. ID/pivot/credential tetap,
repeat no-op dan konflik kedua nama berhenti tanpa merge. Tidak ada alias bypass
untuk super-admin, route HTTP, reset seeder akun, atau schema baru. Cache
permission dihapus sesudah commit. Operasi administratif ini tidak dapat
dibalik secara diam-diam; koreksi akses harus audited dan disetujui.

Detail implementasi/QA ada pada
[work item migrasi](work-items/legacy-super-role/README.md).

## Dependency

- Laravel auth.
- Spatie Permission.
- Spatie Activitylog.

## Acceptance Criteria

- [x] Shared Inertia auth memuat permission map.
- [x] Shared Inertia auth memakai `superSystem`.
- [x] Hook `usePermission()` tersedia untuk UX frontend.
- [x] Focused identity test lulus.
- [x] UI manajemen akses, permission terdaftar dan assignment pengguna tersedia.
- [x] Audit/rollback, guest/readonly, privilege, reserved/last-super dan seed aman diuji.
- [x] Browser workflow desktop/tablet terverifikasi melalui fallback disposable.

## Risiko Terbuka

- Permission matrix seluruh module bisnis belum lengkap karena module bisnis
  masih planned.
- Profil starter kit masih memiliki jalur delete akun tanpa last-super guard;
  hardening akun merupakan pekerjaan terpisah, bukan bagian UI assignment.
- Uji konkurensi/load MySQL terpisah belum dijalankan. Test otomatis memakai
  SQLite disposable; pola locking MySQL direview, bukan diklaim diuji paralel.
- Katalog di atas 1000 perlu pencarian remote. Ketersediaan CDN font tidak masuk
  QA feature; fallback browser memakai stub CSS font eksternal.
