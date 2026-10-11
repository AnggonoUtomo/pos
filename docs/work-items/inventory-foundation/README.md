# Work Item: Inventory Foundation

## Status

Completed. Implementasi, QA dan pengiriman remote terverifikasi pada 11 Oktober 2026.

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

- [x] Work item memiliki increment detail sebelum coding.
- [x] Dependency Inventory foundation terhadap POS real terdokumentasi.
- [x] Scope gudang, satuan, dan item dipisahkan jelas.
- [x] QA automated per increment ditentukan.
- [x] Risiko terbuka dicatat sebelum coding module dimulai.

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
| 1 | Passed | Dokumen work item dan rencana implementasi Inventory foundation | `git diff --check`; review dokumen |
| 2 | Passed | Fondasi module `Inventory/Warehouses` | 11 focused test; full suite 49 test; route, migration, seed MySQL |
| 3 | Passed | Fondasi master `Inventory/Units`; konversi dimiliki Items | 9 focused test; full suite 58 test; route, migration dan seed MySQL |
| 4 | Passed | Fondasi Items: core 4A dan demo 4B | 40 focused test; 98 full test; lookup public, migration, seed dan rollback MySQL |
| 5 | Passed | Endpoint data/store dengan permission; audit/demo terintegrasi; Gate super-system sesuai ADR-010 | 33 focused test; 131 full test; HTTP, MySQL, route/status |
| 6 | Passed | CRUD backend/admin desktop/tablet untuk master inventory; guard satuan dan audit atomic | 150 test/853 assertion; lint/typecheck/build; fallback browser, 18 screenshot |

## Handoff

- Perubahan: Increment 1-6 selesai; CRUD gudang, satuan, barang/multi satuan,
  konversi exact, public lookup/guard, permission, demo relasional, soft delete,
  restore dan audit atomic tersedia. Ada 18 route Inventory dengan can per
  action; super-system bypass Gate tidak melewati invariant (ADR-010/011).
- Verifikasi akhir 11 Oktober 2026: 150 test/853 assertion, Pint, lint global
  source aktif, typecheck global, build, route/status dan diff check PASS.
  Semua 9 migration Ran; tidak menambah schema pada Increment 6. Gap typecheck
  baseline diperbaiki dalam commit terpisah a1c2854 sesuai persetujuan user.
- Chrome DevTools MCP: BLOCKED karena tool tidak tersedia; browser plugin juga
  tidak menemukan browser terhubung. Fallback Playwright lokal PASS dengan
  SQLite disposable, 18 screenshot desktop/tablet dan console kosong. MySQL
  pengguna tidak dimutasi oleh QA browser. Detail dan bukti ada di tasks.md.
- Tidak disentuh: theme/CSS, Old-docs, SampleUI, dependency, credential seed,
  assignment akses user, stok/FIFO, payment dan pricing real.
- Risiko berikutnya: guard referensi transaksi dan snapshot historis wajib
  sebelum Stock/POS real; locking konkurensi MySQL belum disimulasikan; pilihan
  form dibatasi 1000 satuan aktif. User existing belum mengimplementasikan
  MustVerifyEmail; middleware verified bukan jaminan verifikasi email.
- Tidak membuat module/transaksi baru dari handoff ini tanpa instruksi user.
