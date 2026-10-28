# Tasks: Keranjang Tertahan

## Sebelum Mulai

- [x] Baca aturan, arsitektur/struktur, POS, roadmap dan recovery existing.
- [x] User mengesahkan own-only tanpa pengalihan/reservasi dan no double-pay.
- [x] Informasikan module target, siapkan plan/checklist dan pertahankan scope.

## Increment 1: Backend

- [x] RED test own/permission/validation/no ledger dan retry/version/cancel.
  - Acceptance: hanya actor sendiri; tahan tidak mengubah uang/stok.
  - Verification: HeldCartTest SQLite disposable.
- [x] Schema ULID, repository port, save/list/resume/cancel dan audit.
  - Acceptance: reprice current, invalid lines dipertahankan; konflik tidak
    menimpa perubahan; cancel soft delete, completed bukan mutable draft.
  - Verification: focused test dan migration disposable.
- [x] Integrasi source checkout dan replay/rollback.
  - Acceptance: hanya satu invoice per sumber, pembayaran gagal tidak menutup
    draft, replay key sama tetap snapshot lama.
  - Verification: focused checkout serta MySQL row-lock dua worker.
- [x] Commit backend sesudah focused/Pint lulus.

## Increment 2: UI

- [x] Tahan/daftar/panggil/batal dengan dialog, loading/error/empty dan Sonner.
  - Acceptance: cart kosong hanya setelah save sukses; replace perlu konfirmasi;
    field diskon/pajak/satuan/pelanggan/gudang tidak hilang.
  - Verification: browser desktop/tablet, reload, reprice dan cancel.
- [x] F6/F7, active source dan pending recovery.
  - Acceptance: operasi held terkunci saat pending payment; ID/version dipulihkan,
    konflik dua tab tidak membuat invoice ganda.
  - Verification: recovery parser dan browser alur terkait.
- [x] Commit UI sesudah gate yang relevan lulus.

## Increment 3: Penutupan

- [x] Satu regresi backend, lint/typecheck/build, Pint/diff check.
- [x] DevTools discovery atau BLOCKED+fallback; screenshot diperiksa.
- [x] Migration MySQL disposable, concurrency dan aktivasi additive lokal.
- [x] Panduan/API, checklist dan status roadmap berdasarkan hasil nyata.
- [x] Commit/push scope tanpa LSP; local/remote diverifikasi.

## Bukti

- RED: endpoint belum ada, 404 sesuai harapan.
- HeldCartTest: PASS 8 tests / 102 assertions sebelum interupsi; source tetap.
- Focused HeldCartTest + PosCheckoutTest setelah resume: PASS 25 tests /
  261 assertions. Ownership super-system, no ledger, optimistic version,
  quote current, soft cancel, replay dan rollback audit/payment tercakup.
- Pint PHP scope berubah: PASS. Backend disimpan pada commit `1d41eab`.
- `php scripts/qa/held-carts-mysql.php`: PASS; dua worker warehouse/key
  berbeda terbukti menunggu held row lock, satu checkout berhasil dan satu
  ditolak; tepat satu invoice/stock issue/payment receipt; replay aman.
- Satu gate `php artisan test --compact`: 850 PASS / 3 FAIL, 6951 assertions.
  Ketiganya test lifecycle existing karena tabel operasional baru belum masuk
  BUSINESS_TABLES. Daftar disesuaikan tanpa menjalankan reset pada DB toko;
  rerun hanya `OperationalLifecycleTest`: PASS 9 tests / 87 assertions.
  Tidak ada kegagalan tersisa; full suite tidak diulang pada source tak berubah.
- `npx tsc --noEmit`, ESLint scope POS, Prettier scope dan build: PASS.
- `node scripts/qa/pos-payment-ui.mjs`: PASS, termasuk parser recovery sumber
  held valid dan penolakan version null/nol/pecahan/overflow.
- Chrome DevTools MCP: BLOCKED, tool tidak tersedia pada discovery sesi ini.
  Fallback Playwright existing + installed Chrome, tanpa dependency baru.
- Browser menemukan TooltipProvider belum ada di POS fullscreen saat daftar
  berisi draft. Provider lokal ditambahkan dan build diulang pada source final.
- Chrome core: PASS tahan tanpa ledger, persistence/reload, pemanggilan harga
  terbaru, F6/F7, dua tab satu invoice/stock issue/receipt. Screenshot1440/1024
  diperiksa; tidak blank atau overflow. Artifact ignored:
  `storage/framework/testing/held-carts-browser-1cfa242a9f9a27f7/`.
  Script berhenti pada assertion tombol belakang modal (selector QA, console
  aplikasi bersih). Selector dikoreksi; `QA_HELD_FOCUS=recovery` melanjutkan
  hanya recovery/update/cancel, tanpa mengulang skenario core yang sudah lulus.
- Chrome recovery/update/cancel: PASS pada source runtime yang sama, console
  bersih; respons pembayaran hilang sesudah commit -> reload/replay payload
  identik dengan held ID/version, tepat satu invoice baru. Respons save held
  hilang -> retry URL/body sama tanpa draft ganda. Update versi draft, konfirmasi
  replace, cancel sumber aktif dan reload tanpa ledger tambahan lulus.
  Artifact: `storage/framework/testing/held-carts-browser-2354bc28cb0bd8f1/`.
- Review scope correctness/ownership, boundary module, row lock/idempotency,
  rollback, pagination serta recovery: tidak ada blocker tersisa. Tidak ada
  package baru; format/theme existing dipertahankan.
- Aktivasi 28 Oktober 2026: migration additive held carts Ran batch30;
  route:cache dan route:list PASS (empat endpoint). Tidak ada seeder/reset/
  grant/account change. Sebelum/sesudah: items6, invoices3, users2, roles8,
  payment_receipts3; tabel held baru kosong0. public/hot tidak ada.
  HTTPS login200 tanpa bypass TLS.
- .env.testing sementara dihapus sesudah QA. Server browser disposable berhenti;
  file LSP user dan Old-docs tidak diubah/disertakan.
- Commit save point: backend `1d41eab`, kompatibilitas daftar data operasional
  `48f25aa`, UI/recovery + QA harness `4b06e91`. Scope commit diperiksa dan
  file LSP staged milik user tetap di luar commit.
- Delivery: dokumentasi `0a4fae0` dipush ke origin/main; ls-remote cocok dan
  divergence HEAD...origin/main `0 0`. Worktree tersisa hanya file LSP existing
  staged/untracked. Commit checklist penutupan menyusul tanpa perubahan runtime.
