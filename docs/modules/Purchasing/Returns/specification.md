# Spesifikasi Fondasi Draft Retur Pembelian

Dokumen ini merekam scope fondasi draft yang telah selesai, bukan keseluruhan
capability module saat ini. Posting barang dari layer penerimaan asal sudah
diimplementasikan sesuai [ADR-017](../../../decisions/ADR-017-PURCHASE-RETURN-ORIGINAL-RECEIPT-LAYER.md)
dan [work item posting](../../../work-items/purchase-return-posting/README.md).
Pernyataan "tidak posting" di bawah hanya berlaku untuk increment fondasi draft;
penerimaan uang supplier kini ditetapkan terpisah oleh
[ADR-018](../../../decisions/ADR-018-PURCHASE-RETURN-REFUND-RECEIPTS.md) dan
[work item penerimaan](../../../work-items/purchase-return-refund/README.md).

## Status

Scope produk Disetujui. Increment 2 telah mengimplementasikan scaffold, schema
draft dan value object quantity. Increment 3 menyediakan lookup, persistence,
permission, query, route data/source dan create/update. Increment 4 menyediakan
soft delete/restore draft, version/audit atomic serta demo seeder aman. Increment
5 menyediakan UI dan Increment 6 memverifikasi native MySQL serta aktivasi
additive. Migration aktif lokal; tabel draft kosong.

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
   update tidak menambah version/audit. Delete/restore menaikkan version tepat
   sekali dan wajib audit dalam transaksi. Snapshot/baris tetap ada dan status
   non-draft tidak dapat dihapus/pulihkan termasuk super-system. Pembayaran pembelian dapat menaikkan version
   PO tanpa mengubah snapshot baris; jangan menyamakan version PO dengan draft retur.
8. Restore mempertahankan snapshot, bukan membuat dokumen/baris baru. Master
   inactive/rename/soft-deleted tidak menulis ulang snapshot posted/draft.

Draft bukan reservasi. Validasi jumlah berlaku terhadap sumber per dokumen;
dua draft tidak mengonsumsi hak retur satu sama lain. Tidak ada klaim batas
kumulatif posting, ketersediaan stok atau FIFO sampai writer posting retur
dirancang terpisah. Label UI wajib membedakan permintaan draft dari retur selesai.

## Published Contract

PurchaseOrders mempublikasikan `Application/Contracts/ReturnPurchaseLookup` dan
`Application/DTOs/ReturnPurchaseData` bersama consumer nyata di Returns.
Method `findEligible(id)` memberi satu DTO atau null; `searchEligible(search)`
memberi maksimal25 DTO.

Projection sumber yang dibutuhkan:

- Header: id/number, supplier_id/code/name, warehouse_id/code/name, posted_at.
- Lines: id, item_id, unit_id, base_unit_id, sku/item_name/unit_name/base_unit_name,
  quantity/base_quantity/conversion_ratio/unit_cost/total_value asal.
- Decimal string exact6; tidak mengekspor notes sumber, actor, demo_key,
  request_hash, payment_snapshot, bank, split pembayaran, FIFO layer/consumption.
- Eligibility memerlukan status posted, tidak soft-deleted, stock receipt
  terpasang, dan receipt cocok pada source type/id/nomor/gudang/waktu posting.
  Jumlah baris receipt harus sama; tiap baris harus cocok pada source line/item/
  base unit/kuantitas dasar/nilai total.
- Query memakai snapshot order/baris, pencarian literal max150 dan limit25; tidak
  join master live untuk mengganti nilai historis. Tidak mengekspor notes,
  posted/paid actor, request hash atau payment snapshot. Detail draft membaca
  snapshot lokal.

Input save: `purchase_order_id`, `notes`, `lines[{purchase_order_line_id,
quantity}]`, ditambah `version` pada update. Delete/restore juga menerima version
wajib. Baris menolak key tambahan. ID/nomor/
status/snapshot/harga/rasio/gudang/actor/demo_key bukan authority dari client.
Source order tidak dapat diganti pada update. Delete hanya menemukan dokumen
aktif, restore hanya menemukan dokumen trashed; operasi pada state yang salah
tidak dianggap sukses.
Validasi membership/quantity/snapshot di backend termasuk bagi super-system.
Rencana error: guest login/JSON401, forbidden403, missing404, input/stale422;
audit gagal membatalkan mutation, bukan success palsu.

## Data Yang Direncanakan

