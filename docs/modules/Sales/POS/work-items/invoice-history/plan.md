# Rencana Riwayat Penjualan

| No | Status | Hasil | Acceptance | Verifikasi |
| --- | --- | --- | --- | --- |
| 1 | Passed | Scope dan checklist | Ownership, HTTP, izin, tanggal/snapshot dan non-scope jelas sebelum coding | Review/link/diff |
| 2 | Passed | Query dan HTTP | List/count/options ter-scope, filter WIB/literal/ULID valid, privacy/read-only dan receipt kompatibel | RED/GREEN feature, regresi POS, Pint/route |
| 3 | Planned | UI dan navigasi | Halaman admin modular, filter URL, detail/cetak ulang, shortcuts/palette dan loading/error/empty | Typecheck/ESLint/build, browser fixture |
| 4 | Planned | QA dan delivery | Full gates, browser desktop/tablet Light/Dark, database pengguna tidak berubah, docs dan remote cocok | Full tests/Pint, browser/route/status, review/commit/push |

Commit otomatis sesudah task/increment terverifikasi; push saat seluruh work
item selesai. Tidak memasang dependency/membuat branch atau menyentuh Old-docs.
DevTools discovery dahulu; bila tool unavailable catat BLOCKED dan pakai
Playwright existing pada fixture loopback/SQLite disposable. Test SQLite bukan
bukti row lock MySQL; pekerjaan ini readonly, tidak mengubah transaksi/locking.
Rollback kode melalui commit terpisah sesuai instruksi user; tidak down ledger.
