# Tasks: Stok Awal Dan Fondasi FIFO

## Sebelum Mulai

- [x] AGENTS.md, docs/README.md dan workflow dibaca.
- [x] Arsitektur, folder, ADR-003/011 dan module master terkait dibaca.
- [x] Scope/non-scope serta harga pokok positif dan sekali per pasangan disetujui.
- [x] Target Inventory/Stock diinformasikan; artefak user dipertahankan.
- [x] Dampak schema, contract, permission, audit, UI, soft delete/FIFO dipetakan.
- [x] Dry-run generator sesuai struktur canonical.
- [x] QA backend/browser desktop/tablet direncanakan; mobile ditunda.

## Increment 1: Dokumentasi

- [x] Tetapkan specification, plan dan tasks sebelum coding.
  - Acceptance: scope disetujui dan seluruh increment punya gate/checklist.
  - Verification: review link relatif dan git diff --check.

## Increment 2: Domain Dan Persistence

- [x] Generate module dan test scaffold menggunakan generator existing.
  - Acceptance: route/database/provider di module, tanpa folder paralel.
  - Verification: dry-run dan scaffold test.
- [x] Buat valuasi decimal exact serta schema ULID.
  - Acceptance: konversi dan nilai benar; nol/negatif/overflow/rounding ditolak.
  - Verification: unit RED/GREEN, migration up/down disposable.

## Increment 3: Draft

- [x] Tambah lookup publik Items/Warehouses dengan consumer Stock nyata.
  - Acceptance: tidak menggunakan adapter/model privat lintas module.
  - Verification: lookup/transaction tests dan review import.
- [x] Buat create/edit/delete/restore draft serta permission-only seeder.
  - Acceptance: audit atomic; tanpa stok; input allowlist, master aktif, max 100.
  - Verification: HTTP/permission/validation/audit rollback tests.

## Increment 4: Posting Dan Guard

- [x] Posting membuat balance, movement, FIFO dan snapshot dalam satu transaksi.
  - Acceptance: posted immutable, duplicate/retry tidak menggandakan saldo.
  - Verification: posting, snapshot, pair uniqueness dan rollback tests.
- [x] Guard master berhistori melalui published StockUsage.
  - Acceptance: konversi/removal/deactivate/delete tidak merusak histori.
  - Verification: regresi master termasuk bypass super-system.

## Increment 5: Query Dan UI

- [x] Buat list stok awal, saldo dan kartu stok dengan filter/pagination.
  - Acceptance: server read memakai ledger dan snapshot, permission backend.
  - Verification: feature query/page tests.
- [x] Buat UI, dialog, shortcut, Sonner dan navigasi.
  - Acceptance: state loading/empty/error dan view/manage/post terpisah.
  - Verification: lint/typecheck/build dan browser desktop/tablet.

## Increment 6: QA Dan Delivery

- [x] Seeder demo relasional aman/idempotent melalui action.
  - Acceptance: posted tidak ditimpa; tidak membuat akun/auto-grant.
  - Verification: demo seeder test.
- [x] QA full backend, schema/route dan browser terisolasi.
  - Acceptance: hasil actual PASS atau BLOCKED jujur dengan fallback.
  - Verification: full suite, Pint, frontend gates, route/status, report browser.
- [x] Perbarui docs/API, MODULES, dependency master dan bukti verifikasi.
  - Acceptance: status sesuai implementasi, tidak mengklaim consumption FIFO.
  - Verification: review/link/diff, selective staging dan commit.
- [ ] Push setelah semua scope terverifikasi.
  - Acceptance: remote main sesuai HEAD; artefak user tetap lokal.
  - Verification: push, git status dan remote ref.

## Sesudah Coding

- [x] Scope selesai dan focused/full test lulus.
- [x] Frontend lint/typecheck/build dan QA desktop/tablet lulus.
- [x] Route/schema/status, audit dan demo terverifikasi.
- [x] Risiko/limit/gap serta dokumen aktual diperbarui.

## Hasil Verifikasi

12 Oktober 2026: dry-run generator, review scope/link relatif dan staged diff
check PASS untuk Increment 1; belum ada coding atau QA runtime Stock.

Increment 2: RED 12 test gagal karena valuasi/tabel belum ada; GREEN valuasi,
scaffold dan schema. Up/down diperiksa hanya SQLite disposable, bukan MySQL user.

