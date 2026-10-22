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

- [ ] Domain alokasi dan basis paid authoritative exact6.
  - Acceptance: snapshot source, partial round-down/full residual, nol, kuantitas
    dan nilai kumulatif/overflow dijaga; tidak memakai float/HPP/harga terkini.
  - Verification: unit/feature red-green, source/ledger fingerprint.

## Increment 3: Finance

- [ ] Ledger receipt supplier, proof/contract/DTOs dan writer dengan consumer nyata.
  - Acceptance: total/split exact6, source paid, metode aktif compatible, replay,
    audit wajib dan batas paid; sumber lama tidak berubah presisi.
  - Verification: feature writer, migration up/down disposable, rollback audit.

## Increment 4: Settlement

- [ ] Quote dan settlement Returns atomic berversi/fingerprint.
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
