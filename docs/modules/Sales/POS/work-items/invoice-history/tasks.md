# Tasks: Riwayat Penjualan

## Sebelum Mulai

- [x] AGENTS, docs/README, ARCHITECTURE dan FOLDER-STRUCTURE dibaca.
- [x] Dokumen/source POS, receipt/permission dan pola UI existing dipelajari.
- [x] Scope readonly disetujui user; target Sales/POS diinformasikan.
- [x] Dampak route/permission/UI/database/seeder/audit/FIFO dipetakan di README.
- [x] QA backend/frontend/browser dan exclusion file user direncanakan.

## Increment 1: Scope

- [x] Tulis scope, dependency, urutan dan checklist sebelum coding.
  - Acceptance: list/count/options/detail ownership, tanggal WIB dan snapshot,
    non-scope explicit; tanpa ADR baru karena tidak mengganti keputusan mahal.
  - Verification: review, link relatif dan git diff --check.

## Increment 2: Query Dan HTTP

- [x] Tulis test RED untuk authorization/filter/privacy/read-only.
  - Acceptance: guest/outsider/create-only ditolak; post milik sendiri, view
    semua; actor tampering tidak memperluas scope dan opsi tidak bocor.
  - Verification: InvoiceHistoryTest sebelum endpoint tersedia.
- [x] Implementasikan query/adapter/filter dan endpoint Inertia/JSON.
  - Acceptance: pagination25/stable order, literal invoice search, rentang WIB,
    validasi invalid tanggal/ULID/page, historical choices, exact money;
    tidak membuka HPP/payload privat atau mutation.
  - Verification: focused/regresi POS, Pint, route:list dan diff review.
- [x] Update bukti/status dan commit setelah GREEN.

## Increment 3: UI

- [x] Buat halaman admin dengan komponen lokal history.
  - Acceptance: cari/filter/reset URL, pagination, loading/error/empty, tanggal
    WIB dan angka Indonesia; tidak ada edit/delete atau fake summary.
  - Verification: typecheck/ESLint/build, browser authenticated fixture.
- [x] Pasang receipt existing, navigasi dan shortcut.
  - Acceptance: detail GET authorization backend, request stale dibatalkan,
    error/reload/cetak dan dialog viewport aman; sidebar/palette berizin;
    Alt+F cari, Ctrl/Cmd+Enter terapkan, Esc tutup, Ctrl/Cmd+P pada receipt.
  - Verification: UI gates, browser desktop/tablet Light/Dark dan permission.
- [x] Update bukti/status dan commit setelah verifikasi.

## Increment 4: QA Dan Delivery

- [x] Full/focused QA, browser history dan regresi checkout.
  - Acceptance: tidak ada invoice/stock/payment/activity user dimutasi; DevTools
    BLOCKED jujur bila tool unavailable dan fallback PASS terpisah.
  - Verification: full tests/Pint/TS/lint/build, route/status, screenshots/report.
- [x] Update module/API/MODULES/QUALITY dan checklist.
  - Acceptance: sesuai kemampuan aktual, risiko/non-scope tertulis.
  - Verification: review boundary/privacy, link, diff/staging/secret scope check.
- [x] Commit/push dan cocokkan HEAD dengan GitHub.
  - Acceptance: Old-docs/SampleUI/LSP/env/artefak QA tidak ikut.
  - Verification: git push, ls-remote dan divergence0/0.

## Sesudah Coding

- [x] Semua acceptance implementasi/QA/delivery lulus.
- [x] Full test/frontend gates dan browser desktop/tablet lulus.
- [x] Schema tidak berubah, read/print tidak mengubah ledger/audit.
- [x] Bukti/risiko/dokumen dan remote terverifikasi.

## Hasil Verifikasi

Increment 1: review scope/source/permission, link relatif dan git diff --check
PASS. Tidak ada runtime/schema/UI/database berubah. Berikutnya test RED Increment 2.

Increment 2: InvoiceHistoryTest RED13 karena endpoint belum ada, lalu GREEN13.
Boundary/privacy/date WIB/pagination27 invoice/literal wildcard/invalid/tamper
teruji; Inertia memakai withoutVite mengikuti pola test Pages existing, bukan
menganggap build frontend sudah tersedia. Dua route auth/middleware OR view/post
terverifikasi; Pint scope PASS. Regresi checkout/read/lock dijalankan bersama.
UI/navigasi/build/browser belum tersedia sampai Increment 3. Tidak ada migration,
permission/grant, dependency atau database pengguna berubah.

Increment 3: TS/ESLint/build2715 PASS; full suite sebelum patch dependency
419 tests/3301 assertions PASS. Browser history PASS pada fixture28 invoice,
fingerprint invoice/Stock/Finance/activity identik sesudah seluruh read/print.
Scope own/view-all/options/count, pagination/reload, literal search, WIB,
historical master, empty/reset, shortcut/palette, receipt503/reload/stale abort
dan cetak-only receipt terverifikasi. Desktop1440x1000 dan tablet1024x768/
768x1024, Light/Dark serta sidebar collapsed. Screenshot desktop dan receipt
Dark portrait diperiksa visual. Console unexpected kosong,503 injeksi QA.
DevTools discovery BLOCKED karena tool tidak tersedia; fallback Playwright
existing dipakai tanpa dependency baru. Report ignored: storage/framework/
testing/parties-browser-9b4c2f3956657589/invoice-history-report.json.
Percobaan pertama gagal selector placeholder command palette pada script QA;
disesuaikan ke label existing, bukan mengubah aplikasi, kemudian PASS.

