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

- [x] Pengaturan/form Indonesia, daftar/filter/restore, shortcut dan F1.
- [x] Dashboard membaca ringkasan berizin via public contract; refresh aktual.
- [x] Demo CCTV/kelistrikan aman, panduan operator dan roadmap.
- [x] Typecheck/lint/build/page-guides dan commit save point.
  - Focused minimum/seeder: 15 test / 151 assertions PASS. Dashboard hanya
    mengirim low_stock untuk permission view, tanpa membuka akses laporan.
  - tsc --noEmit, eslint resources/js, build 2942 modules dan page-guides
    30 page PASS. Setelah koreksi class layout, hanya build/layout diulang.

## Increment 3: QA dan Delivery

- [x] Native MySQL exact dan unique/soft delete; tidak memakai data toko.
  - minimum-stock-mysql.php PASS: migrate, DECIMAL(20,6), duplikat termasuk
    terhapus, restore, summary; fingerprint stok/FIFO/sales/payment identik.
    Database generated dibersihkan pada finally.
- [x] Browser alur berubah desktop/tablet via MCP atau fallback tercatat.
  - Chrome DevTools MCP cached bin/stdio isolated: login, DOM, screenshot PASS.
    Namespace MCP tidak tersedia; executable existing dipakai tanpa instalasi.
  - Chrome/Playwright existing: CRUD/status/restore/reload, Indonesia, F1,
    shortcut, filter, dashboard refresh/link dan permission PASS; console bersih.
    Bukti: storage/framework/testing/mvp-browser-21ca3427e9892536/visual-report.json.
  - Screenshot awal tablet memperlihatkan pencarian menyempit; class diperbaiki
    agar filter turun baris. Layout-only PASS, input >=200px tanpa overflow:
    storage/framework/testing/mvp-browser-65d2108704046319/visual-report.json.
    Kedua screenshot final desktop/tablet diperiksa visual.
- [x] Satu regresi penuh, ulang hanya pemeriksaan terdampak bila perlu.
  - php artisan test --compact: 965 PASS / 7703 assertions, 138,98 detik.
    Tidak mengulang seluruh suite setelah perubahan class layout/dokumen saja.
- [x] Backup verified, aktivasi additive dan fingerprint existing identik.
  - pos:backup --verify: backups/pos-20261031-214921-788ba7a4.sql, restore
    disposable identik. Nama file mengikuti runtime existing, bukan perubahan TZ.
  - activate-minimum-stock-local.php PASS; tabel baru kosong, permission catalog
    additive/idempotent tanpa grant, seluruh row/schema existing identik.
    Migration batch35 Ran, lima route aktif/cache, HTTPS login 200 tanpa bypass TLS.
- [ ] Dokumentasi bukti, commit, push dan verifikasi remote.
