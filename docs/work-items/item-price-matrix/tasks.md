# Tasks: Harga Jual Pada Form Barang

## Sebelum Mulai

- [x] AGENTS.md, arsitektur, struktur dan module terkait dibaca.
- [x] User menyetujui matrix wajib lengkap sebelum barang disimpan.
- [x] Scope/non-scope, PRD, plan dan boundary ditulis sebelum coding.
- [x] UI label/angka Indonesia, harga pokok terpisah dari harga jual.

## Increment 1: Fondasi Pricing

- [x] Schema ULID/unique/FK dan audit item_prices.
  - Acceptance: price mutable, no-op tanpa audit tambahan, rollback atomic.
  - Verification: focused Pricing tests, migration up/down.
- [x] Published contract pricing dan lookup satuan jual.
  - Acceptance: tidak mengimpor model privat lintas module; matrix lengkap,
    positif/scale2, level aktif, satuan milik barang; tidak ada fallback.
  - Verification: action/contract tests termasuk invalid/duplicate/missing.
- [x] Guard level yang dirujuk harga dan commit increment.
  - Acceptance: super-system tidak melewati invariant, harga trashed terjaga.
  - Verification: Parties regression, diff check/Pint.

Increment 1 Passed: Pricing RED sebelum contract ada; sesudah implementasi
ItemPrices/Parties HTTP/Application/Seeder 19 tests/143 assertions PASS.
Pint PASS. SQLite menyimpan amount sebagai string exact mengikuti pola Stock;
MySQL DECIMAL(20,2). Nilai maksimum tetap exact pada read DTO. Migration
additive lokal melalui apply-parties-local PASS; akun/grant/master/ledger
fingerprint existing identik. Belum ada harga user yang diisi otomatis.

## Increment 2: Workflow Barang Dan UI

- [x] Save/list Items memakai public pricing boundary.
  - Acceptance: harga + katalog + audit commit/rollback bersama; create/edit
    publik menolak harga kosong atau matrix stale.
  - Verification: HTTP authorization/validation/rollback dan regresi existing.
- [x] Tambahkan matrix harga dekat editor satuan dan formatter rupiah exact.
  - Acceptance: row per satuan jual, level aktif sebagai kolom, simpan/reopen
    harga nyata; error/loading/Sonner/shortcut dan format Indonesia.
  - Verification: unit parser, lint/typecheck/build dan browser.
- [x] Seed harga demo tanpa menimpa existing lalu commit increment.
  - Acceptance: hanya SKU demo yang dikenal, relation valid, repeat idempotent.
  - Verification: seeder tests termasuk production refusal/preservation.

Increment 2 PASS: HTTP/action/seeder/regresi, formatter exact, ESLint/TS/build
dan browser lulus. Fixture HTTP existing dilengkapi level/harga eksplisit,
bukan melemahkan kewajiban matrix. Seeder diuji RED sebelum class ada, lalu
GREEN untuk 54 harga, idempotency, harga edit/trashed, production refusal dan
rollback bila prerequisite terakhir hilang. Restore harga mempertahankan ID.
Screenshot matrix Light/Dark telah diperiksa; animasi dinonaktifkan saat capture.

## Increment 3: QA Dan Delivery

- [x] PHP suite, lint/typecheck/build, route/migration dan MySQL disposable.
  - Acceptance: gate lulus, current reads dan no partial save terverifikasi.
  - Verification: automated tests/QA script, command dicatat.
- [x] DevTools atau fallback browser desktop/tablet.
  - Acceptance: harga tersimpan/reload, invalid/missing/readonly, framing,
    console bersih, format rupiah; mobile tidak diuji.
  - Verification: report/screenshot lokal ignored.
- [x] Terapkan additive migration/demo harga lokal dan isi checklist.
  - Acceptance: akun/grant/stok/ledger existing tetap, tidak reset database.
  - Verification: fingerprint, git diff/status.
- [x] Commit penutupan, push dan cocokkan remote HEAD.
  - Acceptance: hanya file scope pekerjaan terkirim, artefak user tidak ikut.
  - Verification: staged diff/check, push dan ls-remote.

## Bukti Verifikasi 14 Oktober 2026

- `php artisan test --compact`: 270 tests / 1672 assertions PASS.
- `vendor/bin/pint --dirty --test`: PASS.
- `npx eslint resources/js`: PASS; `npx tsc --noEmit`: PASS.
- `node scripts/qa/item-ratio-format.mjs`: PASS, termasuk maksimum harga
  999999999999999999.99 exact dan penolakan lebih dari dua desimal.
- `npm run build`: PASS, 2696 modules transformed. Tidak mengubah theme CSS.
- `php scripts/qa/item-prices-mysql.php`: PASS, database generated disposable;
  54 harga/idempotency, maksimum DECIMAL exact, current locking read level dan
  unit setelah snapshot REPEATABLE READ, rollback katalog/harga/audit; database
  dibersihkan. Bukan bukti seluruh interleaving/load concurrency produksi.
- `php artisan route:list --path=inventory/items`: enam route existing PASS.
- `php artisan migrate:status`: seluruh migration Ran termasuk item_prices.
- Chrome DevTools MCP: BLOCKED, tidak ada tool namespace DevTools yang dapat
  ditemukan pada sesi ini. Fallback memakai Playwright Core existing, tanpa
  install dependency, profile/credential/database QA terisolasi.
- `node scripts/qa/inventory-browser.mjs`: PASS full Inventory regression;
  report ignored `storage/framework/testing/inventory-browser-23e2e820a3683e16`.
- Re-run `--items-only`: PASS, reload harga persisted, matrix required/zero,
  flags, CRUD/restore, readonly, shortcut/palette, sidebar expanded/collapsed,
  Light/Dark/Clean, desktop 1440x1000 dan tablet 1024x768 / 768x1024; console
  tanpa error/warning. Screenshot stabil diperiksa manual. Report ignored:
  `storage/framework/testing/inventory-browser-448f6609d41e072f`.
- `php scripts/qa/apply-prices-local.php`: PASS dua kali, 54 harga lokal;
  fingerprint akun/grant/master/stok/ledger dan harga existing identik.
  Migration additive sudah applied, tidak ada reset database.
- Smoke `http://pos.test/login`: 200, built assets tersedia, tanpa Vite dev
  client. Alur CRUD authenticated diuji di fixture disposable, bukan memakai
  credential user lokal.
- `git diff --check` dan staged check: PASS pada Increment 2 (`0e1a851`).
- Delivery fitur/QA `e5e8732` telah di-push ke origin/main dan dikonfirmasi
  `git ls-remote` cocok dengan HEAD lokal; divergence 0/0. Catatan penutupan
  checklist dikirim sebagai commit dokumentasi sesudah verifikasi tersebut.
  SampleUI dan empat artefak LSP user tetap untracked, tidak ikut commit.

## Batas Yang Tetap

Transaksi posted POS belum tersedia. Integrasi katalog/keranjang nyata menjadi
work item terpisah pos-live-cart, bukan bagian perubahan master harga. Harga pokok,
FIFO, stok, akun/grant, theme, SampleUI dan Old-docs tidak diubah. Legacy barang
tanpa harga harus dilengkapi saat edit; demo hanya sembilan SKU dikenal dan
tidak menimpa item dengan harga existing/trashed. Mobile tidak diuji.
