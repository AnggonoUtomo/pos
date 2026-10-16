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
- Level yang dirujuk pelanggan atau harga barang, termasuk nonaktif/trashed, tidak boleh
  dihapus atau dinonaktifkan. Nama/kode tetap dapat diedit.
- Invariant dijalankan Application, bukan hanya middleware atau UI.
- Create/update/restore pelanggan mengunci level terlebih dahulu lalu
  pelanggan. Delete/deactivate level mengunci level yang sama sebelum cek
  referensi; current locking read dipakai untuk pemeriksaan MySQL.
- Mutable master memakai soft delete. Restore tidak mengubah status aktif.
- Tidak ada mutasi stok, FIFO atau finance dari module ini.

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
phone/email/address tidak disalin ke audit. Edit kontak saja mencatat
`changed_fields` berupa nama field, tanpa nilai kontak. No-op tidak menambah log.
Pembacaan model sebelum mutation juga memakai current locking read agar dirty
check Eloquent tidak melewatkan perubahan dari snapshot REPEATABLE READ lama.

Demo 3 level ECERAN/GROSIR/INSTALLER dan 3 pelanggan fiktif: Pelanggan Umum,
Toko Listrik Demo, Installer CCTV Demo. Seeder idempotent memakai action;
existing code including trashed dilewati. Level missing/nonaktif pada pelanggan
baru membatalkan seluruh batch dan audit. Tidak seed otomatis ke database user.

## Harga Barang Per Satuan Dan Level

item_prices: ULID id, item_id/unit_id/customer_level_id FK restrict, amount
DECIMAL(20,2) MySQL, timestamp/soft delete. Composite FK ke pasangan item_units;
unique item/unit/level tetap direservasi termasuk trashed. SQLite test memakai
string exact agar tidak kehilangan presisi NUMERIC. Audit sales.item_prices
memuat ID/amount/lifecycle, actor bila authenticated; no-op tanpa log tambahan.

ItemPriceCatalog adalah public contract untuk SaveItemCatalog/ListItems.
Validasi matrix lengkap untuk semua satuan jual x level aktif, amount positif
scale2, tanpa float atau fallback. Maksimal 1000 level dan 5000 kombinasi.
Unit tambahan yang bukan satuan jual tidak memerlukan harga. Penghapusan
konfigurasi jual men-soft-delete harga, re-add memulihkan ID yang sama.
Level yang pernah dirujuk harga tidak boleh delete/nonaktif, termasuk harga
trashed. Lock workflow: level terurut -> Units terurut -> Item -> relasi/harga.
Query locking memakai current read; mutation harga/katalog/audit atomic.

Consumer PostInvoice memakai ItemPriceCatalog::forItems(itemIds, levelId,
lock = false) dengan lock true pada transaction default. Harga/active levels
memakai current read; default false mempertahankan read editor/katalog existing.
CustomerSalesLookup::findActive(id, lock = false) mendukung lock level lalu
customer dan recheck assignment, tanpa mempublikasikan kontak. Lookup locked
menolak pemakaian di luar transaction. Model/repository tetap privat.

Demo harga: 54 kombinasi dari 18 satuan jual sembilan produk x tiga level.
Harga fiktif Eceran dasar per item, Grosir faktor 0.90 dan Installer 0.95 hanya
untuk seed awal; bukan aturan pricing aplikasi. Rasio unit memakai DTO Items.
Jika item telah memiliki harga apa pun (termasuk trashed), seluruh item dilewati
agar edit user tidak tertimpa. Prasyarat hilang/level tak dikenal membatalkan
batch; local/testing saja. Harga real user selalu diisi eksplisit.
