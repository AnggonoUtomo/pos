# Tasks: Pengaturan Global Dan Pagination

## Sebelum Mulai

- [x] Baca AGENTS, README, arsitektur/struktur dan module Operasional/laporan.
- [x] Petakan query pagination, tabel dan pengecualian.
- [x] Konfirmasi default/opsi/override sementara/reset halaman/filter.

## Increment 1: Backend Preferensi Dan Read Query

- [x] Test RED kemudian GREEN untuk default10, override, input invalid,
  izin/audit/version dan lintas endpoint.
  - Acceptance: ukuran dibatasi backend, tidak mengubah default dari tabel.
- [x] Tambah migration, contract/binding dan konsumsi pada repository daftar.
  - Acceptance: tidak mengimpor privat lintas module; summary/saldo utuh.
- [x] Verifikasi test terfokus, format dan commit save point.

## Increment 2: UI Operasional Dan Seluruh Daftar

- [x] Tab vertikal Umum/Cetak/Backup; form/shortcut/cetak/manual backup tetap.
- [x] Pagination shared pada seluruh tabel daftar, termasuk stok laporan.
  - Acceptance: ukuran, rentang/total, first/prev/nomor/next/last, filter tetap,
    loading/empty/boundary dan navigasi keyboard; override bukan setting global.
- [x] Update panduan dan test UI; desktop/tablet representatif light/dark.
- [x] Gate regresi backend/frontend sekali; perbaiki hanya kegagalan terkait.

Regresi penuh tetap FAIL karena gap existing, bukan dinyatakan lulus.
User menyetujui gap terpisah; detail reproduksi dan focused PASS ada pada README.
QA browser PASS; koreksi akhir lebar tombol nomor panjang diikuti build/lint
frontend yang terdampak tanpa mengulang seluruh suite.

## Increment 3: Aktivasi Dan Delivery

- [x] Backup terverifikasi dan migrate hanya schema pekerjaan ini.
- [ ] Catat bukti, audit final scope, commit dan push; LSP dikecualikan.
