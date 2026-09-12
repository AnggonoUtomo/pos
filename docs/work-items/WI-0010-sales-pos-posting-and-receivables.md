# WI-0010: Posting Penjualan POS dan Piutang

Status: Draf

## Tujuan

Membangun sales/POS posting yang memilih gudang, memakai customer-level pricing, consume FIFO, dan mencatat payment/receivable.

## Rujukan

- `docs/SPEC.md`
- `docs/adr/ADR-0003-inventory-fifo-no-negative-stock.md`
- `docs/adr/ADR-0004-finance-lite-before-full-accounting.md`
- `docs/QA-AUTOMATION.md`

## Modul Target

- Category: Sales
- Module: POS
- Namespace/path: `Modules/Sales/POS`
- Jenis pekerjaan: POS fullscreen, sales posting, FIFO consume, payment, dan receivable

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

## Rencana Increment

| Increment | Fokus | Checklist Sebelum | Checklist Sesudah | QA automated | Status |
| --- | --- | --- | --- | --- | --- |
| INC-01 | Sales/POS draft input dan warehouse selection | - [ ] POS layout tersedia<br>- [ ] Warehouse contract tersedia | - [ ] User dapat memilih warehouse | feature test warehouse selection, `npm run build` jika UI dibuat | Draf |
| INC-02 | Pricing, multi unit, discount, tax snapshot | - [ ] Catalog pricing contract tersedia | - [ ] Harga/satuan/pajak/diskon tersnapshot | feature test POS pricing snapshot | Draf |
| INC-03 | Posting stok dan FIFO COGS | - [ ] Inventory consume contract tersedia | - [ ] FIFO allocation tersimpan<br>- [ ] Stok kurang ditolak | feature test POS posting, insufficient stock, FIFO COGS | Draf |
| INC-04 | Multi payment dan receivable | - [ ] Rule walk-in/piutang dipahami | - [ ] Multi payment valid<br>- [ ] Walk-in tidak bisa piutang | feature test multi payment and receivable | Draf |
| INC-05 | POS fullscreen UI | - [ ] Target desktop/tablet dipahami | - [ ] UI build lulus dan flow utama tervalidasi | `npm run build`, pemeriksaan manual POS | Draf |

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
