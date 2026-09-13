# WI-0009: Posting Pembelian dan Hutang

Status: Draf

## Tujuan

Membangun Purchase order/invoice posting yang menambah stok, membuat FIFO layer, dan mencatat payable/payment.

## Rujukan

- `docs/SPEC.md`
- `docs/adr/ADR-0004-finance-lite-before-full-accounting.md`
- `docs/QA-AUTOMATION.md`

## Modul Target

- Category: Purchasing
- Module: PurchaseOrders
- Namespace/path: `app/Modules/Purchasing/PurchaseOrders`
- Jenis pekerjaan: transaksi pembelian, penerimaan, FIFO masuk, hutang finance lite

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

## Rencana Increment

| Increment | Fokus | Checklist Sebelum | Checklist Sesudah | QA automated | Status |
| --- | --- | --- | --- | --- | --- |
| INC-01 | Purchase draft dan receive posting | - [ ] Supplier dan warehouse tersedia | - [ ] Draft tidak mengubah stok<br>- [ ] Receive mengubah stok | feature test purchase receive | Draf |
| INC-02 | FIFO layer dari pembelian | - [ ] Cost source disepakati | - [ ] FIFO layer bertambah sesuai qty/cost | feature test purchase FIFO layer | Draf |
| INC-03 | Payable finance lite | - [ ] Rule hutang phase 1 dipahami | - [ ] Partial payment dan status hutang berjalan | feature test purchase payment/payable | Draf |

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
