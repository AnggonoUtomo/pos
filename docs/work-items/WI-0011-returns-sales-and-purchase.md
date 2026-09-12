# WI-0011: Retur Penjualan dan Pembelian

Status: Draf

## Tujuan

Membangun retur penjualan dan retur pembelian berdasarkan transaksi asal.

## Rujukan

- `docs/SPEC.md`
- `docs/adr/ADR-0003-inventory-fifo-no-negative-stock.md`

## Scope

Masuk scope:

- Retur penjualan berdasarkan sales invoice asal.
- Retur pembelian berdasarkan purchase invoice asal.
- Return quantity validation.
- Stock effects.
- Refund/deposit/payable/receivable adjustment.
- Activity log.

Di luar scope:

- Retur tanpa transaksi asal.
- Exchange/tukar tambah.

## Checklist Sebelum Coding

- [ ] Alokasi FIFO penjualan tersedia.
- [ ] Informasi layer/cost stok pembelian tersedia.
- [ ] Aturan pembayaran/refund dipahami.

## Kriteria Penerimaan

- [ ] Retur tidak bisa melebihi qty transaksi asal.
- [ ] Retur penjualan mengembalikan stok dengan cost dari alokasi asal.
- [ ] Retur pembelian mengurangi stok tanpa membuat minus.
- [ ] Piutang/hutang/payment teradjust sesuai status.

## Verifikasi

- [ ] Feature test sales return partial/full.
- [ ] Feature test purchase return insufficient stock Ditolak.
- [ ] Feature test receivable/payable adjustment.
