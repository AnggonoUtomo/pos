# WI-0012: Transfer Gudang dan Stok Opname

Status: Draf

## Tujuan

Membangun transfer antar gudang, item masuk/keluar, dan stok opname dasar.

## Rujukan

- `docs/SPEC.md`
- `docs/adr/ADR-0003-inventory-fifo-no-negative-stock.md`

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

## Kriteria Penerimaan

- [ ] Transfer mengurangi gudang asal dan menambah gudang tujuan.
- [ ] Cost FIFO terbawa ke gudang tujuan.
- [ ] Transfer ditolak jika stok asal tidak cukup.
- [ ] Opname membuat adjustment yang audited.

## Verifikasi

- [ ] Feature test transfer FIFO cost.
- [ ] Feature test transfer insufficient stock.
- [ ] Feature test opname adjustment.
