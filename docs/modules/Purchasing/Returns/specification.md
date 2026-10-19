# Spesifikasi Fondasi Draft Retur Pembelian

## Status

Scope produk Disetujui. Dokumen ini baseline rencana, bukan bukti Implemented.
Belum ada module runtime. Detail schema/contract dikonfirmasi terhadap generator
dan source terkini sebelum implementasi, tanpa mengubah scope diam-diam.

## Tujuan Dan Scope

Staff pembelian dapat membuat, melihat, memperbarui, menghapus lunak dan
memulihkan draft retur dari satu pembelian yang telah diterima. Barang, supplier,
gudang, satuan, rasio dan harga beli diambil dari snapshot pembelian asal.
Tidak memilih ulang master/harga terkini atau mengganti gudang asal pada draft.

Baseline PurchaseOrders saat ini menerima seluruh baris sekaligus: status posted,
stock_receipt_id dan posted_at lengkap menunjukkan penerimaan penuh. Karena
belum ada penerimaan parsial, jumlah diterima mengikuti quantity baris posted,
bukan saldo gudang saat ini. Sumber draft/terhapus atau metadata receipt tidak
lengkap tidak eligible. Pembelian diterima yang sudah dibayar maupun belum
dicatat pembayarannya sama-sama dapat menjadi sumber; draft tidak menilai hak uang.

## Arsitektur Dan Ownership

- Hexagon: Purchasing/Returns; composition root di
  app/Modules/Purchasing/Returns/ServiceProvider.php.
- Presentation: controller/request/route dan Inertia, tanpa persistence langsung.
- Application: query workspace/sumber, save dan lifecycle draft, validation,
  transaction, version, audit dan port persistence internal.
- Domain: kuantitas/base quantity dan invariant decimal murni; tanpa Laravel,
  Eloquent atau import Domain privat Sales/Returns.
- Infrastructure: implementasi persistence/audit milik module.
- PurchaseOrders mempublikasikan lookup snapshot minimal; adapter-nya membaca
  tabel milik PurchaseOrders, bukan mengembalikan Eloquent model/repository.
- Tidak ada contract mutation Stock/Finance, event baru atau framework reusable.

## Aturan Draft Dan Presisi

1. Satu dokumen merujuk satu purchase_order_id, immutable setelah dibuat.
2. Minimal satu dan maksimal100 baris. Satu baris sumber hanya sekali per draft;
   baris harus benar-benar milik pembelian yang dipilih.
3. Quantity canonical string positif, maksimal14 digit integer/6 pecahan,
   tidak melebihi quantity diterima pada baris asal.
4. Satuan wajib satuan snapshot baris asal. base_quantity = quantity retur
   dikali conversion_ratio asal, exact scale6 tanpa rounding/float; overflow
   atau hasil yang tidak dapat direpresentasikan ditolak.
5. unit_cost dan total_value asal tetap decimal string scale6. Harga ini adalah
   harga beli historis, bukan biaya layer FIFO atau nominal refund supplier.
   Draft tidak menghitung settlement uang/total refund; presisi monetary retur
   untuk posting/refund harus diputuskan pada work item berikutnya.
6. Status hanya draft pada scope ini. Nomor server direncanakan PR-{ULID},
   immutable; timestamp server dan notes opsional maksimal500.
7. Update/delete/restore memakai version draft sendiri. Stale ditolak, no-op
   tidak menambah version/audit. Pembayaran pembelian dapat menaikkan version
   PO tanpa mengubah snapshot baris; jangan menyamakan version PO dengan draft retur.
8. Restore mempertahankan snapshot, bukan membuat dokumen/baris baru. Master
   inactive/rename/soft-deleted tidak menulis ulang snapshot posted/draft.

Draft bukan reservasi. Validasi jumlah berlaku terhadap sumber per dokumen;
dua draft tidak mengonsumsi hak retur satu sama lain. Tidak ada klaim batas
kumulatif posting, ketersediaan stok atau FIFO sampai writer posting retur
dirancang terpisah. Label UI wajib membedakan permintaan draft dari retur selesai.

## Contract Yang Direncanakan

Nama kerja published lookup PurchaseOrders: ReturnPurchaseLookup::find(id) dan
search(search), mengembalikan ReturnPurchaseData/header serta lines minimal.
Dibuat hanya bersama consumer query/action Returns yang nyata.

Projection sumber yang dibutuhkan:

- Header: id/number, supplier_id/code/name, warehouse_id/code/name, posted_at.
- Lines: id, item_id, unit_id, base_unit_id, sku/item_name/unit_name/base_unit_name,
  quantity/base_quantity/conversion_ratio/unit_cost/total_value asal.
- Decimal string exact6; tidak mengekspor notes sumber, actor, demo_key,
  request_hash, payment_snapshot, bank, split pembayaran, FIFO layer/consumption.
- Query hanya sumber eligible, pencarian literal bounded, snapshot tidak join
  master live untuk mengganti nilai historis. Detail draft membaca snapshot lokal.

Rencana input save: purchase_order_id, notes, lines[{purchase_order_line_id,
quantity}], ditambah version pada update. ID/nomor/status/snapshot/harga/rasio/
gudang/actor/demo_key bukan authority dari client. Lifecycle hanya version.
Validasi membership/quantity/snapshot di backend termasuk bagi super-system.
Rencana error: guest login/JSON401, forbidden403, missing404, input/stale422;
audit gagal membatalkan mutation, bukan success palsu.

