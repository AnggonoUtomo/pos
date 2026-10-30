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

- [ ] Tab vertikal Umum/Cetak/Backup; form/shortcut/cetak/manual backup tetap.
- [ ] Pagination shared pada seluruh tabel daftar, termasuk stok laporan.
  - Acceptance: ukuran, rentang/total, first/prev/nomor/next/last, filter tetap,
    loading/empty/boundary dan navigasi keyboard; override bukan setting global.
- [ ] Update panduan dan test UI; desktop/tablet representatif light/dark.
- [ ] Gate regresi backend/frontend sekali; perbaiki hanya kegagalan terkait.

## Increment 3: Aktivasi Dan Delivery

- [ ] Backup terverifikasi dan migrate hanya schema pekerjaan ini.
- [ ] Catat bukti, audit final scope, commit dan push; LSP dikecualikan.
