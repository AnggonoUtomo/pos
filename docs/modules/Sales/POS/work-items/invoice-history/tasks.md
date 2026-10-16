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

- [ ] Full/focused QA, browser history dan regresi checkout.
  - Acceptance: tidak ada invoice/stock/payment/activity user dimutasi; DevTools
    BLOCKED jujur bila tool unavailable dan fallback PASS terpisah.
  - Verification: full tests/Pint/TS/lint/build, route/status, screenshots/report.
- [ ] Update module/API/MODULES/QUALITY dan checklist.
  - Acceptance: sesuai kemampuan aktual, risiko/non-scope tertulis.
  - Verification: review boundary/privacy, link, diff/staging/secret scope check.
- [ ] Commit/push dan cocokkan HEAD dengan GitHub.
  - Acceptance: Old-docs/SampleUI/LSP/env/artefak QA tidak ikut.
  - Verification: git push, ls-remote dan divergence0/0.

## Sesudah Coding

- [ ] Semua acceptance scope lulus dan status aktual.
- [ ] Full test/frontend gates dan browser desktop/tablet lulus.
- [ ] Schema tidak berubah, read/print tidak mengubah ledger/audit.
- [ ] Bukti/risiko/dokumen dan remote terverifikasi.

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
Regresi akhir/delivery menunggu patch dan pengulangan gate terkait.
