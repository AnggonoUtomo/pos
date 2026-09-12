# WI-0013: Laporan Operasional Fase 1

Status: Draf

## Tujuan

Membangun laporan operasional fase 1 untuk penjualan, pembelian, stok, laba, kas, pajak, hutang, dan piutang.

## Rujukan

- `docs/PRD.md`
- `docs/SPEC.md`
- `docs/ARCHITECTURE.md`

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

## Kriteria Penerimaan

- [ ] Laporan hanya memakai posted transaction kecuali filter draft dinyatakan eksplisit.
- [ ] Laporan stok konsisten dengan stock movement.
- [ ] Laporan laba memakai COGS FIFO.
- [ ] Receivable/Laporan hutang sesuai payment status.

## Verifikasi

- [ ] Feature/query tests untuk laporan inti.
- [ ] Pemeriksaan manual memakai data contoh.
