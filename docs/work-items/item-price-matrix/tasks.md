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

## Increment 2: Workflow Barang Dan UI

Increment 1 Passed: Pricing RED sebelum contract ada; sesudah implementasi
ItemPrices/Parties HTTP/Application/Seeder 19 tests/143 assertions PASS.
Pint PASS. SQLite menyimpan amount sebagai string exact mengikuti pola Stock;
MySQL DECIMAL(20,2). Nilai maksimum tetap exact pada read DTO. Migration
additive lokal melalui apply-parties-local PASS; akun/grant/master/ledger
fingerprint existing identik. Belum ada harga user yang diisi otomatis.

- [ ] Save/list Items memakai public pricing boundary.
  - Acceptance: harga + katalog + audit commit/rollback bersama; create/edit
    publik menolak harga kosong atau matrix stale.
  - Verification: HTTP authorization/validation/rollback dan regresi existing.
- [ ] Tambahkan matrix harga dekat editor satuan dan formatter rupiah exact.
  - Acceptance: row per satuan jual, level aktif sebagai kolom, simpan/reopen
    harga nyata; error/loading/Sonner/shortcut dan format Indonesia.
  - Verification: unit parser, lint/typecheck/build dan browser.
- [ ] Seed harga demo tanpa menimpa existing lalu commit increment.
  - Acceptance: hanya SKU demo yang dikenal, relation valid, repeat idempotent.
  - Verification: seeder tests termasuk production refusal/preservation.

## Increment 3: QA Dan Delivery

- [ ] PHP suite, lint/typecheck/build, route/migration dan MySQL disposable.
  - Acceptance: gate lulus, current reads dan no partial save terverifikasi.
  - Verification: automated tests/QA script, command dicatat.
- [ ] DevTools atau fallback browser desktop/tablet.
  - Acceptance: harga tersimpan/reload, invalid/missing/readonly, framing,
    console bersih, format rupiah; mobile tidak diuji.
  - Verification: report/screenshot lokal ignored.
- [ ] Terapkan additive migration/demo harga lokal, isi checklist, commit/push.
  - Acceptance: akun/grant/stok/ledger existing tetap, tidak reset database.
  - Verification: fingerprint, git diff/status dan remote HEAD.
