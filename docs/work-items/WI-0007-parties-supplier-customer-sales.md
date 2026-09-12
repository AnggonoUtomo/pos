# WI-0007: Master Supplier Pelanggan dan Sales

Status: Draf

## Tujuan

Membuat master pihak eksternal: supplier, customer, customer level, dan sales.

## Rujukan

- `docs/SPEC.md`
- `docs/PRD.md`
- `docs/QA-AUTOMATION.md`

## Modul Target

- Category: Sales
- Module: Parties
- Namespace/path: `app/Modules/Sales/Parties`
- Jenis pekerjaan: master customer, supplier, sales person, dan customer level

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

## Rencana Increment

| Increment | Fokus | Checklist Sebelum | Checklist Sesudah | QA automated | Status |
| --- | --- | --- | --- | --- | --- |
| INC-01 | Customer dan customer level | - [ ] Field customer minimum disetujui | - [ ] Customer level dapat dipakai pricing | feature test customer level | Draf |
| INC-02 | Supplier dan sales person | - [ ] Field supplier/sales disetujui | - [ ] Master supplier/sales berjalan | feature test parties CRUD | Draf |
| INC-03 | Walk-in customer rule | - [ ] Rule piutang walk-in dipahami | - [ ] Walk-in dapat dikenali oleh Sales/POS | test walk-in customer rule | Draf |

## Kriteria Penerimaan

- [ ] Customer dapat dikaitkan dengan customer level.
- [ ] Supplier dapat dipakai purchasing.
- [ ] Salesperson dapat dipakai sales invoice.
- [ ] Master changes tercatat activity log.

## Verifikasi

- [ ] Feature tests CRUD utama.
- [ ] Authorization tests untuk akses master.
