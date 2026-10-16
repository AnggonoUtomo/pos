# Rencana Increment Checkout POS Lunas

## Scope

Sesuai README/PRD. Kerjakan risk-first, commit setiap increment yang benar-benar
lulus. Increment 1..5 selesai; berikutnya Increment 6 penutupan/delivery.
UI posting sudah aktif setelah quote valid dan permission terpenuhi.

| No | Status | Nama | Perubahan Dan Acceptance | Verifikasi |
| --- | --- | --- | --- | --- |
| 1 | Passed | Aturan dan presisi | Dokumen/ADR, Domain settlement dan alokasi FIFO murni, no I/O | RED/GREEN unit, Pint, regresi/full suite, diff |
| 2 | Passed | Pengeluaran Stock | Schema additive movement/alokasi, mutation no-negative current lock, query kartu/saldo keluar tanpa join privat POS | SQLite up/down, action/query/audit rollback, MySQL FK/current read |
| 3 | Passed | Payment record | Schema dan action Finance, snapshot, cash bersih dan guard histori metode/akun; belum checkout UI | Action/precision/rollback/guard tests, migration dan MySQL |
| 4 | Passed | Posting POS atomic | Invoice/line/idempotency, consumer nyata contract Stock/Finance dan locking metadata/pricing, HTTP permission/read detail | HTTP/tamper/retry/race/rollback, route/status dan MySQL dua koneksi |
| 5 | Passed | Checkout UI | Dialog multi-bayar, quote stale/retry/recovery, success/bukti snapshot/cetak, shortcut dan permission | TS/lint/build, fallback browser desktop/tablet Light/Dark |
| 6 | In Progress | Penutupan | Regresi penuh, demo disposable CCTV/kelistrikan, aktivasi lokal additive dan docs aktual | Full gates, MySQL/browser, fingerprint lokal, review/commit/push/remote |

## Dependency Dan Implementasi

Domain Finance hanya memakai enum miliknya; Domain Stock tidak mengimpor POS.
POS memanggil published application boundary, bukan model/repository privat.
Belum membuat port/event placeholder pada Increment 1. Lock order final wajib
dicocokkan dengan seluruh mutation master existing sebelum Increment 4.

Invoice/payment/movement/consumption posted tidak memakai soft delete.
Master mutable existing tetap menggunakan lifecycle existing dengan guard
referensi baru bila ada payment record. Activity log atomic, tanpa credential,
kontak/rekening sensitif atau seluruh request mentah.

## QA Automated

- Unit: PaymentSettlementTest dan FifoAllocationTest memakai PHPUnit murni.
- Integrasi: rollback pada titik persist/audit gagal, snapshot master renamed,
  stock multigudang/multi-satuan, pembayaran exact, retry dan permission.
- MySQL disposable dua koneksi: last-stock concurrent checkout, status/price/
  metode berubah, idempotency race dan FIFO residual. Jangan klaim SQLite
  membuktikan row lock atau test serial membuktikan seluruh concurrency.
- UI: Chrome DevTools discovery dahulu; BLOCKED harus punya alasan dan fallback
  Playwright existing. Desktop1440x1000, tablet1024x768/768x1024, Light/Dark,
  expanded/collapsed bila shell admin tersentuh; bukan mobile.
- QA/UI hanya fixture disposable. Demo transaksi tidak diposting otomatis di
  MySQL pengguna. Aktivasi schema/permission additive tanpa grant/reset, dengan
  fingerprint existing akun/role/master/harga/stok/audit/Finance.

## Batas Berhenti Dan Rollback

Increment 2 berhenti pada action internal dan query Stock yang teruji, termasuk
migration lokal additive tanpa pengeluaran pengguna. Belum ada port/route
pengeluaran publik. Increment 3 menambah penerimaan internal Finance dan guard
histori; migration lokal Ran tanpa payment pengguna. Increment 4 menambah backend
posting atomic dan receipt JSON; schema POS Ran batch13 tanpa invoice pengguna.
Increment 5 mengaktifkan UI multi-payment, recovery pending dan receipt/cetak
browser. Increment 6 tetap pending untuk penutupan keseluruhan dan delivery;
scope baru harus mendapat persetujuan user.

Sebelum ada schema/data baru, rollback kode melalui commit terpisah sesuai
instruksi user. Sesudah posting nyata, jangan down migration/soft delete ledger
sebagai rollback. Koreksi data memerlukan reversal audited yang belum scope.
Push hanya setelah keseluruhan work item selesai atau user meminta eksplisit.
