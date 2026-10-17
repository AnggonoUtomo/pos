# Specification: Inventory/Items

Published SaleItemCatalog::findMany(ids, lock = false) dipakai CheckCart dan
PostInvoice. Lock true wajib transaction default, discovery unit lalu lock
unit terurut/item/relasi current read dan recheck konfigurasi. Unit baru yang
belum termasuk discovery ditolak untuk quote ulang, bukan dibaca tanpa lock.
Default false mempertahankan katalog/read existing. Tidak mengekspor model.

## Status Dan Scope

Fondasi backend Implemented pada 9 Oktober 2026: create barang beserta satuan, audit,
permission, demo relasional, dan konversi kuantitas ke base unit.
Endpoint data/store internal tersedia pada Increment 5B; CRUD backend dan UI
admin tersedia pada Increment 6. Base unit immutable; saldo/FIFO dimiliki Stock.
Form matrix harga tersedia melalui public pricing boundary Sales/Parties.

## Model Data

| Tabel | Kolom utama | Ketentuan |
| --- | --- | --- |
| items | id, sku, name, base_unit_id, is_active | ULID; SKU unik maks 64; nama maks 150; base_unit_id FK units; default aktif |
| item_units | id, item_id, unit_id, conversion_ratio, barcode, is_sale_unit, is_purchase_unit | ULID; pasangan item/unit unik; FK restrict; rasio decimal(20,6); barcode nullable unik maks 64 |

Kedua tabel memiliki timestamp dan soft delete. Foreign key lintas module
menjaga referensi database tanpa mengimpor model privat Units. SKU, pasangan
item/unit, dan barcode tetap direservasi pada soft-deleted row.

## Invariant Dan Precision

- Base unit dimiliki barang, sesuai ADR-009, dengan rasio tepat 1.
- CreateItem menambahkan relasi base unit otomatis bila tidak disertakan.
- Satuan turunan memiliki rasio minimal 1: base unit harus menjadi satuan
  terkecil. Rasio pecahan seperti 1.5 tetap valid; rasio 0.5 ditolak.
- Kuantitas nonnegatif boleh pecahan dengan maksimal enam angka desimal.
- Input rasio/kuantitas berupa decimal string; tidak memakai float/scientific
  notation, nilai negatif, NaN, atau infinity.
- Rasio dan kuantitas dibatasi decimal(20,6): maksimal 14 digit integer dan
  enam digit desimal. Hasil konversi harus muat dan tepat pada scale enam.
- Perkalian memakai BigDecimal dari brick/math existing. Hasil yang memerlukan
  pembulatan ditolak, bukan dipotong. Strategi rounding transaksi belum dibuat.
- Satuan dalam satu barang tidak boleh duplikat. Satuan tidak dikenal,
  nonaktif, atau terhapus ditolak ketika membuat barang.
- SKU/nama tidak kosong, barcode kosong menjadi null. Panjang divalidasi pada
  Application sebelum persistence, bukan hanya bergantung pada HTTP nanti.

## Layer Dan Contract

- Domain/ValueObjects/ConversionRatio memiliki validasi dan toBaseQuantity.
- Application/Actions/CreateItem mengorkestrasi validation dan DB transaction.
- Application/DTOs/CreateItemData dan ItemUnitData menjadi input use case.
- Application/Contracts/ItemRepository menangani create, paginate, dan pencarian SKU
  termasuk soft-deleted row untuk seeder idempotent.
- Infrastructure/Persistence/Repositories/EloquentItemRepository menyimpan
  hasil validasi; tidak mengambil kepemilikan business rule.
- Units/Application/Contracts/UnitLookup menyediakan findActiveById(id, lock)
  dan findActiveByCode(code), mengembalikan UnitData atau null.
- Lookup dengan lock dipanggil di dalam transaction CreateItem pada connection
  default bersama. Adapter memakai lockForUpdate pada satuan yang dirujuk.
- Exception invalid input: InvalidArgumentException; konflik constraint DB
  dipetakan pada boundary HTTP: unique constraint menjadi validation error
  inventory; invariant menjadi error units. Error SQL tidak ditampilkan.
- StoreItemRequest hanya menerima field master yang diizinkan; ratio harus
  decimal string, unit_id distinct dan dinormalisasi lowercase sesuai HasUlids,
  maksimal 100 relasi; barcode kosong menjadi null, barcode '0' tetap valid.
- ListItems mengembalikan pagination 25 record dengan eager load relasi milik
  Items, tanpa mengambil model privat Units. Data terhapus disaring, barang
  nonaktif tetap tampil untuk admin. Response mengikuti API.md.

## Format Angka UI

