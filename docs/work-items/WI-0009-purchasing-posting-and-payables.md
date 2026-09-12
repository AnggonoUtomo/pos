# WI-0009: Posting Pembelian dan Hutang

Status: Draf

## Tujuan

Membangun Purchase order/invoice posting yang menambah stok, membuat FIFO layer, dan mencatat payable/payment.

## Rujukan

- `docs/SPEC.md`
- `docs/adr/ADR-0004-finance-lite-before-full-accounting.md`

## Scope

Masuk scope:

- Purchase order.
- Purchase invoice draft/posting.
- Multi-unit purchase lines.
- Discount and tax.
- Pembayaran sebagian/lunas.
- Supplier payable.
- FIFO layer creation.

Di luar scope:

- Retur pembelian dikerjakan di WI-0011.
- Full accounting journal.

## Checklist Sebelum Coding

- [ ] Inventory contract tersedia.
- [ ] Kontrak pembayaran/hutang tersedia atau dibuat minimal.
- [ ] Tax include/exclude formula disepakati.

## Kriteria Penerimaan

- [ ] Pembelian posted menambah stok dan FIFO layer.
- [ ] Draft pembelian tidak mengubah stok.
- [ ] Partial payment membuat payable.
- [ ] Tax dan discount tersnapshot.
- [ ] Activity log posting tersedia.

## Verifikasi

- [ ] Feature test post purchase.
- [ ] Feature test draft has no stock effect.
- [ ] Feature test partial payment payable.
