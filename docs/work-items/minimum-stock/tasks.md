# Tasks: Stok Minimum

## Sebelum Mulai

- [x] Baca AGENTS, indeks, arsitektur, struktur, Stock dan dashboard terkait.
- [x] User menyetujui <=, minimum 0, nonaktif terpisah, hanya peringatan.
- [x] Batasi scope dan pertahankan LSP user/Old-docs.

## Increment 1: Backend

- [x] Schema ULID/unique pair/soft delete/version dan aturan exact.
  - Acceptance: belum diatur tidak diperingatkan; saldo kosong 0; sama batas
    tetap low; master nonaktif tidak menambah alert; tanpa mutasi ledger.
- [x] CRUD, permission, audit/no-op/stale/rollback dan pagination.
  - Verification: focused RED/GREEN dan review lock/filter.
  - Bukti: RED contract belum bound; GREEN 13 test / 117 assertions sebelum
    tambahan cleanup master nonaktif. Uji ulang terdampak dicatat di bawah.
- [x] Commit save point setelah gate terfokus lulus.
  - Gate backend: 24 test / 212 assertions PASS (minimum, seeder, lifecycle).
    Penghapusan boleh saat master nonaktif; pemulihan tetap memerlukan master
    aktif. Allowlist lifecycle hanya menerima tabel minimum yang kosong.

## Increment 2: Workspace dan Dashboard

- [ ] Pengaturan/form Indonesia, daftar/filter/restore, shortcut dan F1.
- [ ] Dashboard membaca ringkasan berizin via public contract; refresh aktual.
- [ ] Demo CCTV/kelistrikan aman, panduan operator dan roadmap.
- [ ] Typecheck/lint/build/page-guides dan commit save point.

## Increment 3: QA dan Delivery

- [ ] Native MySQL exact dan unique/soft delete; tidak memakai data toko.
- [ ] Browser alur berubah desktop/tablet via MCP atau fallback tercatat.
- [ ] Satu regresi penuh, ulang hanya pemeriksaan terdampak bila perlu.
- [ ] Backup verified, aktivasi additive dan fingerprint existing identik.
- [ ] Dokumentasi bukti, commit, push dan verifikasi remote.
