# Rencana Increment Fondasi Draft Retur Pembelian

## Scope Dan Dependency

Dokumentasi -> scaffold/schema/Domain -> source lookup + simpan HTTP -> lifecycle/
demo -> UI/browser -> native/aktivasi/delivery. Owner utama Purchasing/Returns,
source lookup milik PurchaseOrders; Stock/Finance hanya target fingerprint.
Jangan menganggap kemiripan retur penjualan membenarkan import privat atau
keputusan FIFO/uang yang belum disepakati.

## Increment

| No | Status | Nama | Perubahan | Acceptance | Verifikasi |
| --- | --- | --- | --- | --- | --- |
| 1 | Passed | Baseline | README/spec/plan/tasks module dan work item; module index/source roadmap | Scope, non-scope, dependency, presisi dan QA jelas tanpa runtime change | Review, 10 dokumen/22 link, whitespace PASS |
| 2 | Planned | Fondasi teknis | Dry-run/generator, discovery, schema draft dan quantity Domain | Route/database module canonical, exact6, batas schema, tanpa mutation ledger | Scaffold/unit, migrate up/down disposable |
| 3 | Planned | Alur simpan | Published source lookup/DTOs dengan query/action consumer, catalog permission, persistence dan HTTP create/update/read | Sumber received/snapshot safe, harga bukan input, create/update audited/versioned | Feature privacy/input/membership/atomic dan route |
| 4 | Planned | Lifecycle/demo | Soft delete/restore, stale/no-op/audit hardening dan demo module | Draft saja, tidak mereset user, fingerprint ledger tetap | Feature lifecycle/audit/seeder/native current-read bila perlu |
| 5 | Planned | UI | List/form/detail/lifecycle, source picker, nav/palette dan shortcut | Permission UX, loading/error/retry/readonly, format Indonesia tanpa klaim posted/refund | TS/lint/build, DevTools/Playwright |
| 6 | Planned | Penutupan | Native MySQL, regresi, aktivasi lokal aman, docs dan delivery | Semua acceptance verified, aktivasi2x tanpa grant/demo/mutation sumber, remote sama HEAD | Full suite/native/browser/status/Git |

## Rincian Checkpoint

Increment 2 mengonfirmasi schema/FK/JSON/decimal/rollback berdasarkan source,
baru membuat migration. Jangan menyalin nomor migration yang sudah dipakai.
Tulis test red quantity/base overflow/nonexact, lalu implementasi Domain murni.
Scaffold default tidak boleh menjadi endpoint publik tanpa authorization atau
memberi kesan CRUD selesai. Tidak membuat published port kosong.

Increment 3 membuat lookup/DTOs PurchaseOrders bersama consumer query/action
Returns. Projection hanya data posted yang dibutuhkan; own adapter membaca own
tables. Uji source draft/trashed/metadata receipt invalid dan nilai setelah
master rename/inactive. Pembayaran PO tidak boleh membuat draft berubah harga.
Tulis tests save terlebih dahulu, termasuk quantity/member/forgery/version dan
rollback audit. Query/HTTP berizin menjadi alur yang dapat diverifikasi.

Increment 4 melengkapi delete/restore dan batas no-op (JSON key order MySQL,
decimal panjang dan notes numeric-string "0" dibandingkan strict). Demo hanya
draft dari sumber existing; fixture disposable boleh menyiapkan PO+receipt.
Tidak menambahkan reservasi atau menghitung stok tersedia untuk menolak draft.

Increment 5 menguji abort quote/snapshot lama, loading/error500/retry,403/422,
double submit, stale version dan keyboard dari level dialog. Tidak memberi
success pengeluaran stok atau penerimaan kas. Tampilan desktop1440x1000,
tablet1024x768 dan768x1024, Light/Dark, sidebar expanded/collapsed.

Increment 6 menguji native decimal/FK/unique/row lock/stale/current read dan
audit rollback. Fingerprint meliputi PO/header/lines, receipt/lines/alokasi,
saldo/movement/layer/consumption, semua ledger Finance dan audit asal sesuai
scope (audit draft baru memang bertambah). Aktivasi lokal berbeda dari CRUD:
hanya migration/catalog, seluruh data/audit existing harus identik.

## QA Automated

- Generator pada Increment 2: php artisan module:make Purchasing Returns --dry-run,
  lalu --with-tests hanya setelah struktur/preview sesuai baseline.
- Backend rencana setelah file ada: php artisan test --compact
  tests/Feature/Modules/Purchasing/Returns dan tests/Unit/Modules/Purchasing/Returns.
- Regresi sumber: tests/Feature/Modules/Purchasing/PurchaseOrders dan test
  projection sumber yang dibuat; php artisan test --compact sebelum penutupan.
- Scoped vendor/bin/pint --test pada PHP yang berubah.
- UI: npx tsc --noEmit; npx eslint resources/js; npm run build. Jangan lint
  --fix global yang bisa menyentuh arsip/artefak user.
- Route/status: php artisan route:list --path=purchasing/returns dan
  php artisan migrate:status setelah route/schema ada.
- Native/browser/aktivasi: script spesifik belum ada. Buat saat ada consumer dan
  catat nama command, status dan artifact aktual, bukan mengklaim tersedia sekarang.
- DevTools MCP dahulu sesuai [QUALITY](../../../../../QUALITY.md), termasuk
  executable MCP existing isolated bila tool langsung tidak diekspos; fallback
  dengan alasan jika blocked. Tidak install dependency/profile user.
- DB QA disposable, nama/actual database diverifikasi sebelum migration/writer,
  credential random lewat stdin tanpa dicetak dan cleanup. Mobile tidak diuji.

## Risiko Dan Mitigasi

| Risiko | Mitigasi/owner |
| --- | --- |
| Jumlah diterima disamakan saldo sekarang | PurchaseOrders lookup mengikuti full posted quantity, bukan Stock availability. |
| Draft dianggap batas kumulatif/retur final | Returns menjelaskan bukan reservasi dan tidak punya route post/refund. |
| Harga6 pecahan dibulatkan ke2 ala sales | Snapshot cost string6, tidak menghitung supplier settlement. |
| Pembayaran PO mengubah version | Draft memakai version sendiri; lookup tidak mengekspor payment metadata. |
| Audit/no-op/restore merusak data | TDD, exact/strict JSON comparison dan native transaction tests. |
| Demo/aktivasi mem-posting sumber baru | Seeder draft skip bila prerequisite tidak ada; aktivasi hanya catalog/schema. |

## Batas Berhenti Dan Rollback

Tahap sekarang berhenti setelah Increment 1 dokumentasi dan commit save point.
Increment 2..6 menunggu arahan lanjut user. Scope posting/FIFO/uang supplier tidak
boleh diambil dari roadmap tanpa interview/approval terpisah.
Pemulihan dokumen melalui commit terfokus, bukan reset perubahan user. Setelah
ada data, rollback schema harus mengikuti guard/keputusan Increment 2; jangan
drop tabel populated atau menghapus draft user untuk memaksa migration ulang.
