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

- [ ] Posting membuat balance, movement, FIFO dan snapshot dalam satu transaksi.
  - Acceptance: posted immutable, duplicate/retry tidak menggandakan saldo.
  - Verification: posting, snapshot, pair uniqueness dan rollback tests.
- [ ] Guard master berhistori melalui published StockUsage.
  - Acceptance: konversi/removal/deactivate/delete tidak merusak histori.
  - Verification: regresi master termasuk bypass super-system.

## Increment 5: Query Dan UI

- [ ] Buat list stok awal, saldo dan kartu stok dengan filter/pagination.
  - Acceptance: server read memakai ledger dan snapshot, permission backend.
  - Verification: feature query/page tests.
- [ ] Buat UI, dialog, shortcut, Sonner dan navigasi.
  - Acceptance: state loading/empty/error dan view/manage/post terpisah.
  - Verification: lint/typecheck/build dan browser desktop/tablet.

## Increment 6: QA Dan Delivery

- [ ] Seeder demo relasional aman/idempotent melalui action.
  - Acceptance: posted tidak ditimpa; tidak membuat akun/auto-grant.
  - Verification: demo seeder test.
- [ ] QA full backend, schema/route dan browser terisolasi.
  - Acceptance: hasil actual PASS atau BLOCKED jujur dengan fallback.
  - Verification: full suite, Pint, frontend gates, route/status, report browser.
- [ ] Perbarui docs/API, MODULES, dependency master dan bukti verifikasi.
  - Acceptance: status sesuai implementasi, tidak mengklaim consumption FIFO.
  - Verification: review/link/diff, selective staging dan commit.
- [ ] Push setelah semua scope terverifikasi.
  - Acceptance: remote main sesuai HEAD; artefak user tetap lokal.
  - Verification: push, git status dan remote ref.

## Sesudah Coding

- [ ] Scope selesai dan focused/full test lulus.
- [ ] Frontend lint/typecheck/build dan QA desktop/tablet lulus.
- [ ] Route/schema/status, audit dan demo terverifikasi.
- [ ] Risiko/limit/gap serta dokumen aktual diperbarui.

## Hasil Verifikasi

12 Oktober 2026: dry-run generator, review scope/link relatif dan staged diff
check PASS untuk Increment 1; belum ada coding atau QA runtime Stock.

Increment 2: RED 12 test gagal karena valuasi/tabel belum ada; GREEN valuasi,
scaffold dan schema. Up/down diperiksa hanya SQLite disposable, bukan MySQL user.

Increment 3: RED 4 HTTP test 404, GREEN draft/lifecycle/permission/audit rollback.
Regresi presisi menemukan SQLite float; kolom decimal QA diganti string exact.
MySQL tetap DECIMAL. Bukan bukti pengujian locking MySQL.
