# Plan: Stok Awal Dan Fondasi FIFO

## Scope

Sesuai [specification.md](specification.md). Selesaikan dan commit tiap increment
terverifikasi; push hanya setelah keseluruhan scope selesai.

| No | Status | Isi | Acceptance | Verifikasi |
| --- | --- | --- | --- | --- |
| 1 | Passed | Scope dan checklist | Dokumen sesuai persetujuan | Review, link dan diff |
| 2 | Passed | Scaffold, domain valuasi dan schema | Decimal exact, ULID, migration module | RED/GREEN unit, scaffold/migration test |
| 3 | Passed | Lookup publik dan draft | Permission, aktif, audit, lifecycle | Feature HTTP dan rollback |
| 4 | Passed | Posting, snapshot, ledger dan guard master | Sekali per pasangan, idempotent, atomic | Posting/guard/regresi tests |
| 5 | Passed | Query dan UI | Stok awal/saldo/kartu, dialog dan permission | Page tests, lint/typecheck/build |
| 6 | Passed | QA dan delivery | Demo aman, browser, dokumentasi aktual | Full test, route/status, browser, review, commit/push |

## QA Automated

- php artisan test --filter=Stock; regresi Inventory/Identity terkait dan full suite.
- php vendor/bin/pint --dirty --test; php artisan route:list --path=inventory.
- Migration up/down SQLite disposable; migrate:status lokal; demo idempotency.
- npx eslint resources/js; npx tsc --noEmit; npm run build.
- Chrome DevTools MCP dicoba melalui tool discovery; bila tidak tersedia,
  catat BLOCKED dan gunakan Playwright/Chromium existing, tanpa dependency baru.
- Desktop 1440x1000 dan tablet 1024x768/768x1024; screenshot/console/overflow.
- git diff --check, selective staging, review secret/layer/consumer dan link docs.

## Risiko Dan Rollback

MySQL race/lock bukan bukti dari SQLite. Nilai FIFO exact disimpan terpisah
dari biaya rata-rata representatif; consumption baru harus mempertahankan
residual. Draft lama dengan konversi berubah ditolak saat posting. Koreksi
posted ditunda, bukan diberi tombol hapus. Demo tidak otomatis dijalankan pada
database user. Migration down hanya untuk database disposable; rollback
produksi yang berisi posting memerlukan rencana preservasi data.

## Batas Berhenti

Tidak membuat sales/purchasing/transfer/adjustment/reversal/payment/pricing.
Tidak mengubah theme, dependency, credential, assignment role atau Old-docs.