## Data Yang Direncanakan

Header purchase_returns: ULID, nomor unique, purchase_order_id, nomor/supplier
snapshot untuk pencarian, snapshot JSON safe, status draft, version, notes,
demo_key internal nullable unique, timestamps dan deleted_at.
Lines purchase_return_lines: ULID, purchase_return_id, purchase_order_line_id,
quantity/base_quantity decimal20,6 dan snapshot JSON baris asal. Unique pasangan
draft/baris sumber; tidak unique sumber lintas draft. Baris tidak soft delete
independen; save mengganti isi baris secara atomik, soft delete melalui header.
FK restrict direncanakan untuk menjaga referensi sumber; kunci schema/migration
final saat Increment 2, termasuk perilaku rollback berisi draft dan SQLite tests.
Tidak ada tabel movement/layer/payment atau field "sudah refund" palsu.

## Authorization Dan Audit

Rencana key purchasing.returns.view untuk index/data/detail snapshot draft;
purchasing.returns.manage untuk lookup sumber/create/update/delete/restore.
Manage tidak otomatis memberi view, dan izin PurchaseOrders bukan bypass Returns.
Lookup sumber melalui Returns tidak mengharuskan hak CRUD PurchaseOrders/Finance;
permission manage mengizinkan projection terbatas, bukan seluruh detail pembelian.
Controller HasMiddleware dengan can per action; usePermission/isSuperSystem hanya UX.

Audit purchasing.returns created/updated/deleted/restored, subject ULID,
actor dari auth atau null untuk demo/QA. Audit wajib satu transaction dengan
header/baris/version: null/disabled/veto gagal dan rollback seluruh mutation.
Audit hanya ID/nomor/status/version/jumlah baris yang dibutuhkan, tanpa notes,
kontak supplier, credential, payment payload atau salinan snapshot penuh.

## HTTP Dan UI Yang Direncanakan

Prefix /purchasing/returns, name purchasing.returns.*; web/auth/verified dan CSRF.
GET index/data, lookup purchases dan purchases/{id}, POST store,
PUT {id}, DELETE {id}, POST {id}/restore. Tidak ada route post/pay/refund/void.
Detail draft boleh lewat data snapshot daftar seperti pola Returns existing;
nama method/controller/response final dikunci bersama implementasi dan docs/API.md.
Pagination25, eager lines bounded100, search literal nomor retur/pembelian/supplier,
trashed dan page. Tidak menampilkan jumlah total refund atau status kas/utang.

Page purchasing/returns/index; components/hooks/types lokal bila diperlukan.
Pakai shadcn/ui, Lucide semantic dengan tooltip, Sonner success/error CRUD,
workspace shortcut dropdown dan nav/palette existing. Alt+F cari,
Ctrl/Cmd+Shift+N tambah, Ctrl/Cmd+Enter simpan/konfirmasi dari level dialog,
Esc tutup saat aman. Mode readonly untuk viewer/detail.
Loading/empty/error/403/422/retry snapshot, abort request lama, disable submit
ganda dan format Indonesia wajib. Tidak mengganti theme atau mengklaim draft
telah mengeluarkan stok/menerima uang. QA1440x1000,1024x768,768x1024,
Light/Dark dan sidebar expanded/collapsed; mobile di luar scope.

## Seeder Demo

Seeder module local/testing eksplisit melalui Application, hanya membuat draft
relasional dari pembelian diterima existing yang relevan CCTV/kelistrikan
(perangkat unit/set, kabel meter/rol, konektor buah/pak/dus sesuai baris sumber).
Tidak seed ulang master, membuat PO/receipt/payment, mengubah harga atau grant.
Tidak ada sumber eligible: skip dengan alasan. Demo idempotent termasuk marker
draft terhapus/edited, tidak memulihkan atau menimpa pekerjaan user.
Fixture QA boleh menyiapkan alur relasional pada DB disposable terpisah.
Aktivasi lokal hanya migration/catalog; demo draft bukan bagian aktivasi.

## Acceptance Criteria

- [ ] CRUD draft dan source lookup hanya pembelian eligible, snapshot safe/exact.
- [ ] Invalid qty/base/member/duplicate/forgery/stale ditolak tanpa mutation.
- [ ] Lifecycle/version/no-op dan audit atomic terverifikasi.
- [ ] Stock/FIFO/receipt/PO/payment asal identik sepanjang lifecycle draft.
- [ ] Permission, UI/shortcut, error/retry/readonly dan browser desktop/tablet lulus.
- [ ] Demo aman, native MySQL, aktivasi additive dua kali dan delivery terverifikasi.

## Di Luar Scope Dan Risiko Terbuka

Tidak posting/pengiriman fisik, reservasi/batas kumulatif lintas draft, pengurangan
stok/FIFO, supplier refund, utang/parsial/jatuh tempo, approval/cetak/ekspor,
retur tanpa pembelian asal, perubahan harga/satuan/gudang bebas atau reversal.
Desain pemilihan FIFO layer untuk retur supplier dan uang kembali sengaja belum
diputuskan; jangan menyalin aturan layer baru retur penjualan ke pengeluaran stok.
Rencana detail, bukti dan gap conformance:
[work item](work-items/draft-foundation/README.md).
