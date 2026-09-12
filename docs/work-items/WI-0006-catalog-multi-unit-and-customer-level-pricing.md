# WI-0006: Katalog Multi Satuan dan Harga Level Pelanggan

Status: Draf

## Tujuan

Membangun catalog foundation untuk item, satuan, konversi, barcode, customer level price, dan taxable flag.

## Rujukan

- `docs/SPEC.md`
- `docs/adr/ADR-0003-inventory-fifo-no-negative-stock.md`

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
