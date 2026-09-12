# WI-0012: Transfer Gudang dan Stok Opname

Status: Draf

## Tujuan

Membangun transfer antar gudang, item masuk/keluar, dan stok opname dasar.

## Rujukan

- `docs/SPEC.md`
- `docs/adr/ADR-0003-inventory-fifo-no-negative-stock.md`
- `docs/QA-AUTOMATION.md`

## Modul Target

- Category: Inventory
- Module: WarehouseOperations
- Namespace/path: `app/Modules/Inventory/WarehouseOperations`
- Jenis pekerjaan: transfer gudang, stock opname, dan adjustment audited

## Scope

Masuk scope:

- Warehouse transfer.
- Transfer FIFO cost carry-over.
- Item masuk/keluar adjustment.
- Stock opname posting.
- Stock movement and activity log.

Di luar scope:

- Multi cabang.
- Export/import transfer beda lokasi.

## Checklist Sebelum Coding

- [ ] Fondasi Persediaan FIFO tersedia.
- [ ] Movement types jelas.
- [ ] Approval requirement ditentukan jika perlu.

## Rencana Increment

| Increment | Fokus | Checklist Sebelum | Checklist Sesudah | QA automated | Status |
| --- | --- | --- | --- | --- | --- |
| INC-01 | Transfer gudang | - [ ] Warehouse dan FIFO tersedia | - [ ] Transfer consume source dan create destination layer | feature test warehouse transfer FIFO | Draf |
| INC-02 | Stock opname | - [ ] Rule selisih stok disepakati | - [ ] Adjustment opname audited dan tidak bypass minus | feature test stock opname adjustment | Draf |
| INC-03 | Stock operation reporting hook | - [ ] Read model kebutuhan laporan diketahui | - [ ] Movement dapat dipakai laporan | feature test stock operation movement | Draf |

## Kriteria Penerimaan

- [ ] Transfer mengurangi gudang asal dan menambah gudang tujuan.
- [ ] Cost FIFO terbawa ke gudang tujuan.
- [ ] Transfer ditolak jika stok asal tidak cukup.
- [ ] Opname membuat adjustment yang audited.

## Verifikasi

- [ ] Feature test transfer FIFO cost.
- [ ] Feature test transfer insufficient stock.
- [ ] Feature test opname adjustment.