Editor isi satuan dasar memakai format Indonesia: 1.000000 dari API menjadi 1,
6.000000 menjadi 6, dan 1.500000 menjadi 1,5. Nol desimal akhir tidak ditampilkan;
titik adalah grouping ribuan (1.000), koma adalah pemisah pecahan.
Formatter/parser shared resources/js/lib/decimal-input.ts (dipakai Items dan
PurchaseOrders) menjaga semua digit sebagai string/BigInt,
bukan float. Input 1,5 dikirim sebagai canonical 1.5; 1.000,5 sebagai 1000.5.
Input ambigu 1.5, grouping tidak valid, atau lebih dari enam digit pecahan
ditolak sebelum request, tanpa menghapus isian. Base unit tetap readonly 1.
API/storage tidak berubah; validasi minimal 1 dan guard histori tetap backend.

## Atomicity, Authorization, Dan Audit

Barang, seluruh relasi satuan, harga jual, dan audit harus commit atau rollback bersama.
Model Item dan ItemUnit mencatat atribut master relevan dan lifecycle; actor
mengikuti auth context bila ada. Audit bukan ledger. Endpoint index/data dan
store/update/destroy/restore memakai
HasMiddleware dengan can:inventory.view/manage per action, auth dan verified
mengikuti starter kit existing. Super-system memakai Gate sesuai ADR-010 dan
tetap harus lolos validasi bisnis.
Access seeder menyiapkan permission tanpa mengubah role/akses akun.

## Demo

- Sembilan produk CCTV/kelistrikan: kamera, NVR, UTP, NYM, MCB, RJ45,
  adaptor, balun dan paket CCTV. Delapan satuan, 18 relasi.
- BOX kamera = 4 UNIT; BOX NVR = 2 UNIT; BOX MCB = 12 UNIT.
- ROLL UTP = 305 M; ROLL NYM = 100 M; 0.125 ROLL NYM = 12.5 M.
- Dataset dan biaya fiktif lengkap ada pada
  [demo CCTV/kelistrikan](../../../work-items/cctv-electrical-demo/README.md).
- Barcode demo berupa label unik {sku}-{unit}, misalnya DEMO-CCTV-IP-2MP-BOX; bukan
  EAN produksi.
- Local/testing saja. Semua demo dibuat dalam transaction; unit demo yang
  diperlukan harus sudah aktif. Seeder tidak membuat/mengubah master Units.
- SKU existing termasuk soft-deleted row dilewati tanpa mengubah relasi/audit.

## Acceptance Dan Risiko

- [x] Domain math akurat, menolak rasio/kuantitas invalid, overflow, dan rounding.
- [x] Lookup public Units menyaring unit nonaktif/terhapus dan mengembalikan DTO.
- [x] CreateItem memastikan rasio base unit 1 dan rasio dus berbeda per barang.
- [x] Kegagalan barcode/relasi membatalkan barang, relasi satuan, dan audit.
- [x] ULID, unique/FK, soft delete, permission seed, dan demo idempotent terverifikasi.
- [x] Regresi, migration/status, route inspection, dan seed MySQL lulus.

ManageItem menyediakan update/delete/restore atomic. Base unit immutable sejak
create sesuai ADR-011 dan keputusan user 10 Oktober 2026. Editor master dapat mengubah rasio,
barcode dan flag satuan tambahan; relasi yang dikeluarkan di-soft-delete dan
ditambahkan kembali memakai ID lama. Delete item hanya menghapus lunak master,
tidak mengubah lifecycle relasi; restore mempertahankan konfigurasi satuan.
ItemUnitUsage mempublikasikan pemeriksaan referensi (termasuk trashed) untuk guard
Units. Sebelum consumer transaksi nyata dibuat, perubahan rasio/penghapusan
barang/satuan wajib mendapat guard transaksi dan snapshot historis.
Category/brand ditunda untuk menjaga scope foundation; pricing level berada
pada Sales/Parties melalui workflow SaveItemCatalog. UI tersedia pada resources/js/pages/inventory/items:
list/filter/barcode search, pagination, dialog multi-satuan dan lifecycle,
permission UX, Sonner, dropdown shortcut. Pilihan satuan memakai listActive
publik (maksimum 1000); pencarian remote diperlukan bila master melebihi batas
tersebut. QA desktop/tablet dan dark theme lulus melalui fallback Playwright.

## Harga Jual Pada Workflow Barang

Form matrix menampilkan satuan jual sebagai baris dan seluruh level aktif sebagai
kolom. Nilai positif wajib lengkap, maksimal dua desimal; format Indonesia,
canonical decimal string di API. Harga bukan HPP dan bukan fungsi rasio otomatis.
SaveItemCatalog mengunci level sebelum mutasi katalog dan memanggil
ItemPriceCatalog dalam outer transaction. Error matrix membatalkan katalog,
relasi dan audit, termasuk untuk super-system. ListItems membaca harga batch.
ItemPricingLookup mempublikasikan lookup SKU/konfigurasi satuan jual untuk
consumer pricing; tidak mempublikasikan repository/model Items.
Barang lama yang belum berharga harus dilengkapi saat edit. Tidak ada backfill
harga rekaan. Menambah level membuat matrix lama perlu dilengkapi saat edit;
belum ada consumer penjualan nyata. Restore mempertahankan konfigurasi existing.
