# Rencana Penerimaan Uang Retur Supplier

| Increment | Status | Hasil | Verifikasi |
| --- | --- | --- | --- |
| 1. Baseline | Passed | Scope, ADR, kontrak, checklist | Review source, link/diff |
| 2. Nilai Dan Source | Passed | Domain alokasi exact6, paid basis publik | 39 test/320 assertion, source integrity/redaction, Pint |
| 3. Finance | Planned | Proof pembayaran asal dan receipt supplier immutable exact6 | Feature atomic/audit/replay/metode/batas paid |
| 4. Settlement | Planned | Schema Returns, quote/fingerprint, aksi/route/permission | HTTP403/422, stale/replay/nol/concurrency/rollback |
| 5. UI Dan Histori | Planned | Dialog, readonly, viewer, Finance history | Typecheck/lint/build, DevTools MCP1440/1024/768 |
| 6. QA Dan Aktivasi | Planned | Native MySQL, seeder, activation, docs/delivery | Full test, migration/status, fingerprint, commit/push |

## Ownership Dan Lock

PurchaseOrders menerbitkan basis paid/snapshot melalui boundary publik existing
ReturnPurchaseLookup. Finance menerbitkan proof pembayaran pembelian authoritative
tanpa akun/bank privat; consumer PurchaseOrders memverifikasi metadata sendiri.
Returns memvalidasi kuantitas/snapshot dan menjaga scope/revision per pembelian.
Finance memiliki ledger kas masuk supplier exact6 yang terpisah dari kas masuk
penjualan exact2 dan refund keluar penjualan exact2.

Urutan settlement: scope pembelian -> retur -> proof/disbursement asal -> akun
terurut -> metode terurut -> receipt. Status settled dan audit Returns satu
transaction dengan receipt dan audit Finance. Quote tidak memesan hak; fingerprint
berubah setelah settlement lain. Existing settlement diperiksa sebelum validasi
master live supaya retry historis tetap aman.

## Operasi

Migration berada di module masing-masing, additive dan menolak down berhistori.
Aktivasi hanya schema/catalog setelah QA disposable, dua kali tanpa grant/demo.
Seeder demo dipertimbangkan eksplisit pada fixture/disposable; jangan membuat
pembayaran atau penerimaan supplier pada data user tanpa instruksi.
