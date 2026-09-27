# Work Item: Inventory Foundation

## Status

Ready

## Owner Dan Lokasi

- Owner module: lintas module Inventory (`Inventory/Warehouses`,
  `Inventory/Units`, `Inventory/Items`).
- Target kode: `app/Modules/Inventory/*`.
- Target dokumen: `docs/work-items/inventory-foundation/` dan dokumen module
  Inventory yang dibuat saat source module mulai dikerjakan.

## Kondisi Awal

Baseline UI dan Identity sudah tersedia. Module Inventory belum dibuat sebagai
source module bisnis, sementara transaksi POS membutuhkan fondasi inventory
sebelum mutation transaksi dapat aman:

- gudang untuk pemilihan warehouse saat transaksi;
- satuan dasar dan multi satuan;
- barang/item dengan base unit terkecil;
- stok tidak boleh minus;
- FIFO kelak berjalan per `item + warehouse`;
- demo seeder yang relevan untuk menjalankan alur POS mock ke data nyata.

## Scope

- Menyiapkan urutan implementasi fondasi Inventory sebelum transaksi Sales/POS
  real.
- Membuat module awal untuk gudang, satuan, dan item bila coding dimulai.
- Menetapkan data model awal untuk master inventory dengan ULID, soft delete
  pada master mutable, permission backend, audit activity log, dan demo seeder.
- Menjaga agar stok kuantitas dan FIFO belum dimutasi bebas dari master item.

## Tidak Dikerjakan

- Tidak membuat transaksi penjualan/POS real.
- Tidak membuat stock movement, FIFO layer, stock card, receiving, adjustment,
  retur, void, payment, tax, atau pricing real.
- Tidak membuat integrasi supplier/purchasing.
- Tidak mengubah keputusan multi gudang, single company, dan stok tidak boleh
  minus.

## Acceptance Criteria

- [ ] Work item memiliki increment detail sebelum coding.
- [ ] Dependency Inventory foundation terhadap POS real terdokumentasi.
- [ ] Scope gudang, satuan, dan item dipisahkan jelas.
- [ ] QA automated per increment ditentukan.
- [ ] Risiko terbuka dicatat sebelum coding module dimulai.

## Dampak Yang Harus Dicek

- [x] Database/migration.
- [x] Route.
- [x] Permission/policy.
- [x] UI/Inertia.
- [x] Seeder demo.
- [x] Activity log.
- [x] Soft delete atau mekanisme koreksi.
- [x] Transaksi stok/FIFO/payment/tax/diskon/pricing bila tersentuh.

## Dependency Dan Keputusan

- DDD-lite Modular Monolith + Hexagonal Architecture.
- Module source berada di `app/Modules/{Domain}/{Module}`.
- Route, migration, dan seeder berada di dalam module.
- ULID untuk primary key tabel utama.
- English technical naming untuk tabel, class, route, permission, DTOs, dan
  namespace.
- Display text UI dan dokumentasi memakai Bahasa Indonesia.
- Backend permission memakai `HasMiddleware` atau policy; frontend permission
  hanya UX.
- Demo seeder module wajib dipertimbangkan.
- Posted/ledger/stock movement/FIFO layer kelak tidak memakai soft delete
  sebagai pengganti reversal.

## Increment

| Increment | Status | Ringkasan | Verifikasi |
| --- | --- | --- | --- |
| 1 | Ready | Dokumen work item dan rencana implementasi Inventory foundation | `git diff --check`; review dokumen |
| 2 | Planned | Scaffold module `Inventory/Warehouses` | dry-run generator; focused test; route check |
| 3 | Planned | Scaffold module `Inventory/Units` | dry-run generator; focused test; route check |
| 4 | Planned | Scaffold module `Inventory/Items` | dry-run generator; focused test; route check |
| 5 | Planned | Master data database, permission, seeder demo | migration/status check; seed test; permission test |
| 6 | Planned | UI admin desktop/tablet untuk master inventory | lint; build; Chrome DevTools MCP |

## Handoff

- Perubahan: work item Inventory foundation dibuat sebagai acuan sebelum coding.
- Verifikasi: pending sampai Increment 1 selesai.
- Chrome DevTools QA: SKIPPED untuk increment dokumentasi awal karena belum ada
  UI baru.
- Risiko terbuka: urutan detail module dapat berubah setelah scaffold dry-run
  dan audit source saat coding dimulai.
