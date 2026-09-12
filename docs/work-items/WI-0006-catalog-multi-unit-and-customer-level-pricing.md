# WI-0006: Katalog Multi Satuan dan Harga Level Pelanggan

Status: Draf

## Tujuan

Membangun catalog foundation untuk item, satuan, konversi, barcode, customer level price, dan taxable flag.

## Rujukan

- `docs/SPEC.md`
- `docs/adr/ADR-0003-inventory-fifo-no-negative-stock.md`
- `docs/QA-AUTOMATION.md`

## Modul Target

- Category: Inventory
- Module: Catalog
- Namespace/path: `Modules/Inventory/Catalog`
- Jenis pekerjaan: master barang, multi satuan, barcode, dan harga level pelanggan

## Scope

Masuk scope:

- Item.
- Unit.
- Unit conversion to base unit.
- Category/brand dasar.
- Barcode.
- Customer level price per item and unit.
- Taxable flag.

Di luar scope:

- Quantity-based price break.
- Promo periode.
- Point pelanggan.

## Checklist Sebelum Coding

- [ ] Base-unit rule dipahami.
- [ ] Snapshot kebutuhan transaksi dipahami.
- [ ] Unique constraint item/barcode dirancang.

## Rencana Increment

| Increment | Fokus | Checklist Sebelum | Checklist Sesudah | QA automated | Status |
| --- | --- | --- | --- | --- | --- |
| INC-01 | Item, unit, dan base unit | - [ ] Field item/unit disetujui | - [ ] Item punya base unit valid | unit/feature test item unit | Draf |
| INC-02 | Unit conversion dan barcode | - [ ] Aturan konversi base unit jelas | - [ ] Konversi tidak ambigu<br>- [ ] Barcode unik | unit test conversion, feature test barcode uniqueness | Draf |
| INC-03 | Customer level price | - [ ] Level pelanggan tersedia atau contract placeholder jelas | - [ ] Lookup harga per item/unit/level berjalan | feature test price lookup | Draf |
| INC-04 | UI dan audit harga | - [ ] Flow admin catalog disetujui | - [ ] Build lulus<br>- [ ] Activity log harga tercatat | `npm run build`, test activity log price terkait | Draf |

## Kriteria Penerimaan

- [ ] Item punya base unit.
- [ ] Unit conversion valid dan tidak ambigu.
- [ ] Harga dapat dicari berdasarkan item, unit, customer level.
- [ ] Fallback harga default jelas.
- [ ] Activity log untuk perubahan harga penting.

## Verifikasi

- [ ] Unit test conversion.
- [ ] Feature test price lookup.
- [ ] `npm run build` jika UI berubah.
