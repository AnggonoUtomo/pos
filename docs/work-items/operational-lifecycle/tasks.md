# Tasks: Lifecycle Operasional

## Sebelum Mulai

- [x] AGENTS/arsitektur/struktur dan module terkait dibaca.
- [x] Database production diperiksa read-only; user menyetujui seluruh data bisnis diganti.
- [x] User menegaskan penggantian hanya sekali; akun/akses/settings/audit/backup dilindungi.

## Increment 1: Dataset Dan Pengaman

- [x] Test skenario utuh dan pengaman sebelum implementasi selesai.
  - Acceptance: saldo/FIFO/uang exact, idempotensi, rollback, guard production/nonempty.
  - Verification: focused test RED lalu GREEN pada SQLite disposable.
- [x] Seeder master module dan orchestrator tanpa reset otomatis.
  - Acceptance: transaksi memakai use case existing, harga semua level/satuan lengkap.
  - Verification: focused test serta Pint scoped.
- [x] Helper penggantian sekali.
  - Acceptance: DELETE transactional sesuai FK, fingerprint cocok, audit sekali,
    seluruh tabel protected terjaga, reset kedua ditolak.
  - Verification: fixture populated + rollback + replay test.

## Increment 2: Aktivasi Dan Panduan

- [x] Satu gate backend lengkap dan backup restore verified.
  - Acceptance: tidak ada regresi, backup baru dapat dipulihkan tanpa menimpa toko.
  - Verification: full PHPUnit sekali, pos:backup --verify.
- [x] Ganti database bisnis satu kali dan verifikasi.
  - Acceptance: data lama hilang, skenario/angka expected cocok, protected fingerprint identik.
  - Verification: MySQL apply dengan fingerprint pinned, rerun seeder no-op, reset kedua ditolak.
- [x] Panduan lifecycle aktual dan handoff.
  - Acceptance: urutan belajar, dokumen posted/draft, harga/satuan dan hasil expected jelas.
  - Verification: report readonly, link check, diff scoped; tidak mengulang semua QA browser/tema.
- [ ] Commit dan push scoped.
  - Acceptance: file LSP user/backup/temporary tidak masuk commit.
  - Verification: scope, push, SHA remote dan divergence 0/0.
