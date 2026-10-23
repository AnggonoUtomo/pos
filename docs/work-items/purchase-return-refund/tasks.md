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

- [ ] Dialog konfirmasi/split, result readonly, recovery dan Finance history.
  - Acceptance: format Indonesia exact6 trimmed, quote server, Sonner, shortcut,
    viewer, loading/error/nol/retry, beda sumber/arah/nominal histori jelas.
  - Verification: typecheck/lint/build, DevTools MCP desktop1440/tablet1024/768.

## Increment 6: QA Dan Aktivasi

- [ ] Seeder demo aman atau alasan skip, native/full gate dan activation disposable.
  - Acceptance: source/Stock immutable, data/grant lama identik; schema/catalog
    idempotent tanpa posting/seed otomatis pada lokal.
  - Verification: test/Pint/native/browser/activation dua kali, route/status.
- [ ] Aktivasi lokal, checklist/docs, commit dan delivery.
  - Acceptance: local/remote SHA sama dan ahead/behind0/0; file user utuh.
  - Verification: diff/link/scope, commit/push/ls-remote.

## Sesudah Coding

- [ ] Acceptance dan bukti setiap increment dicatat sesuai realita.
- [ ] Tidak ada mutation source/Stock/FIFO atau file user.
- [ ] Risiko/gap yang tidak ditutup dicatat dan docs module/API selaras.

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
