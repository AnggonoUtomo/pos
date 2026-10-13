# Tasks: Format Rasio Indonesia

## Sebelum Mulai

- [x] AGENTS.md, indeks docs dan dokumen Inventory/Items dibaca.
- [x] Target, scope dan boundary penyimpanan/UI dijelaskan ke user.
- [x] Plan/acceptance/QA ditulis sebelum coding.

## Increment 1: Format Dan Integrasi

- [x] Tambahkan formatter/parser string exact lokal Items.
  - Acceptance: 1/6 tanpa nol akhir; 1,5; 1.000,5; precision tidak berkurang.
  - Verification: scripts/qa/item-ratio-format.mjs.
- [x] Terapkan pada initial form dan transport submit.
  - Acceptance: input koma disimpan canonical; input ambigu tidak dikirim;
    error tidak menghapus isian; rasio dasar tetap readonly 1.
  - Verification: typecheck/lint, browser CRUD dan backend Items regression.
- [x] Verifikasi, perbarui docs, commit dan push.
  - Acceptance: build asset terbaru, desktop/tablet tidak overflow, schema/data
    pengguna tidak dimutasi, no rounding, theme dan Old-docs tidak disentuh.
  - Verification: build, DevTools atau fallback dengan catatan, diff/remote check.

## Hasil

Increment 1 Passed, 13 Oktober 2026.

| Command/pemeriksaan | Hasil |
| --- | --- |
| node scripts/qa/item-ratio-format.mjs | PASS; integer, fraction, grouping, ambiguous input dan round-trip batas DECIMAL exact |
| npx tsc --noEmit | PASS |
| npx eslint resources/js | PASS |
| npm run build | PASS; 2695 modules |
| php artisan test --compact --filter='Items\|InventoryPagesTest\|ItemLookup\|UnitLookup' | PASS; 63 tests, 355 assertions |
| node scripts/qa/inventory-browser.mjs --items-only | PASS |
| Chrome DevTools MCP | BLOCKED; tool tidak tersedia, memakai fallback Playwright existing |
| git diff --check | PASS |
| Asset virtual host pos.test | Manifest terbaru ada di HTML, asset response 200 |

Browser SQLite disposable: initial editor base 1 dan tambahan 12 tanpa nol
desimal; 0,5 dikonversi lalu ditolak invariant backend; input titik ambigu 1.5
ditolak lokal dan tetap berada di form. 24,000001 berhasil disimpan/dibuka ulang
tanpa digit berubah. CRUD/restore, flag, barcode 0, guard referensi, readonly,
shortcut/palette dan dark/clean juga lulus. Desktop 1440x1000 expanded/collapsed,
tablet 1024x768/768x1024, console/page error kosong; screenshot form portrait
ditinjau, tidak ada overlap. Mobile tidak diuji sesuai scope project.
Artefak ignored: storage/framework/testing/inventory-browser-9fb3b834a2446a6f.

Tidak ada migration, seed, atau mutation database MySQL user. Perubahan hanya
format dan boundary input UI; schema/API, FIFO, stok, theme, Old-docs dan
artefak user tidak disentuh. Formatter/parser berada dekat fitur Items,
bukan impor helper privat Stock atau abstraction global baru.
