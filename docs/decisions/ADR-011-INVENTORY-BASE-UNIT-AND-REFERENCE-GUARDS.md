# ADR-011: Satuan Dasar Tetap Dan Guard Referensi Satuan

## Status

Accepted

## Tanggal

2026-10-10

## Konteks

Barang mempunyai satuan dasar terkecil dan konversi multi satuan miliknya.
Mengganti dasar atau menonaktifkan satuan yang sudah dirujuk dapat merusak
konversi, pemulihan data, dan interpretasi transaksi yang kelak memakai barang.
User menyetujui aturan aman ini sebelum implementasi CRUD Increment 6.

## Keputusan

- `base_unit_id` tidak boleh diganti setelah barang dibuat, termasuk oleh
  super-system. UI mengunci pilihan; Application tetap menegakkan invariant.
- Satuan yang pernah dirujuk `items.base_unit_id` atau `item_units.unit_id`
  tidak boleh dihapus atau dinonaktifkan. Referensi soft-deleted tetap dihitung.
- Units membaca referensi melalui public contract Items `ItemUnitUsage`,
  bukan Eloquent model privat. Items membaca satuan lewat `UnitLookup`/`UnitData`.
- Kedua public read boundary mempunyai consumer nyata dan tidak saling
  memanggil secara rekursif. Tidak membuat event untuk invariant sinkron ini.
- Mutation memakai transaction connection default bersama. Create/edit barang
  mengunci satuan menurut urutan ID sebelum barang; mutation satuan mengunci
  master sebelum pemeriksaan referensi. Data dan audit commit/rollback bersama.
- Rasio, barcode, dan flag satuan tambahan masih editable pada fase master
  tanpa transaksi stok. Sebelum Stock/POS real, wajib ada guard transaksi dan
  snapshot historis; keputusan ini bukan izin mengubah transaksi posted.

## Alternatif

- Mengizinkan penggantian dasar: memerlukan migrasi saldo dan histori konversi
  yang belum memiliki kebutuhan maupun alur koreksi aman.
- Menonaktifkan satuan sambil menyembunyikan referensi terhapus: mengganggu
  restore barang dan konfigurasi satuannya.
- Guard hanya frontend atau foreign key: tidak melindungi status aktif,
  soft delete, maupun request langsung ke backend.

## Konsekuensi

- Positif: konfigurasi dasar stabil, restore aman, boundary module eksplisit.
- Negatif: satuan yang pernah dipakai tidak dapat dihapus/nonaktif melalui CRUD;
  perubahan dasar kelak membutuhkan keputusan dan pekerjaan migrasi terpisah.
- Batas: test SQLite membuktikan behavior/rollback, bukan simulasi konkurensi
  MySQL. Pengujian locking konkurensi perlu diperluas pada work item transaksi.

## Verifikasi

InventoryCrudTest menguji immutable base unit, guard referensi termasuk trashed,
permission, lifecycle, dan rollback audit. InventoryPagesTest dan fallback QA
browser menguji pilihan dasar terkunci serta penolakan hapus satuan terpakai.

## Referensi

- [ADR-009](ADR-009-INVENTORY-UNITS-AND-ITEMS.md)
- [ADR-010](ADR-010-SUPER-SYSTEM-GATE-BYPASS.md)
- [Work item Inventory Foundation](../work-items/inventory-foundation/README.md)
