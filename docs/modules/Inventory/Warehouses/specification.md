# Specification: Inventory/Warehouses

## Status

Fondasi backend Implemented dan terverifikasi pada 9 Oktober 2026;
Endpoint daftar/create internal tersedia sejak Increment 5A; CRUD backend dan
UI admin tersedia pada Increment 6A/6B, terverifikasi desktop/tablet.

## Scope Dan Arsitektur

Increment 2 menyiapkan persistence, permission, dan demo data. Composition root
adalah `app/Modules/Inventory/Warehouses/ServiceProvider.php`, ditemukan oleh
loader existing. Migration, route scaffold, dan seeder berada di dalam module.
Adapter persistence menggunakan Eloquent di Infrastructure.

CreateWarehouse dan ListWarehouses memakai WarehouseRepository dan DTOs di
Application. Adapter Eloquent dibinding oleh provider. Domain belum diperlukan
untuk CRUD sederhana. Create memakai transaction boundary untuk data/audit;
controller hanya memanggil use case, bukan persistence.

## Data

| Kolom | Ketentuan |
| --- | --- |
| `id` | ULID primary key |
| `code` | String maksimal 32 karakter, unique termasuk soft-deleted row |
| `name` | String maksimal 150 karakter |
| `is_active` | Boolean, default true |
| `created_at`, `updated_at` | Timestamp Laravel |
| `deleted_at` | Soft delete master |

Kode diisi eksplisit, tidak memakai penomoran otomatis. Code/name di-trim dan
divalidasi panjang/wajib; kode tetap direservasi setelah soft delete. Hanya gudang aktif
yang tidak dihapus yang boleh dipilih saat integrasi transaksi nanti.

## Seeder Demo

`WH-MAIN` (Gudang Utama) dan `WH-SECONDARY` (Gudang Tambahan) dibuat aktif.
Pencarian existing row menyertakan soft-deleted row. Pengulangan seed tidak
mengganti ULID/nama/status, tidak menghidupkan gudang terhapus, dan tidak
menambah audit tanpa perubahan. Seeder menolak environment selain local/testing.
Permission disiapkan terpisah dan dapat di-seed tanpa memasukkan demo data.

## Authorization Dan Audit

- Permission awal `inventory.view` dan `inventory.manage`, guard `web`.
- GET inventory/warehouses/data memakai can:inventory.view; POST inventory/warehouses
  memakai can:inventory.manage melalui HasMiddleware. Response/QA ada di API.md.
- Perubahan model create/update/delete/restore memakai Spatie Activitylog.
- Prasyarat kompatibilitas: `activity_log.subject_id` existing bertipe bigint.
  Migration global memperlebarnya menjadi varchar(26) agar subject numerik lama
  dan ULID baru sama-sama terhubung. Kolom causer tidak berubah karena user
  existing masih numerik. Rollback tidak mempersempit subject_id, agar referensi
  ULID pada audit yang sudah tersimpan tidak rusak.
- Audit hanya memuat code, name, is_active, dan deleted_at; update tanpa
  perubahan tidak menghasilkan log baru. Actor mengikuti auth context jika ada.
- Soft delete bukan mekanisme koreksi transaksi. Larangan menghapus gudang
  yang masih memiliki stok/transaksi perlu ditambahkan bersama consumer tersebut.

## UI Dan Di Luar Scope

UI admin tersedia pada resources/js/pages/inventory/warehouses dengan list,
filter server, pagination, dialog CRUD, Sonner, shortcut, dan permission UX.
Route index nyata diaktifkan pada sidebar/palette. Tidak ada stok, FIFO,
pembayaran atau integrasi POS nyata.

## Acceptance Criteria

- [x] Provider ditemukan dan migration module dijalankan pada test database.
- [x] ULID, kode unik, default aktif, dan soft delete terverifikasi.
- [x] Seeder idempotent dan menjaga perubahan user, termasuk soft-deleted row.
- [x] Permission dapat di-seed ulang tanpa memberi akses otomatis.
- [x] Audit create/update/delete/restore dan actor terverifikasi.

## Risiko Terbuka

Larangan menghapus/nonaktifkan gudang yang memiliki stok atau transaksi perlu
ditetapkan dan diuji bersama consumer terkait. Ownership master satuan/barang
mengikuti ADR-009 dan tidak mengubah boundary gudang.
