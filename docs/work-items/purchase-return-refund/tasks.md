# Tasks: Penerimaan Uang Retur Supplier

## Sebelum Mulai

- [x] AGENTS, README, ARCHITECTURE, FOLDER-STRUCTURE dan module terkait dibaca.
- [x] Scope pembelian lunas, penerimaan penuh sekali dan exact6 disetujui.
- [x] Target Returns/PurchaseOrders/Payments diumumkan dan preseden ditinjau.
- [x] File user staged, SampleUI, LSP dan Old-docs di luar scope.
- [x] Rencana increment/acceptance/QA dicatat sebelum coding.

## Increment 1: Baseline

- [x] Finalisasi ADR-018, kontrak dan rencana.
  - Acceptance: owner, source paid, presisi, lock, replay, nol dan non-scope jelas.
  - Verification: review source/dokumen, link relatif dan git diff --check.

## Increment 2: Nilai Dan Source

- [x] Domain alokasi dan basis paid authoritative exact6.
  - Acceptance: snapshot source, partial round-down/full residual, nol, kuantitas
    dan nilai kumulatif/overflow dijaga; tidak memakai float/HPP/harga terkini.
  - Verification: unit/feature red-green, source/ledger fingerprint.

## Increment 3: Finance

- [x] Ledger receipt supplier, proof/contract/DTOs dan writer dengan consumer nyata.
  - Acceptance: total/split exact6, source paid, metode aktif compatible, replay,
    audit wajib dan batas paid; sumber lama tidak berubah presisi.
  - Verification: feature writer, migration up/down disposable, rollback audit.

## Increment 4: Settlement

- [x] Quote dan settlement Returns atomic berversi/fingerprint.
  - Acceptance: hanya posted/paid, hak per pembelian terkunci, stale/replay,
    permission terpisah; nol tanpa ledger/metode dan posted tetap immutable.
  - Verification: HTTP/auth403/422, native lock/concurrency, audit veto lintas module.

## Increment 5: UI Dan Histori

- [x] Dialog konfirmasi/split, result readonly, recovery dan Finance history.
  - Acceptance: format Indonesia exact6 trimmed, quote server, Sonner, shortcut,
    viewer, loading/error/nol/retry, beda sumber/arah/nominal histori jelas.
  - Verification: typecheck/lint/build, DevTools MCP desktop1440/tablet1024/768.

## Increment 6: QA Dan Aktivasi

- [x] Seeder demo aman atau alasan skip, native/full gate dan activation disposable.
  - Acceptance: source/Stock immutable, data/grant lama identik; schema/catalog
    idempotent tanpa posting/seed otomatis pada lokal.
  - Verification: test/Pint/native/browser/activation dua kali, route/status.
- [ ] Aktivasi lokal, checklist/docs, commit dan delivery.
  - Acceptance: local/remote SHA sama dan ahead/behind0/0; file user utuh.
  - Verification: diff/link/scope, commit/push/ls-remote.

## Sesudah Coding

- [x] Acceptance dan bukti setiap increment dicatat sesuai realita.
- [x] Tidak ada mutation source/Stock/FIFO atau file user.
- [x] Risiko/gap yang tidak ditutup dicatat dan docs module/API selaras.

## Bukti Increment 2

Unit/feature terfokus: 39 test/320 assertion PASS. Domain diuji red-green untuk
residual/nol/overflow. Source proof Finance memverifikasi jumlah baris ledger,
pointer/nomor/waktu/snapshot pembayaran PurchaseOrders, redaction dan master
nonaktif tanpa mengubah sumber. Pint lulus. Belum ada schema atau uang diterima.

## Bukti Increment 3

Finance/Payments dan Purchasing/Returns: 92 test/883 assertion PASS. Writer
memerlukan transaction caller, proof paid cocok, split exact6, batas paid,
canonical replay dan audit atomic. Guard histori master mencakup receipt baru
(test merah sebelum guard ditambahkan). Migration up lewat RefreshDatabase dan
down populated ditolak. Pint PASS. Database lokal belum diaktivasi.

