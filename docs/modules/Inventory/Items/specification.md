# Specification: Inventory/Items

## Status Dan Scope

Fondasi backend sedang dikerjakan: create barang beserta satuan, audit,
permission, demo relasional, dan konversi kuantitas ke base unit.
Belum ada endpoint/UI CRUD, perubahan base unit, saldo stok, FIFO, atau harga.

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
- Application/Contracts/ItemRepository menangani create dan pencarian SKU
  termasuk soft-deleted row untuk seeder idempotent.
- Infrastructure/Persistence/Repositories/EloquentItemRepository menyimpan
  hasil validasi; tidak mengambil kepemilikan business rule.
- Units/Application/Contracts/UnitLookup menyediakan findActiveById(id, lock)
  dan findActiveByCode(code), mengembalikan UnitData atau null.
- Lookup dengan lock dipanggil di dalam transaction CreateItem pada connection
  default bersama. Adapter memakai lockForUpdate pada satuan yang dirujuk.
- Exception invalid input: InvalidArgumentException; konflik constraint DB
  tetap exception persistence, dipetakan pada boundary HTTP saat CRUD tersedia.

## Atomicity, Authorization, Dan Audit

Barang, seluruh relasi satuan, dan audit harus commit atau rollback bersama.
Model Item dan ItemUnit mencatat atribut master relevan dan lifecycle; actor
mengikuti auth context bila ada. Audit bukan ledger. Use case belum diekspos
HTTP; permission controller akan diterapkan dan diuji pada Increment 6.
Access seeder menyiapkan permission tanpa mengubah role/akses akun.

## Demo

- DEMO-WATER-600: Air Mineral 600 ml, base PCS, BOX berisi 12 PCS.
- DEMO-TEA-250: Teh Kemasan 250 ml, base PCS, PACK 6 PCS dan BOX 24 PCS.
- DEMO-RICE: Beras, base G, KG berisi 1000 G; 0.125 KG menjadi 125 G.
- Barcode demo berupa label unik DEMO-{sku}-{unit}; bukan EAN produksi.
- Local/testing saja. Semua demo dibuat dalam transaction; unit demo yang
  diperlukan harus sudah aktif. Seeder tidak membuat/mengubah master Units.
- SKU existing termasuk soft-deleted row dilewati tanpa mengubah relasi/audit.

## Acceptance Dan Risiko

- [ ] Domain math akurat, menolak rasio/kuantitas invalid, overflow, dan rounding.
- [ ] Lookup public Units menyaring unit nonaktif/terhapus dan mengembalikan DTO.
- [ ] CreateItem memastikan rasio base unit 1 dan rasio dus berbeda per barang.
- [ ] Kegagalan barcode/relasi membatalkan barang, relasi satuan, dan audit.
- [ ] ULID, unique/FK, soft delete, permission, dan demo idempotent terverifikasi.
- [ ] Regresi, migration/status, route inspection, dan seed MySQL lulus.

Perubahan base unit/rasio barang yang sudah memiliki transaksi belum diizinkan
melalui use case pada tahap ini. Guard perubahan/deaktivasi master yang sudah
direferensikan dan snapshot transaksi harus ditambahkan bersama consumer nyata.
Category/brand ditunda untuk menjaga scope foundation; pricing level berada
pada pekerjaan Sales berikutnya. UI dan QA desktop/tablet menunggu Increment 6.
