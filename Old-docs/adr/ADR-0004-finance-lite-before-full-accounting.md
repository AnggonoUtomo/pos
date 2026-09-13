# ADR-0004: Finance Lite Sebelum Full Accounting

## Status

Diterima

## Tanggal

2026-09-12

## Konteks

Fase awal sudah memiliki domain kompleks: POS, pembelian, retur, FIFO, multi satuan, multi payment, pajak, diskon, hutang, dan piutang. Full accounting akan menambah chart of accounts, jurnal, buku besar, neraca, laba rugi formal, dan tutup tahun.

## Keputusan

Fase 1 memakai Finance Lite.

Finance Lite mencakup:

- Kas/bank.
- Multi metode pembayaran.
- Hutang supplier.
- Piutang pelanggan.
- Pembayaran hutang/piutang.
- Refund retur.
- Laporan kas, hutang, piutang, pajak, dan laba FIFO.

Semua transaksi tetap journal-Siap agar full accounting bisa dibangun di fase berikutnya.

## Alternatif Yang Dipertimbangkan

### Full Accounting Dari Awal

- Kelebihan: laporan formal lengkap.
- Kekurangan: scope terlalu besar dan berisiko memperlambat POS core.
- Ditolak untuk fase 1.

### Tanpa Finance Module

- Kelebihan: lebih cepat.
- Kekurangan: multi payment, hutang, piutang, refund, dan kas tidak punya Boundary jelas.
- Ditolak.

## Konsekuensi

- Tidak ada jurnal manual, buku besar, neraca, atau tutup tahun di fase 1.
- Payment dan receivable/payable harus tetap rapi.
- Event transaksi harus menyimpan breakdown agar jurnal dapat digenerate nanti.
