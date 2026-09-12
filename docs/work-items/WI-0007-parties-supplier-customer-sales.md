# WI-0007: Master Supplier Pelanggan dan Sales

Status: Draf

## Tujuan

Membuat master pihak eksternal: supplier, customer, customer level, dan sales.

## Rujukan

- `docs/SPEC.md`
- `docs/PRD.md`

## Scope

Masuk scope:

- Supplier.
- Customer.
- Customer level.
- Salesperson.
- Default tax mode/customer level.

Di luar scope:

- Komisi sales kompleks.
- Wilayah pelanggan detail.

## Checklist Sebelum Coding

- [ ] Customer level contract cocok dengan Catalog pricing.
- [ ] Default walk-in customer rule jelas.

## Kriteria Penerimaan

- [ ] Customer dapat dikaitkan dengan customer level.
- [ ] Supplier dapat dipakai purchasing.
- [ ] Salesperson dapat dipakai sales invoice.
- [ ] Master changes tercatat activity log.

## Verifikasi

- [ ] Feature tests CRUD utama.
- [ ] Authorization tests untuk akses master.
