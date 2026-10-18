# Tasks: Fondasi Draft Retur Penjualan

## Sebelum Mulai

- [x] AGENTS.md, docs/README.md, ARCHITECTURE.md, FOLDER-STRUCTURE.md dibaca.
- [x] Scope user dan snapshot POS diperiksa; tidak ada implementasi retur lama.
- [x] Module target diumumkan; worktree user dipisahkan dari scope.
- [x] Tidak ada posting/refund/reservasi; harga asal bukan nominal refund.

## Increment 1: Baseline Dan Scaffold

- [x] Tambahkan spesifikasi, rencana, dan checklist sebelum coding.
  - Acceptance: dependency, permission, invariant dan batas ledger eksplisit.
  - Verification: review manual baseline dan git diff --check.
- [x] Dry-run dan generate Sales/Returns melalui generator project.
  - Acceptance: route/migration/seeder/provider/test berada di module canonical.
  - Verification: module:make --dry-run, --with-tests, scaffold test.

## Increment 2: Backend Draft

- [x] Publikasikan lookup snapshot invoice minimal dari POS.
  - Acceptance: tidak ada HPP/payment/private adapter lintas module.
  - Verification: focused test privacy dan snapshot setelah master berubah.
- [x] Tambahkan schema, validasi kuantitas, save/delete/restore dan audit atomik.
  - Acceptance: positif <= asal, exact base, duplikat/forgery/stale ditolak;
    invoice immutable, draft saja, no-op tidak menambah audit/version.
  - Verification: unit/feature test, audit rollback dan fingerprint ledger.
- [x] Tambahkan query, HTTP permission dan demo relasional.
  - Acceptance: bounded search/pagination; viewer tidak mutation; demo aman.
  - Verification: permission/validation/lifecycle/demo tests dan route:list.

## Increment 3: UI Dan Browser

- [x] Buat halaman, form, lifecycle, Sonner dan format Indonesia.
  - Acceptance: input qty memakai satuan asli, harga hanya snapshot; permission
    visibility benar, loading/error/empty tertangani, tidak ada sukses refund.
  - Verification: typecheck, ESLint, build dan browser CRUD.
- [x] Hubungkan sidebar/palette dan shortcut dropdown.
  - Acceptance: desktop/tablet Light/Dark, expanded/collapsed tanpa overflow.
  - Verification: DevTools atau BLOCKED + fallback Playwright dan screenshot.

## Increment 4: Penutupan

- [ ] Verifikasi native MySQL schema/precision/version/audit rollback.
  - Acceptance: database disposable dibersihkan; ledger asal tidak berubah.
  - Verification: script QA native dan focused/full regression.
- [ ] Aktifkan migration dan permission lokal secara aman dan idempotent.
  - Acceptance: tidak seed demo/grant/reset user; fingerprint existing sama.
  - Verification: activation dua kali dan migrate:status.
- [ ] Perbarui API/module index, checklist dan hasil aktual.
  - Acceptance: klaim sesuai command; batas posting/refund tetap eksplisit.
  - Verification: diff --check, scoped commit, push, divergence 0/0.

## Bukti Verifikasi

Increment 1: dry-run/generate PASS; ReturnsScaffoldTest PASS (1 test/1 assertion).
Baseline direview dan git diff --check PASS sebelum commit.

Increment 2: red awal 13 test (class/action belum ada), lalu focused
ReturnQuantityTest/ReturnDraftTest/ReturnsScaffoldTest PASS 21 test/111 assertion.
Pint scoped PASS; route:list --path=sales/returns PASS delapan route.
Migration dieksekusi pada SQLite disposable melalui RefreshDatabase; native
MySQL dan aktivasi lokal tetap menunggu increment 4. Tidak ada migration user
yang dijalankan pada increment ini.

Review lanjutan: test regresi RED membuktikan perbandingan long decimal dan
catatan numeric-string tidak boleh memakai loose equality PHP. Perbaikan
strict comparison dengan urutan key JSON dinormalisasi PASS; focused kini
24 test/124 assertion, termasuk multi-satuan BOX dan veto audit restore/model.

Increment 3: npx tsc --noEmit PASS; npx eslint resources/js PASS; npm run build
PASS (2735 module). Chrome DevTools MCP namespace tidak diekspos sesi; server
existing berhasil diakses via stdio, Chrome headless isolated dan MCP roots/list
dibatasi workspace POS. Tidak install package/mengubah konfigurasi user.
DevTools QA PASS login/source/create/detail, kuantitas 1,25, framing1440/1024/768,
console runtime/network internal, screenshot dan fingerprint ledger unchanged.
Artefak lokal: storage/framework/testing/sales-returns-browser-cb589704e672f02b/.

Playwright existing melengkapi QA: CRUD lifecycle, invalid qty > terjual,
format1,5, pencarian literal, retry snapshot500 terkontrol dan disable save,
viewer readonly/outsider403, shortcut/palette, Light/Dark expanded/collapsed
desktop1440x1000/tablet1024x768 dan768x1024. PASS, console unexpected kosong,
dua error disengaja terpisah; event created/updated/deleted/restored dan
fingerprint invoice/stock/FIFO/finance unchanged. Screenshot diperiksa visual.
Artefak lokal: storage/framework/testing/sales-returns-browser-c6581ddc16018648/.
QA mobile tidak dijalankan sesuai scope. Selector QA retry dan parsing page ID/
MCP roots diperbaiki setelah percobaan gagal; hasil akhir di atas sudah ulang PASS.

Schema memakai header snapshot JSON dan snapshot per baris JSON untuk data asal
immutable; qty/base qty tetap decimal20,6 MySQL, source invoice/line FK restrict,
unique baris per draft. Harga asal disimpan sebagai string decimal exact di JSON,
bukan hasil kalkulasi refund. Domain tidak mengimpor framework.
