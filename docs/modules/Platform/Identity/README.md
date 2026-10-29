# Platform/Identity

## Status

Active

## Tujuan

Module ini mengelola baseline akses aplikasi: role, permission, shared auth
permission untuk Inertia, UI manajemen role/permission/akses pengguna,
tambah/edit/status akun internal, sinkronisasi akses audited, seeder permission/demo role, serta pengaman hapus
akun pribadi dari profil starter kit.

## Boundary

Penghapusan/nonaktif akun memanggil published `ActiveShiftLookup` Sales/POS
di bawah lock user yang sama dengan buka/checkout shift. Akun dengan shift
aktif wajib menutup shift dahulu. Riwayat shift tetap menyimpan snapshot nama
setelah akun closed kemudian dihapus; tidak memakai model privat POS.

- Memiliki: CRUD role biasa, katalog permission terdaftar, assignment role user,
  tambah/edit nama/email dan status aktif akun, authorization helper backend,
  shared auth permission, seeder akses.
- Tidak memiliki: registrasi publik, profil user lanjutan, multi company,
  payroll, customer/supplier identity, atau audit ledger.

## Public Boundary

- Contracts: `Application/Contracts/AccessRepository` adalah outbound port
  internal Identity, bukan contract mutation untuk module lain. Belum ada
  contract lintas module yang dipublikasikan.
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
UI memakai `platform.identity.view`; mutation memakai `platform.identity.manage`.
Operator akun dapat memakai users.view untuk tab pengguna saja. Tambah/edit/status
memakai platform.identity.users.create/update/manage; assignment role tetap
identity.manage. Permission create tanpa manage boleh membuat akun tanpa role.

## Data Dan Seeder

- Migration module menambah users.is_active (default true) dan access_version
  (default 0); tidak mengganti ID/credential atau assignment existing.
- `IdentityPermissionsSeeder`: enam key Identity; aman untuk provisioning
  tanpa mengubah akun, password, role atau assignment.
- `IdentityRolesDemoSeeder`: cashier/inventory-manager/inventory-viewer untuk
  local/testing; memakai permission modul yang sudah tersedia, tidak membuat
  akun atau menimpa role existing. Prasyarat tidak tersedia menyebabkan rollback.
- `ProductionRolesSeeder`: tujuh profil produksi guard web, permission minimal,
  audit atomic dan rerun no-op. Tidak membuat akun/grant user, tidak mereset
  super-system existing; konflik matriks berhenti tanpa penimpaan. Detail
  [matriks produksi](production-roles.md) dan
  [work item](work-items/production-roles/README.md).
- `IdentityDemoSeeder`/`IdentityAccessSeeder` existing dapat membuat atau
  memperbarui akun demo. Jangan gunakan untuk rollout UI akses atau migrasi role.
- Identifier User/Role/Permission existing tetap integer starter kit/package;
  tidak ada migrasi identifier pada pekerjaan UI akses ini.
- Role terpakai/sistem tidak dapat dihapus. Role biasa tanpa referensi memakai
  hard delete audited sebagai pengecualian soft delete untuk tabel package;
  tidak menambah lifecycle model Spatie paralel. Permission tidak punya CRUD UI.

## Operasi

- Shared auth Inertia menyediakan `roles`, `permissions`, dan `superSystem`.
- Hook UX berada di `resources/js/hooks/use-permission.ts`.
- Public registration harus tetap dimatikan.
- Gate::before memberi bypass authorization kepada role super-system guard web
  sesuai ADR-010. Tidak mengubah assignment akun, tidak memberi akses kepada
  user biasa, dan tidak melewati validation/invariant.
- Role lama super-admin bukan alias bypass. Migrasi administratif tersedia:
  `php artisan identity:migrate-super-role` untuk preview dan `--apply` untuk
  rename audited setelah persetujuan operator. ID/pivot/password dipertahankan;
  konflik dua role berhenti tanpa merge. Jangan menjalankan ulang seeder akun
  untuk migrasi karena dapat mengganti credential. Detail ada pada
  [work item migrasi](work-items/legacy-super-role/README.md).
- UI tersedia di `/platform/identity`: tab role, katalog permission dan akses
  pengguna, filter/pagination, checkbox matrix, Sonner, shortcut dan palette.
- Role sistem dilindungi; hanya super-system dapat mengubah assignment
  super-system dan tidak boleh mencabut pemegang terakhir. Manager biasa tidak
  boleh memberi permission/role di luar permission efektifnya. Direct user
  grants dan role guard lain dipertahankan.
- Akun internal dibuat dengan password awal dan konfirmasi dari admin; edit
  hanya nama/email. Password hashed, tidak dikirim ke UI/audit. Tanpa reset
  password admin, hapus akun admin, email invitation atau key permission bebas.
- Status nonaktif mempertahankan akun/pivot, menolak login dan mencabut sesi
  lama dengan access_version serta remember token. Reaktivasi wajib login baru;
  self-deactivation dan menghilangkan super-system aktif terakhir ditolak.
- Operator biasa tidak bisa edit identitas/status super-system atau pengguna
  berpermission lebih tinggi/guard lain. Audit profil mencatat field yang berubah
  saja, tanpa PII. Semua mutation/audit atomic.
- DELETE settings/profile existing memanggil DeleteOwnAccount setelah validasi
  current-password. Last-super dibaca fresh di backend dengan lock role yang sama
  dengan assignment, lalu lock user. Audit/delete atomic; logout dan invalidation
  sesi sesudah action berhasil. Failure audit/delete mempertahankan akun/sesi.
- Audit identity.account_deleted menyimpan causer/subject ID, before roles dan
  after deleted saja; tanpa credential/name/email. Semantik hard delete starter
  kit dipertahankan. Session akun nonaktif/version lama kini ditolak pada request
  web berikutnya; cleanup fisik row sesi dan ownership transaksi bisnis masih
  memerlukan aturan terpisah.
- Detail pengaman profil:
  [work item penghapusan akun](work-items/account-deletion-guard/README.md).
- Detail implementasi dan bukti QA:
  [work item UI akses](work-items/access-management/README.md).
- Manajemen akun terbaru:
  [work item pengguna](work-items/user-management/README.md).

## Verifikasi Utama

```powershell
php artisan test --filter=Identity
php artisan test --filter=PermissionBaselineTest
php artisan test --filter=AccessManagementTest
php artisan test --filter=AccountDeletionTest
php artisan test --filter=UserManagementTest
php artisan test --filter=UserStatusTest
```
