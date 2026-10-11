# Platform/Identity

## Status

Active

## Tujuan

Module ini mengelola baseline akses aplikasi: role, permission, shared auth
permission untuk Inertia, UI manajemen role/permission/akses pengguna existing,
sinkronisasi akses audited, seeder permission/demo role, serta pengaman hapus
akun pribadi dari profil starter kit.

## Boundary

- Memiliki: CRUD role biasa, katalog permission terdaftar, assignment role user,
  authorization helper backend, shared auth permission, seeder akses.
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

## Data Dan Seeder

- Migration: belum ada migration khusus module selain scaffold folder.
- `IdentityPermissionsSeeder`: dua key Identity saja; aman untuk provisioning
  tanpa mengubah akun, password, role atau assignment.
- `IdentityRolesDemoSeeder`: cashier/inventory-manager/inventory-viewer untuk
  local/testing; memakai permission modul yang sudah tersedia, tidak membuat
  akun atau menimpa role existing. Prasyarat tidak tersedia menyebabkan rollback.
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
- CRUD akun/password baru dan key permission bebas tidak masuk scope.
- DELETE settings/profile existing memanggil DeleteOwnAccount setelah validasi
  current-password. Last-super dibaca fresh di backend dengan lock role yang sama
  dengan assignment, lalu lock user. Audit/delete atomic; logout dan invalidation
  sesi sesudah action berhasil. Failure audit/delete mempertahankan akun/sesi.
- Audit identity.account_deleted menyimpan causer/subject ID, before roles dan
  after deleted saja; tanpa credential/name/email. Semantik hard delete starter
  kit dipertahankan, bukan lifecycle CRUD akun baru. Session perangkat lain dan
  ownership transaksi bisnis memerlukan hardening terpisah.
- Detail pengaman profil:
  [work item penghapusan akun](work-items/account-deletion-guard/README.md).
- Detail implementasi dan bukti QA:
  [work item UI akses](work-items/access-management/README.md).

## Verifikasi Utama

```powershell
php artisan test --filter=Identity
php artisan test --filter=PermissionBaselineTest
php artisan test --filter=AccessManagementTest
php artisan test --filter=AccountDeletionTest
```
