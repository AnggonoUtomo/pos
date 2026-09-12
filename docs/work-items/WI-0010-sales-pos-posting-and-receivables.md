# WI-0010: Posting Penjualan POS dan Piutang

Status: Draf

## Tujuan

Membangun sales/POS posting yang memilih gudang, memakai customer-level pricing, consume FIFO, dan mencatat payment/receivable.

## Rujukan

- `docs/SPEC.md`
- `docs/adr/ADR-0003-inventory-fifo-no-negative-stock.md`
- `docs/adr/ADR-0004-finance-lite-before-full-accounting.md`

## Scope

Masuk scope:

- POS transaction posting.
- Sales invoice draft/posting.
- Warehouse selection.
- Customer-level pricing.
- Multi-unit sales.
- Discount and tax.
- Multi payment.
- Receivable for partial payment.
- FIFO allocation and COGS.

Di luar scope:

- Retur penjualan dikerjakan di WI-0011.
- Advanced receipt designer.

## Checklist Sebelum Coding

- [ ] Catalog pricing tersedia.
- [ ] Inventory FIFO contract tersedia.
- [ ] Baseline pembayaran/piutang tersedia.
- [ ] POS UI target desktop/tablet dipahami.

## Kriteria Penerimaan

- [ ] POS posted langsung berdampak ke stok dan pembayaran.
- [ ] Backoffice invoice draft tidak berdampak stok.
- [ ] Stok kurang ditolak.
- [ ] FIFO allocation disimpan.
- [ ] Multi payment valid.
- [ ] Walk-in tidak boleh piutang.

## Verifikasi

- [ ] Feature test POS posting.
- [ ] Feature test insufficient stock.
- [ ] Feature test FIFO COGS.
- [ ] Feature test multi payment.
- [ ] Frontend build jika UI dibuat.
