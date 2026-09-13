# ADR-004: Finance Lite Sebelum Full Accounting

## Status

Accepted

## Tanggal

2026-09-12

## Konteks

Project fase awal membutuhkan pembayaran, kas/bank sederhana, dan ringkasan
operasional. Full accounting memerlukan chart of account, jurnal, posting
akuntansi, closing, dan rekonsiliasi yang lebih besar dari kebutuhan MVP.

## Keputusan

Fase awal memakai Finance Lite. Finance Lite mencatat payment method, kas/bank
operasional, payment record, piutang/utang dasar, dan ringkasan pembayaran.
Full accounting ditunda sampai kebutuhan dan scope-nya diputuskan melalui ADR
baru.

## Konsekuensi

- Sales dan purchasing dapat menyelesaikan payment tanpa membangun general
  ledger penuh.
- Activity log tidak menjadi ledger finance.
- Payment record posted tidak diedit bebas; koreksi memakai void/reversal atau
  dokumen koreksi yang audited.
- Desain data harus tetap menyisakan ruang migrasi ke accounting penuh.

## Verifikasi

- Payment tersimpan sebagai record operasional yang terpisah dari activity log.
- Multi payment tidak membuat total bayar, kembalian, piutang, atau utang tidak
  konsisten.
- Tidak ada asumsi chart of account penuh pada fase 1.