Audit npm production bersih; Composer menemukan advisory existing HTTP Foundation.
User menyetujui [patch terpisah](../../../../../work-items/http-foundation-security-patch/README.md).
Pada save point UI tersebut, regresi/delivery menunggu patch; hasil final di bawah.

## Penutupan QA Increment 4

| Command/Pemeriksaan | Hasil | Catatan |
| --- | --- | --- |
| php artisan test --compact sesudah patch | PASS | 423 tests/3316 assertions, termasuk13 test riwayat dan4 test HeaderUtils |
| Focused POS/history/read/lock sebelum patch | PASS | 33 tests/272 assertions;13 riwayat GREEN sesudah RED endpoint404 |
| Pint --test file PHP delivery | PASS | Module/feature/unit/QA sendiri; tidak memformat user |
| npx tsc --noEmit; npx eslint resources/js; npm run build | PASS | Build2715, source frontend tidak berubah saat patch Composer |
| php scripts/qa/pos-checkout-mysql.php | PASS | Native query history grouped options/scope/LIKE/WIB/decimal, serta current read/concurrency checkout/FK dan cleanup |
| node scripts/qa/invoice-history-browser.mjs sesudah patch | PASS |28 invoice, fingerprint ledger/audit identik; own/view-all/outsider/create-only, filters/invalid range, pagination/reload, receipt503/stale cancel/reprint, shortcuts/palette |
| node scripts/qa/pos-checkout-browser.mjs sesudah patch | PASS | Multi-payment/change, double submit, lost-response/reload/retry idempotent, quote422, permission/storage guard dan receipt print |
| php scripts/qa/apply-pos-checkout-local.php | PASS | Nothing to migrate; fingerprint bisnis/grant identik, tanpa invoice/grant/reset |
| route:list --path=sales/invoices -v; migrate:status path POS | PASS |2 GET auth/permission OR view/post; schema POS batch13 Ran, tidak membuat migration baru |
| composer validate --strict; composer audit --locked | PASS | Hanya http-foundation7.4.18 ke7.4.21; audit bersih |
| npm audit; npm audit --omit=dev | PASS |0 vulnerability; tidak mengubah dependency npm |
| DevTools discovery awal | BLOCKED | Tool belum tersedia; fallback Playwright existing PASS terpisah |
| Review/link/git diff --check | PASS | Readonly/ownership/snapshot/privacy, tanpa model privat lintas module, secret atau artefak user |

Artefak ignored sesudah patch: storage/framework/testing/
parties-browser-e4dc1c01995ed16c/invoice-history-report.json dan
parties-browser-b9674e4d2f6d1935/pos-checkout-report.json. Riwayat28 invoice,
fingerprint invoice/Stock/Finance/activity tidak berubah; checkout tepat2 invoice/
issue/receipt,3 payment records, stok kamera5 dari8. Console unexpected kosong;
503/ERR_FAILED/422 injeksi QA dicatat terpisah. Screenshot sebelum patch telah
diperiksa; frontend sama pada run akhir, screenshot akhir juga tersedia.

Tidak ada schema/permission/grant/data bisnis lokal/theme berubah. Regresi parser
dengan budget PCRE terbatas tidak lagi mereproduksi directive hilang; kontrol
header normal/quoted dan separator space/tab tetap lulus. Ini bukan load test
produksi. Risiko tersisa: index volume besar/choices1000, retention akun,
recovery lintas perangkat, printer fisik/receipt panjang belum scope.
Delivery selesai17 Oktober2026: git push origin main40f1c53 ke b6ebcd9;
git ls-remote mengonfirmasi b6ebcd9fd95ebdd2a9d64e693245f4f181931739 sama dengan
HEAD dan git rev-list --left-right --count HEAD...origin/main menghasilkan0/0.
Semua36 file delivery sesuai scope, tanpa Old-docs/SampleUI/LSP/env/artefak QA.
Commit penutup checklist dibuat setelah bukti ini lalu disinkronkan kembali.

Resume penutupan17 Oktober2026: DevTools MCP kembali tersedia. Smoke pada
isolated context ke http://pos.test/sales/invoices sempat500 karena MySQL lokal
menolak koneksi sebelum session middleware selesai. Tanpa perubahan service/
config agent, koneksi kembali tersedia; migrate:status POS Ran dan reload
redirect /login sesuai auth. Login200 dan21 asset build200, console error/warn
kosong. Emulate desktop1440x1000 serta tablet1024x768/768x1024 tanpa overflow.
Tab QA ditutup. Ini PASS smoke DevTools unauthenticated, bukan klaim seluruh
workflow riwayat diuji lewat DevTools; authenticated workflow tetap Playwright
fixture disposable di atas. Tidak menggunakan credential/session akun user.

Review independen patch HTTP Foundation selesai tanpa temuan konkret; fokus
trigger/control ulang PASS. Detail pada work item patch. Perapian dokumentasi
menghapus heading checklist ganda yang tertinggal, tanpa mengubah UI/runtime.
