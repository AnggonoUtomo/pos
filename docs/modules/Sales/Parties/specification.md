# Specification: Pelanggan Dan Level Pelanggan

## Data Dan Invariant

`customer_levels`: ULID id, code unique maksimal 32, name maksimal 150,
is_active boolean default true, timestamps, deleted_at.

`customers`: ULID id, code unique maksimal 32, name maksimal 150,
customer_level_id ULID FK restrict, phone nullable maksimal 32,
email nullable maksimal 150, address nullable maksimal 500,
is_active boolean default true, timestamps, deleted_at.

Kode trim dan uppercase, unik termasuk trashed. Nama trim. Kontak bukan akun
login; email tidak wajib unik. Tidak ada alamat/kontak default nyata.
Field tak dikenal tidak dipersistensikan. ID route ULID dinormalisasi.

- Pelanggan wajib menunjuk satu level aktif dan tidak terhapus, termasuk
  pada update atau restore. Nonaktif/soft-deleted pelanggan tetap boleh
  merujuk level, tetapi bukan pilihan transaksi nanti.
- Level yang dirujuk pelanggan, termasuk nonaktif/trashed, tidak boleh
  dihapus atau dinonaktifkan. Nama/kode tetap dapat diedit.
- Invariant dijalankan Application, bukan hanya middleware atau UI.
- Create/update/restore pelanggan mengunci level terlebih dahulu lalu
  pelanggan. Delete/deactivate level mengunci level yang sama sebelum cek
  referensi; current locking read dipakai untuk pemeriksaan MySQL.
- Mutable master memakai soft delete. Restore tidak mengubah status aktif.
- Tidak ada mutasi stok, FIFO, pricing atau finance dari module ini.

## HTTP Dan UI

Prefix `/sales/customer-levels` dan `/sales/customers`, route names
`sales.customer-levels.*` dan `sales.customers.*`.
GET index/data, POST store, PUT update, DELETE destroy, POST `{id}/restore`.
Index Inertia: `sales/parties/customer-levels` dan `sales/parties/index`.
Data `{data: [...], meta: {current_page,last_page,per_page,total}}`, 25/baris
halaman. Filter search maksimal 150, status all/active/inactive, trashed boolean,
page positive. Customers juga mendukung filter customer_level_id.
Pilihan level aktif maksimal 1000; remote lookup ditunda jika melampaui batas.

Auth/verified dan `HasMiddleware` can:sales.parties.view untuk index/data,
can:sales.parties.manage untuk mutation. Super-system bypass permission saja.
JSON create 201/id, mutation 200/id; web redirect flash success.
Validation/conflict 422 teredaksi; unknown resource 404.

UI menggunakan shadcn/Lucide, badge semantic, Sonner, loading/empty/error,
search/pagination, dialog CRUD/restore dan dropdown shortcut. Ctrl/Cmd+Shift+N
tambah, Alt+F cari, Ctrl/Cmd+Enter simpan, Esc tutup. Navigation/palette hanya
tampil bila berhak. Desktop dan tablet landscape/portrait; mobile ditunda.

## Audit Dan Demo

Log `sales.customer_levels` dan `sales.customers`; create/update/delete/restore
memuat actor dan subject ULID. Hanya code/name/level/status/deleted_at diaudit;
phone/email/address tidak disalin ke audit. No-op tidak menambah log.

Demo 3 level ECERAN/GROSIR/INSTALLER dan 3 pelanggan fiktif: Pelanggan Umum,
Toko Listrik Demo, Installer CCTV Demo. Seeder idempotent memakai action;
existing code including trashed dilewati. Level missing/nonaktif pada pelanggan
baru membatalkan seluruh batch dan audit. Tidak seed otomatis ke database user.
