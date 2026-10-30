# Specification: Platform/Identity

## Status

Implemented untuk fondasi authorization, UI akses dan manajemen akun internal.
Detail terbaru pada [work item pengguna](work-items/user-management/README.md).
Detail eksekusi berada pada [work item UI akses](work-items/access-management/README.md).

## Tujuan Dan Scope

Platform/Identity menyediakan fondasi authorization aplikasi POS. Scope saat ini
meliputi shared auth permission, seed permission, CRUD role biasa, katalog
permission read-only, assignment role pengguna existing, audit mutation akses
dan hardening hapus akun pribadi dari profil starter kit. Capability akun internal
mencakup create dengan password awal, edit nama/email dan aktivasi/deaktivasi.

## Arsitektur

- Hexagon: `Platform/Identity`.
- Inbound adapter: AccessController/AccessRequest, CLI migrasi role lama, serta
  ProfileController starter kit existing untuk self-delete (route dipertahankan).
- Use case: ManageAccess dan ListAccess; helper SyncUserRoles/SyncRolePermissions
  existing menjadi wrapper kompatibel yang menerapkan authorization baru.
- Outbound port internal: Application/Contracts/AccessRepository.
- DeleteOwnAccount memakai port internal yang sama; jalur destroy pada
  controller profil tidak melakukan delete/persistence langsung.
- Outbound adapter: SpatieAccessRepository menangani persistence, audit dan cache.
- ManageUsers menangani create/update/status dengan port internal yang sama.
  UserController/UserRequest dan EnsureActiveAccount menjadi inbound adapter.
- Application tidak melakukan mutation lewat model atau adapter konkret.
- Composition root:
  `app/Modules/Platform/Identity/ServiceProvider.php`.

## Di Luar Scope

- Public registration.
- Full user profile management.
- Hapus akun oleh admin, reset password admin, email invitation, key permission
  bebas dan direct user grants.
- Customer/supplier master.
- Multi company authorization.

## Contract

- GET `/platform/identity`: tab roles/permissions/users, search maksimum 150
  karakter dan page integer positif maksimum 2147483647; ukuran halaman global.
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
- Props pengguna hanya id/name/email/roles/direct_permissions_count/is_active/
  can_edit. Password, access_version,
  remember token dan credential tidak dikirim ke UI. Direct grants/guard lain
  tidak diubah oleh assignment web.

## Seeder Demo

IdentityPermissionsSeeder hanya mendaftarkan enam permission Identity; tidak
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
  super-system; minimal satu pemegang aktif harus tersisa. Gate bypass tidak melewati
  invariant ini. Jalur self-delete profil juga menegakkan last-super secara
  sinkron. Deaktivasi melindungi last-super aktif dan menolak akun sendiri.
- Manager biasa hanya memberi permission/role dalam permission efektifnya;
  tidak bisa mengedit/menghapus role dengan permission melebihi miliknya.
- Super user pada shared auth disebut `superSystem`.
- Role super-system guard web mendapat bypass Gate::before sesuai ADR-010.
  User lain memakai permission normal; validation/invariant tidak dibypass.

## Penghapusan Akun Pribadi

DELETE settings/profile hanya menerima password current_password dari akun
request; payload target id diabaikan. Tidak membutuhkan identity.manage karena
ini self-service existing, bukan admin CRUD akun. Role fresh dibaca sesudah lock
role web dan user; super-system terakhir ditolak dengan validation field account.
Wrong password tetap field password. Audit identity.account_deleted dan delete
atomic; delete yang veto atau exception membatalkan audit/pivot mutation.
Logout/invalidate sesi hanya sesudah action berhasil. Audit menyimpan ID actor
dan subject yang sama, before roles dan after deleted, tanpa PII/credential.
ID audit tetap tersedia walaupun relasi User sesudah hard delete menjadi null.
Hard delete baseline starter kit dipertahankan; tidak menambah schema/seeder.

UI resources/js/components/delete-user.tsx existing menampilkan account/password
error, Sonner, required input, processing guard, Ctrl/Cmd+Enter serta Escape.
Password dibersihkan setelah setiap request dan cancel; dialog tidak ditutup
ketika request masih berjalan. Detail QA:
[work item guard](work-items/account-deletion-guard/tasks.md).

## UI

- Page: `resources/js/pages/platform/identity/index.tsx`.
- Komponen: access-table, role-form-dialog dan access-editor-dialog di folder
  components lokal page; tipe lokal pada types.ts.
- Tab role, permission dan akses pengguna; server search/pagination; matrix
  checkbox per prefix; dialog konfirmasi delete dan editor role pengguna.
- Toast: operasi CRUD/sync memakai Sonner.
- State: loading, empty, error, dan disabled wajib tersedia.
- Tab pengguna menyediakan dialog tambah/edit/status, badge aktif/nonaktif dan
  guard per operasi. users.view tanpa identity.view hanya menampilkan tab pengguna.
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
- [x] Self-delete profil melindungi last-super, audited/atomic dan logout setelah sukses.
- [x] Create/edit/status akun internal, active last-super/session guard, permission
  per operasi, audit rollback dan browser desktop/tablet terverifikasi.

## Akun Internal Dan Session

POST users memerlukan users.create, nama/email valid/unik ternormalisasi,
password minimal 12 karakter/maksimal 72 byte dan konfirmasi, serta roles list (boleh kosong). Role
nonkosong juga memerlukan identity.manage; anti-escalation existing tetap berlaku.
PUT users/{id} menerima nama/email saja; credential/roles/status payload diabaikan.
PATCH users/{id}/status memerlukan users.manage dan is_active boolean.
Operator non-super tidak dapat takeover target super/higher privilege/guard lain.
Audit create menyimpan roles; edit hanya changed_fields; status before/after
is_active, tanpa password/nama/email. Exception/veto/audit failure rollback.

Migration module menambah is_active default true dan access_version default 0;
deaktivasi mempertahankan akun/pivot, menaikkan version, mengganti remember token
dan membuang reset token. Perubahan email juga mengganti remember token,
menghapus reset token dan verification lama. Login baru menyimpan version; middleware web sebelum Inertia memeriksa
state fresh. Sesi/version lama ditolak sekalipun akun sudah diaktifkan kembali.
Recaller baru yang valid setelah login ulang menggunakan version terbaru;
version user saat recall wajib cocok dengan state fresh sebelum inisialisasi.
Tidak menambahkan admin delete/soft delete atau reset/invitation lifecycle.

## Risiko Terbuka

- Permission matrix seluruh module bisnis belum lengkap karena module bisnis
  masih planned.
- Retained session rows/cleanup fisik dan ownership dokumen transaksi memerlukan
  aturan akun terpisah sebelum module transaksi bisnis digunakan. Deaktivasi dan
  version guard request berikutnya sudah tercakup, bukan cancel request in-flight.
- Uji konkurensi/load MySQL terpisah belum dijalankan. Test otomatis memakai
  SQLite disposable; pola locking MySQL direview, bukan diklaim diuji paralel.
- Katalog di atas 1000 perlu pencarian remote. Ketersediaan CDN font tidak masuk
  QA feature; fallback browser memakai stub CSS font eksternal.
