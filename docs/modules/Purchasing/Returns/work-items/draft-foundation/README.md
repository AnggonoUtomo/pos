# Work Item: Fondasi Draft Retur Pembelian

## Status

In Progress. Increment 1 dokumentasi, Increment 2 scaffold/schema/Domain, dan
Increment 3 backend source lookup serta draft create/update Passed. Increment
4..6 belum dimulai. Migration lokal masih Pending dan belum dijalankan.

## Owner Dan Lokasi

- Owner utama: Purchasing/Returns, app/Modules/Purchasing/Returns.
- PurchaseOrders: published source lookup milik provider dan DTO aman; Returns
  mengonsumsinya tanpa akses repository atau model privat.
- Frontend rencana: resources/js/pages/purchasing/returns dan components lokal.
- Dokumen: [specification](../../specification.md), [plan](plan.md), [tasks](tasks.md).

## Kondisi Awal Dan Bukti Source

PurchaseOrders memiliki draft, penerimaan penuh posted/receipt dan pembayaran
lunas terpisah. PostPurchaseOrder menjaga snapshot baris serta receipt immutable;
PayPurchaseOrder menaikkan version header tanpa mengganti nilai baris.
quantity/conversion_ratio/unit_cost/base_quantity memakai decimal20,6 dan
total_value decimal30,6. Lookup return source sekarang memproyeksikan snapshot
eligible tanpa field payment/actor/notes.
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
| 3 | Passed | Lookup sumber, permission, dan simpan draft melalui HTTP JSON | 87 tests/218 assertions, route/permission, snapshot, audit, no-op |
| 4 | Planned | Lifecycle/version/audit/demo | Feature stale/no-op/rollback/seeder dan fingerprint |
| 5 | Planned | UI/nav/shortcut/browser | TS/lint/build, DevTools dan desktop/tablet |
| 6 | Planned | Native QA, aktivasi dan delivery | MySQL, full regresi, fingerprint lokal dan Git remote |

## Handoff Saat Ini

- Perubahan: published ReturnPurchaseLookup/DTOs, source adapter validasi receipt,
  Returns query/repository/action, create/update API, access catalog, permission
  per action, audit dan version/no-op guard.
- Verifikasi: 18 focused feature tests/137 assertions, full suite, Pint, route:list
  dan `git diff --check`; migration tetap Pending, tidak dijalankan pada DB lokal.
- Browser: SKIPPED karena halaman UI belum masuk scope hingga Increment 5.
- Risiko: lifecycle delete/restore, seeder demo, UI, native MySQL dan aktivasi
  additive belum dibuat atau diverifikasi.
- Old-docs, SampleUI dan artefak user tidak disentuh. Commit tiap increment
  setelah verified; push hanya work item selesai atau diminta eksplisit.
