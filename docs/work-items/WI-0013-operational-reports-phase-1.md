# WI-0013: Laporan Operasional Fase 1

Status: Draf

## Tujuan

Membangun laporan operasional fase 1 untuk penjualan, pembelian, stok, laba, kas, pajak, hutang, dan piutang.

## Rujukan

- `docs/PRD.md`
- `docs/SPEC.md`
- `docs/ARCHITECTURE.md`
- `docs/QA-AUTOMATION.md`

## Modul Target

- Category: Reporting
- Module: OperationalReports
- Namespace/path: `Modules/Reporting/OperationalReports`
- Jenis pekerjaan: laporan operasional fase 1 berbasis transaksi posted

## Scope

Masuk scope:

- Laporan penjualan.
- Laporan pembelian.
- Laporan saldo stok.
- Laporan kartu stok.
- Laporan laba FIFO.
- Laporan mutasi kas/bank.
- Laporan pajak.
- Laporan piutang.
- Laporan hutang.

Di luar scope:

- Neraca.
- Buku besar.
- Laba rugi akuntansi formal.
- Grafik kompleks.

## Checklist Sebelum Coding

- [ ] Data transaksi posted tersedia.
- [ ] Reporting tidak mengubah state.
- [ ] Filter minimum ditentukan.

## Rencana Increment

| Increment | Fokus | Checklist Sebelum | Checklist Sesudah | QA automated | Status |
| --- | --- | --- | --- | --- | --- |
| INC-01 | Laporan stok dan stock card | - [ ] Stock movement tersedia | - [ ] Laporan stok sesuai movement | feature test stock report query | Draf |
| INC-02 | Laporan sales/purchase | - [ ] Sales dan purchase posted tersedia | - [ ] Laporan memakai posted transaction | feature test sales/purchase report query | Draf |
| INC-03 | Laporan laba FIFO | - [ ] FIFO COGS tersimpan | - [ ] Laba memakai COGS FIFO | feature test gross profit FIFO report | Draf |
| INC-04 | UI laporan dasar | - [ ] Filter laporan disepakati | - [ ] Build lulus dan filter utama berjalan | `npm run build`, pemeriksaan manual laporan | Draf |

## Kriteria Penerimaan

- [ ] Laporan hanya memakai posted transaction kecuali filter draft dinyatakan eksplisit.
- [ ] Laporan stok konsisten dengan stock movement.
- [ ] Laporan laba memakai COGS FIFO.
- [ ] Receivable/Laporan hutang sesuai payment status.

## Verifikasi

- [ ] Feature/query tests untuk laporan inti.
- [ ] Pemeriksaan manual memakai data contoh.