Header purchase_returns: ULID, nomor unique, purchase_order_id dengan FK restrict,
nomor/supplier snapshot terindeks, snapshot JSON, status draft, version, notes,
demo_key nullable unique, timestamps dan deleted_at. Baris
purchase_return_lines: ULID, FK restrict ke header dan purchase_order_line,
quantity/base_quantity decimal20,6 (SQLite string panjang21) dan snapshot JSON.
Unique pasangan draft/baris sumber; sumber tidak unique lintas draft. Baris tidak
soft delete independen. Migration down menolak jika masih ada header termasuk
yang soft-deleted, atau baris tersisa, dan hanya menghapus tabel milik Returns.
Domain ReturnQuantity memvalidasi string decimal positif maksimal14 digit integer
dan6 pecahan, quantity <= received, rasio >=1, hasil base quantity exact6 dan
kapasitas decimal20,6. Tidak memakai float. Schema diuji pada SQLite dan MySQL
disposable; migration Returns telah aktif di database lokal setelah aktivasi
terverifikasi dua kali. Tabel lokal masih kosong. Tidak ada tabel
movement/layer/payment atau field "sudah refund".

## Authorization Dan Audit

`purchasing.returns.view` untuk data daftar; `purchasing.returns.manage` untuk
lookup sumber, create/update dan lifecycle draft.
Manage tidak otomatis memberi view, dan izin PurchaseOrders bukan bypass Returns.
Lookup sumber melalui Returns tidak mengharuskan hak CRUD PurchaseOrders/Finance;
permission manage mengizinkan projection terbatas, bukan seluruh detail pembelian.
Controller HasMiddleware dengan `can` per action; usePermission/isSuperSystem
hanya UX. `ReturnsAccessSeeder` mendaftarkan catalog permission tanpa grant.

Audit purchasing.returns created/updated/deleted/restored, subject ULID,
actor dari auth atau null untuk demo/QA. Audit wajib satu transaction dengan
header/baris/version: null/disabled/veto gagal dan rollback seluruh mutation.
Audit hanya ID/nomor/status/version/jumlah baris yang dibutuhkan, tanpa notes,
kontak supplier, credential, payment payload atau salinan snapshot penuh.

## HTTP Dan UI Yang Direncanakan

Prefix `/purchasing/returns`, name `purchasing.returns.*`; web/auth/verified dan
CSRF. `GET /data` memerlukan view; `GET /purchases`, `GET /purchases/{id}`,
`POST /`, `PUT /{id}`, `DELETE /{id}` dan `POST /{id}/restore` memerlukan manage.
Data JSON `{data,meta}`, ukuran halaman global,
search literal max150 atas nomor retur/pembelian/supplier, status all/draft,
trashed0/1 dan page. Purchase search mengembalikan maksimal25 snapshot aman.
Input create/update dan response JSON didokumentasikan di [API](../../../API.md).
Inertia `GET /` tersedia bersama halaman pada Increment 5. Delete/restore
memerlukan version positif dan hanya menerima state active/trashed yang sesuai;
mutation/audit atomic mempertahankan snapshot dan baris. Tidak ada route
post/pay/refund/void. Tidak menampilkan
jumlah total refund atau status kas/utang.

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
Lookup mencari supplier code dan memilih baris CCTV serta kelistrikan dari source
eligible yang tersedia. Tidak ada sumber/baris eligible: skip tanpa mutation. Demo idempotent termasuk marker
draft terhapus/edited, tidak memulihkan atau menimpa pekerjaan user.
Fixture QA boleh menyiapkan alur relasional pada DB disposable terpisah.
Aktivasi lokal hanya migration/catalog; demo draft bukan bagian aktivasi.

## Acceptance Criteria

- [x] CRUD draft dan source lookup hanya pembelian eligible, snapshot safe/exact.
- [x] Invalid qty/base/member/duplicate/forgery/stale ditolak tanpa mutation.
- [x] Lifecycle/version/no-op dan audit atomic terverifikasi.
- [x] Stock/FIFO/receipt/PO/payment asal identik sepanjang lifecycle draft.
- [x] Permission, UI/shortcut, error/retry/readonly dan browser desktop/tablet lulus.
- [x] Demo aman, native MySQL, aktivasi additive dua kali dan delivery terverifikasi.

## Di Luar Scope Dan Risiko Terbuka

Tidak posting/pengiriman fisik, reservasi/batas kumulatif lintas draft, pengurangan
stok/FIFO, supplier refund, utang/parsial/jatuh tempo, approval/cetak/ekspor,
retur tanpa pembelian asal, perubahan harga/satuan/gudang bebas atau reversal.
Desain pemilihan FIFO layer untuk retur supplier dan uang kembali sengaja belum
diputuskan; jangan menyalin aturan layer baru retur penjualan ke pengeluaran stok.
Rencana detail, bukti dan gap conformance:
[work item](work-items/draft-foundation/README.md).
