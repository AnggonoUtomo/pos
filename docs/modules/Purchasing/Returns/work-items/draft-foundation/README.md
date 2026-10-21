# Work Item: Fondasi Draft Retur Pembelian

## Status

Selesai. Increment 1-6 telah diverifikasi dan work item telah dipush. Migration
Returns lokal aktif; tabel draft masih kosong. Posting stok/FIFO dan refund
supplier tetap di luar scope.

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

- [x] Source eligible safe, membership dan quantity/base exact terbukti.
- [x] Simpan/lifecycle/version/no-op dan audit mandatory atomic terbukti.
- [x] Semua operasi draft menjaga source/receipt/Stock/FIFO/Finance identik.
- [x] Permission, UI, demo aman serta QA native/browser sesuai spec lulus.
- [x] Aktivasi lokal hanya schema/catalog, idempotent dan tanpa grant/demo/reset.
- [x] Dokumen aktual/checklist, scoped commit/push dan remote SHA verified.

## Dampak Yang Harus Dicek

| Area | Rencana dan batas |
| --- | --- |
| Database | Header/baris draft milik Returns; ULID/soft delete/version/snapshot. Migration hanya di module. |
| Boundary | Published read lookup/DTOs milik PurchaseOrders untuk consumer nyata Returns; tidak ekspor model/repository. |
| Route/permission | purchasing.returns.view/manage pada route draft/lookup/delete/restore; catalog additive tanpa auto-grant. |
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
| 4 | Passed | Lifecycle/version/audit/demo | 28 focused tests/216 assertions, full suite 733/5800, Pint, 7 routes, fingerprint |
| 5 | Passed | UI/nav/shortcut/browser | 734 tests/5824 assertions, TS/lint/build, DevTools desktop/tablet |
| 6 | Passed | Native QA, aktivasi dan delivery | MySQL disposable dan aktivasi lokal dua kali; 734 tests/5824 assertions, build/typecheck/lint/Pint, route/status lulus |

## Handoff Increment 4

- Perubahan: DELETE/restore hanya draft dengan version guard, soft delete menjaga
  snapshot/baris, audit atomik; demo local/testing mencari source CCTV posted
  existing lewat lookup published dan skip bila tidak tersedia.
- Verifikasi: 28 test Returns/PurchaseOrders lookup/216 assertions, full suite
  733 tests/5800 assertions, Pint scoped, 7 route dan whitespace lulus. Seeder
  idempotent untuk marker edited/trashed; source, receipt, Stock/FIFO, Finance,
  permission/grant tetap identik. Migration lokal Pending, tidak dijalankan.
- Browser: SKIPPED karena UI belum masuk scope sampai Increment 5.
- Risiko: UI, native MySQL/aktivasi dan delivery keseluruhan masih menunggu.
- SampleUI, Old-docs dan artefak LSP user tetap tidak disentuh; tidak push.

## Handoff Increment 5

- Perubahan: Inertia workspace draft, source picker, form/detail, soft delete/
  restore, permission-aware navigation, command palette dan shortcut. Tidak ada
  posting stok/FIFO atau refund supplier.
- Verifikasi: 734 tests/5824 assertions, typecheck, ESLint, build, Pint, route dan
  DevTools desktop/tablet; fingerprint Stock/FIFO/payment tidak berubah.
- Delivery: commit `1409d7b` telah dipush dan diverifikasi sama dengan `origin/main`.
- Risiko tersisa: migration lokal masih Pending; native MySQL/aktivasi belum
  dijalankan. Increment 6 melanjutkan dengan database disposable terlebih dahulu.

## Handoff Increment 6

- Perubahan: skrip QA MySQL native dan aktivasi additive reusable; dokumentasi
  API/module/quality diperbarui mengikuti route dan keadaan migration aktual.
- Verifikasi: native MySQL up/down/up, presisi DECIMAL(20,6), concurrency stale,
  audit rollback, unique/FK dan populated rollback guard lulus. Fingerprint
  source/receipt/Stock/FIFO/Finance/audit asal identik.
- Aktivasi disposable dua kali dan aktivasi lokal dua kali lulus. Hanya migration
  serta dua permission catalog ditambahkan; tidak ada grant, demo, receipt,
  payment atau perubahan tabel/data existing. Tabel draft lokal kosong.
- Regresi penuh 734 tests/5824 assertions; TypeScript, ESLint, build, Pint,
  route dan migration status lulus. QA browser desktop/tablet sudah PASS pada
  Increment 5 dan tidak ada perubahan UI di increment ini.
- Delivery diverifikasi sama dengan `origin/main`; tidak ada perubahan pada
  `SampleUI/`, `Old-docs/` atau file LSP user.

## Handoff Increment 3

- Perubahan: published ReturnPurchaseLookup/DTOs, source adapter validasi receipt,
  Returns query/repository/action, create/update API, access catalog, permission
  per action, audit dan version/no-op guard.
- Verifikasi: 18 focused feature tests/137 assertions, full suite, Pint, route:list
  dan `git diff --check`; migration tetap Pending, tidak dijalankan pada DB lokal.
- Browser: SKIPPED karena halaman UI belum masuk scope hingga Increment 5.
- Risiko saat itu: lifecycle delete/restore, seeder demo, UI, native MySQL dan
  aktivasi additive belum dibuat atau diverifikasi.
- Old-docs, SampleUI dan artefak user tidak disentuh. Commit tiap increment
  setelah verified; push hanya work item selesai atau diminta eksplisit.
