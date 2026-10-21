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
| 2 | Passed | Fondasi teknis | Dry-run/generator, discovery, schema draft dan quantity Domain | Route/database module canonical, exact6, batas schema, tanpa mutation ledger | 78 focused tests/139 assertions, 712 full tests/5628 assertions, Pint, route/status |
| 3 | Passed | Alur simpan | Published source lookup/DTOs, permission catalog, query/route JSON, persistence, create/update | Sumber receipt cocok/snapshot safe, harga bukan input, create/update atomic/audited/versioned | 87 tests/218 assertions, Pint, route:list, diff check |
| 4 | Passed | Lifecycle/demo | Soft delete/restore, stale/no-op/audit hardening dan demo module | Draft saja, tidak mereset user, fingerprint ledger tetap | 28 focused tests/216 assertions, full suite 733/5800, Pint, route dan fingerprint |
| 5 | Passed | UI/browser | List/form/detail/lifecycle, source picker, nav/palette dan shortcut | Permission UX, loading/error/retry/readonly, format Indonesia tanpa klaim posted/refund | 734 tests/5824 assertions, TS/lint/build, DevTools desktop/tablet |
| 6 | Passed | Penutupan | Native MySQL, regresi, aktivasi lokal aman, docs dan delivery | Semua acceptance verified, aktivasi dua kali tanpa grant/demo/mutation sumber, remote sama HEAD | 734 tests/5824 assertions, native MySQL, aktivasi disposable/lokal, build/typecheck/lint/Pint, route/status/Git |

## Rincian Checkpoint

Increment 2 mengonfirmasi schema/FK/JSON/decimal/rollback berdasarkan source.
Migration memakai nomor yang tersedia. Test red quantity/base overflow/nonexact
lalu implementasi Domain murni; seluruhnya sudah lulus pada disposable.
Scaffold default tidak boleh menjadi endpoint publik tanpa authorization atau
memberi kesan CRUD selesai. Tidak membuat published port kosong.

Increment 3 membuat lookup/DTOs PurchaseOrders bersama consumer nyata di Returns.
Projection hanya data received posted yang dibutuhkan; adapter membaca snapshot
own tables dan memvalidasi receipt. Uji source draft/trashed/receipt invalid,
search bounded/literal, nilai setelah master rename, quantity/member/forgery,
version/no-op, permission terpisah dan rollback audit. JSON data/source/create/
update routes tersedia; Inertia index masuk Increment 5.

Increment 4 melengkapi delete/restore dengan version/state guard dan audit
atomic. Test no-op mengacak urutan key JSON snapshot, memakai decimal6 dan notes
"0"; no-op tidak mengubah revision/audit. Demo hanya membuat draft dari source
eligible existing melalui lookup published; skip tanpa source, dan marker
edited/trashed tidak ditimpa. Fixture disposable menyiapkan PO+receipt sebelum
fingerprint; seeder Returns tidak membuat atau memodifikasi ledger. Tidak ada
reservasi atau pemeriksaan saldo stok untuk menolak draft.

Increment 5 menguji request race source, loading/offline/HTTP500/retry, validasi
kuantitas, double submit dan keyboard dari level dialog. HTTP403/422/stale diuji
di feature test. Tidak memberi success pengeluaran stok atau penerimaan kas.
Tampilan desktop1440x1000, tablet1024x768 dan768x1024, Light/Dark, sidebar
expanded/collapsed.

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
- Native/aktivasi: `php scripts/qa/purchase-returns-mysql.php` memverifikasi
  schema, presisi, transaksi, stale, audit dan fingerprint pada MySQL disposable;
  `php scripts/qa/activate-purchase-returns-mysql.php` menguji aktivasi dua kali
  pada baseline terisi; `php scripts/qa/apply-purchase-returns-local.php`
  mengaktifkan migration/catalog lokal dua kali tanpa grant/demo/ledger.
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

Increment 1-6 telah dikerjakan dan diverifikasi sesuai checklist. Work item
selesai; migration Returns aktif di database lokal dan tabel draft tetap kosong.
Scope posting/FIFO/uang supplier tidak boleh diambil dari roadmap tanpa interview/
approval terpisah.
Pemulihan dokumen melalui commit terfokus, bukan reset perubahan user. Setelah
ada data, rollback schema harus mengikuti guard/keputusan Increment 2; jangan
drop tabel populated atau menghapus draft user untuk memaksa migration ulang.