Increment 3: RED 4 HTTP test 404, GREEN draft/lifecycle/permission/audit rollback.
Regresi presisi menemukan SQLite float; kolom decimal QA diganti string exact.
MySQL tetap DECIMAL. Bukan bukti pengujian locking MySQL.

Increment 4: RED 6 posting/guard test 404; GREEN posting/layer/snapshot, retry
idempotent, pemisahan gudang, stale ratio, permission, audit rollback dua baris,
master guard dan rename historis. Read invariant memakai locking read setelah
lock master agar tidak menggunakan snapshot MySQL lama; verifikasi MySQL nyata
dilanjutkan pada Increment 6.

Increment 5: RED 2 read/page test; GREEN 2 test/46 assertion. UI menggunakan
segmented Button existing, bukan memasang package tab baru. Typecheck, ESLint
dan build 2688 module PASS setelah import tab yang belum tersedia diganti.
Browser workflow dijalankan pada Increment 6; tidak diklaim PASS di tahap ini.

Increment 6 (berjalan): demo idempotent dan focused Stock 29 test/172 assertion
PASS. Review menemukan stale eager relation MySQL, urutan lock base/selected
unit dan variasi case ULID; diperbaiki dengan current read, canonical lock,
retry transaction 3 kali dan normalisasi sebelum validasi duplicate. Reproduksi
dua koneksi MySQL REPEATABLE READ pada scripts/qa/stock-mysql.php PASS: stale
ratio ditolak, retry idempotent, sekali per pasangan, urutan lock dan decimal
besar exact. Ini bukan load test semua race paralel.

scripts/qa/apply-stock-local.php menerapkan migration additive dan permission
seeder saja. Fingerprint data akun/role/pivot/master sebelum/sesudah identik;
demo Stock tidak dijalankan pada database user. Migration/status lokal PASS.

Percobaan awal Chrome DevTools MCP BLOCKED karena tool tidak diekspos. Fallback Playwright
existing PASS pada storage/framework/testing/stock-browser-fb38785affa56f5d/report.json:
13 screenshot, desktop 1440x1000, tablet 1024x768/768x1024, console kosong,
CRUD/restore, invalid cost, posted immutable, balance/card, duplicate guard,
viewer/editor, sidebar collapse, shortcut/palette dan dark. QA juga menemukan
stale form state saat memilih item/unit; functional setData memperbaikinya.
Waktu posting dinormalisasi ISO UTC dan browser QA memakai Asia/Jakarta.

Audit awal dependency menemukan advisory existing; user menyetujui work item
terpisah docs/work-items/dependency-security-patch, termasuk tambahan dev tooling
yang ditemukan audit penuh. Hasil penutupan dependency dicatat di work item itu.

QA final Stock setelah update: full suite 237 test/1409 assertion PASS, focused
Stock 29/172 PASS, Pint/typecheck/ESLint/build PASS. Build 2688 module. Route
Stock 7 dan migrate:status 11 migration Ran. MySQL disposable dua koneksi PASS
diulang setelah update Composer. Tidak memutasi database user atau seed demo lokal.

Workflow browser final sesudah clean install PASS:
storage/framework/testing/stock-browser-7b763cd3bd928124/report.json,
13 screenshot dan console kosong, timezone Asia/Jakarta. Screenshot kartu stok
tablet portrait dan dark desktop diinspeksi; tidak ada page overflow/dialog keluar
viewport. Tabel lebar memakai scroll internal. Mobile tidak termasuk QA.

Saat resume, DevTools MCP tersedia: smoke http://pos.test/inventory/stock
redirect login tanpa sesi sesuai auth, asset build 200 dan console tanpa warning/error.
Emulate viewport desktop 1440x1000 serta tablet 1024x768/768x1024 menunjukkan
form login tanpa overflow. Resize window tidak mengubah effective viewport;
emulate digunakan dan innerWidth/innerHeight dikonfirmasi. Workflow authenticated
tetap memakai Playwright fixture disposable; tidak mengakses credential akun user.
Smoke ini bukan klaim seluruh transaksi Stock diuji melalui DevTools.

Clean dependency install/gate terakhir PASS; penutupan menunggu push. Tidak ada
pengeluaran/consumption FIFO, no-negative outflow, POS/purchase/transfer/reversal
atau perubahan theme/Old-docs yang ikut scope ini.
