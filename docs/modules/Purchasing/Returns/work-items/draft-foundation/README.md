# Work Item: Fondasi Draft Retur Pembelian

## Status

In Progress. Increment 1 dokumentasi dan Increment 2 scaffold/schema/Domain
Passed. Increment 3..6 belum dimulai. Migration lokal masih Pending dan belum
dijalankan.

## Owner Dan Lokasi

- Owner utama: Purchasing/Returns, app/Modules/Purchasing/Returns.
- PurchaseOrders: calon penyedia published source lookup, bukan akses repository
  atau model privat; kontrak dibuat bersama consumer nyata, belum sekarang.
- Frontend rencana: resources/js/pages/purchasing/returns dan components lokal.
- Dokumen: [specification](../../specification.md), [plan](plan.md), [tasks](tasks.md).

## Kondisi Awal Dan Bukti Source

PurchaseOrders memiliki draft, penerimaan penuh posted/receipt dan pembayaran
lunas terpisah. PostPurchaseOrder menjaga snapshot baris serta receipt immutable;
PayPurchaseOrder menaikkan version header tanpa mengganti nilai baris.
quantity/conversion_ratio/unit_cost/base_quantity memakai decimal20,6 dan
total_value decimal30,6. Belum ada public lookup untuk consumer retur pembelian.
Sales/Returns menjadi contoh pola draft/snapshot/audit, bukan dependency privat
atau aturan FIFO/uang yang boleh disalin tanpa keputusan.

Source yang ditinjau: migration, model, PostPurchaseOrder dan
EloquentPurchaseOrderRepository pada PurchaseOrders, serta ManageReturnDraft dan
lookup public POS untuk pola boundary. Tidak ditemukan konflik struktur pada
scope yang dibaca; ini bukan audit menyeluruh project.

## Scope Dan Tidak Dikerjakan

CRUD draft dari satu pembelian diterima, snapshot satuan/harga asal, quantity
positif tidak melebihi baris sumber, soft delete/restore/version, dedicated
permission/audit, query/UI dan demo relevan. Spec module memuat acceptance rinci.
Draft tidak mengubah PO/receipt/stok/FIFO/Finance, tidak mereservasi quantity,
tidak menerima uang supplier atau menghitung jumlah refund. Tidak ada posting,
reversal, retur bebas sumber, parsial/utang atau perubahan tema/dependency.

## Acceptance Criteria

- [ ] Source eligible safe, membership dan quantity/base exact terbukti.
- [ ] Simpan/lifecycle/version/no-op dan audit mandatory atomic terbukti.
- [ ] Semua operasi draft menjaga source/receipt/Stock/FIFO/Finance identik.
- [ ] Permission, UI, demo aman serta QA native/browser sesuai spec lulus.
- [ ] Aktivasi lokal hanya schema/catalog, idempotent dan tanpa grant/demo/reset.
- [ ] Dokumen aktual/checklist, scoped commit/push dan remote SHA verified.

## Dampak Yang Harus Dicek

| Area | Rencana dan batas |
| --- | --- |
| Database | Header/baris draft milik Returns; ULID/soft delete/version/snapshot. Migration hanya di module. |
| Boundary | Published read lookup/DTOs milik PurchaseOrders untuk consumer nyata Returns; tidak ekspor model/repository. |
| Route/permission | purchasing.returns.view/manage dan route draft/lookup; belum aktif, tanpa auto-grant. |
| UI | Workspace admin, shortcut dropdown, nav/palette existing, Sonner dan angka Indonesia; tidak mengubah theme. |
| Demo | Draft relasional CCTV/kelistrikan dari sumber existing; skip bila tidak tersedia, tanpa auto receipt/pay. |
| Audit | created/updated/deleted/restored mandatory atomic, metadata minimal tanpa notes/raw payload. |
| Ledger/pricing | Tidak menulis PO/receipt/Stock/FIFO/Finance/master harga; harga snapshot bukan uang kembali. |

## Dependency Dan Keputusan

User sudah mengonfirmasi scope dan non-scope. PRD tambahan belum diperlukan:
produk retur ada di baseline dan kebutuhan draft dijelaskan di specification.
Belum membuat ADR baru: mengikuti pola canonical; bila keputusan schema/boundary
sulit dibalik muncul pada Increment 2, minta keputusan dan ADR sebelum perubahan.
Desain posting FIFO dan refund supplier tetap terbuka di luar work item.

## Increment

| No | Status | Hasil | Verifikasi |
| --- | --- | --- | --- |
| 1 | Passed | Baseline module/work item dan source review | 10 dokumen/22 link lokal valid, whitespace dan scope review |
| 2 | Passed | Scaffold canonical, dua tabel draft, dan Domain quantity exact6 | 78 test/139 assertion lulus; Pint, route absence, migration Pending, diff check |
| 3 | Planned | Lookup sumber dan simpan draft melalui HTTP | Published boundary/privacy, feature create/update/query |
| 4 | Planned | Lifecycle/version/audit/demo | Feature stale/no-op/rollback/seeder dan fingerprint |
| 5 | Planned | UI/nav/shortcut/browser | TS/lint/build, DevTools dan desktop/tablet |
| 6 | Planned | Native QA, aktivasi dan delivery | MySQL, full regresi, fingerprint lokal dan Git remote |

## Handoff Saat Ini

- Perubahan: scaffold module melalui generator, provider discovery, migration
  header/baris draft, dan Domain ReturnQuantity; tidak ada route bisnis atau
  permission yang didaftarkan.
- Verifikasi: rincian test, Pint, generator dry-run, route:list, migration status,
  dan scope commit dicatat di tasks. Test schema berjalan di SQLite disposable.
- Browser: SKIPPED karena Increment 2 tidak mengubah UI atau route bisnis.
- Risiko: lookup/simpan draft, stale/no-op, audit rollback dan demo aman belum
  dibuat; semuanya tetap pada increment berikutnya. Migration native MySQL belum
  dijalankan.
- Old-docs, SampleUI dan artefak user tidak disentuh. Commit tiap increment
  setelah verified; push hanya work item selesai atau diminta eksplisit.