## Bukti Increment 4

40 test/347 assertion Returns dan writer PASS: HTTP403/419/422, quote stale setelah
settlement lain, retry canonical, audit veto dua module, total paid cap dan stok/
source immutable. Alokasi nol tidak membuat uang; retur terakhir mendapat residual
0,000003 secara exact6. Projection settlement dibaca bulk tanpa N+1. Native
MySQL/concurrency dijalankan pada gate Increment 6, belum diklaim lulus.

## Bukti Increment 5

93 focused test/903 assertion, typecheck, ESLint seluruh resources/js dan build
2745 module PASS. Full suite 761 test/6016 assertion PASS. Chrome DevTools MCP
existing/stdio isolated PASS desktop1440/tablet1024/768, Light/Dark expanded/
collapsed, input Indonesia exact6 (selisih mikro disable), konfirmasi shortcut,
hasil/viewer readonly, nol tanpa ledger, history masuk/detail, palette, console
dan network bersih. Delapan screenshot beserta report ignored tersimpan pada
storage/framework/testing/purchasing-returns-browser-4639c6552d2150b6; screenshot
tablet dan dark desktop juga ditinjau visual. Fixture memastikan satu receipt,
dua settlement/audit termasuk nol, serta source/Stock tetap identik.

## Bukti Increment 6

- `php scripts/qa/purchase-return-refund-mysql.php`: PASS migration/status,
  empty down/up, empat worker LOCK WAIT, race quote scope, dua replay, snapshot
  MySQL JSON, nol/residual, rollback audit, FK/down guard dan source/Stock
  immutable. Nominal 900000000090001234560000,123456 tersimpan exact6 melalui
  kuantitas/harga beli yang valid; batas input harga beli tidak dilonggarkan.
- `php scripts/qa/activate-purchase-return-refund-mysql.php`: PASS sebelum schema,
  dua aktivasi, readiness false/true, lima tabel baru kosong, catalog unik,
  grant/source identik dan rerun setelah settlement. Database/worker dibersihkan.
- `php scripts/qa/activate-purchase-return-refund-local.php`: PASS dua kali
  setelah perbaikan cache, plus rerun setelah format script. Migration batch25
  Ran, permission refund tepat satu; receipt/settlement lokal tetap kosong.
  Tidak grant/reset/demo/posting/penerimaan uang pada database toko.
- Percobaan QA awal menemukan cache permission terbawa antar koneksi; QA
  parent/child kini memakai array. Aktivasi lokal pertama menyelesaikan DDL tetapi
  catalog ditahan duplicate permission dari cache stale; transaksi catalog
  rollback. Cache di-refresh sebelum seeder, rerun dan fingerprint lulus. Tidak
  down/drop atau reset data. SQL error diagnostics hanya state/code/frame,
  bukan query/payload sensitif.
- Full suite 761 test/6016 assertion, focused93/903, Pint, ESLint resources/js,
  typecheck dan build PASS; route Returns11 dan Finance history5 sesuai.
- Tautan relatif dokumen87 dan git diff --check PASS. Generator tidak dijalankan
  karena module existing, bukan perubahan scaffold/generator.
- Demo settlement sengaja tidak ditambahkan: akan membuat uang tanpa aksi
  operator. Fixture CCTV relasional menjalankan purchase/paid/return hanya pada
  database disposable. Master demo CCTV/kelistrikan existing tetap digunakan.

## Risiko Dan Batas

Belum mendukung supplier credit, pembelian belum lunas, penerimaan parsial,
reversal atau saldo berjalan. Posted/source dan Stock/FIFO tidak dimutasi.
Gap label aksesibilitas topnav tablet existing tidak diperluas pada pekerjaan
ini; palette diuji melalui shortcut. Mobile di luar scope sesuai keputusan user.
Old-docs, theme, dependency, SampleUI dan empat file LSP staged tidak disentuh.
Push/verifikasi remote menunggu commit QA/dokumentasi ini.
