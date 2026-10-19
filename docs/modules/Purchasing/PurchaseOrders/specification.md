# Spesifikasi Draft Dan Penerimaan Pembelian

## Scope

Daftar/detail/tambah/edit/soft delete/restore draft pembelian, satu supplier dan
satu gudang per dokumen. Maksimal 100 baris, satu barang satu baris. Satuan
terdaftar pada barang dan is_purchase_unit=true; master/satuan dasar harus aktif.
Nomor otomatis PO-{ULID}, status awal draft dan created_at berasal server.
Catatan opsional maksimal500; tidak disalin ke audit karena dapat sensitif.
Tidak ada nomor invoice supplier, tanggal custom, diskon/pajak atau approval.
Penerimaan penuh satu kali tersedia: posting menambah saldo/movement/layer FIFO.
Pembayaran lunas sesudah receipt tersedia terpisah. Tidak ada penerimaan/
pembayaran parsial, utang/jatuh tempo, uang muka, retur/reversal atau cetak.
Penambahan tahap tersebut memerlukan work item tersendiri.

## Data Dan Presisi

purchase_orders: ULID, number unique max32, supplier_id/warehouse_id FK restrict,
snapshot code/name supplier/gudang, status draft/posted, version integer mulai1,
notes nullable, demo_key nullable unique internal seeder, total_value
decimal(30,6), timestamps/softDeletes untuk draft. stock_receipt_id nullable
unique FK restrict, posted_from_version/posted_at/posted_by nullable dari server.
payment_disbursement_id nullable unique FK restrict, payment_snapshot JSON,
paid_from_version/paid_at/paid_by nullable; existing tidak di-backfill lunas.
demo_key dan field posting tidak diterima dari request.
purchase_order_lines: ULID, purchase_order_id/item_id/unit_id/base_unit_id FK
restrict, snapshot SKU/nama/satuan/base unit, quantity/conversion_ratio/unit_cost/
base_quantity decimal(20,6), total_value decimal(30,6), unique order/item.
Baris milik draft di-replace atomic; hanya header memakai soft delete.

Input quantity/unit_cost decimal string positif, maksimal14 digit integer dan6
pecahan. Ratio authoritative dari Items minimal1. Perkalian quantity*ratio dan
quantity*unit_cost harus exact scale6; rounding/overflow ditolak. Total header
penjumlahan backend exact, bukan dari browser. Brick Math existing, tanpa float.
SQLite QA memakai string decimal; MySQL DECIMAL. UI Indonesia: 2, 1,5,
120.000, bukan fixed enam nol. Input grouping titik dan pecahan koma dikonversi
ke canonical decimal string di request. Nilai preview UI bukan authority.

## Lifecycle Dan Boundary

Application mengelola validation, transaction retry3 dan invariant draft.
Lock: dokumen existing, gudang, supplier, satuan sorted, barang sorted; data
master dibaca ulang current di transaction. Binding port-adapter di provider.
SupplierLookup baru dipublikasikan karena consumer draft nyata; DTO hanya
id/code/name tanpa kontak. ItemStockLookup/UnitLookup/WarehouseLookup digunakan
tanpa mengimpor Domain/Infrastructure privat module lain.

Edit/delete/restore wajib version existing; perubahan stale ditolak422, tidak
menimpa revision baru. No-op edit tanpa audit/revision. Hanya status draft
boleh diubah, termasuk super-system. Restore mempertahankan snapshot dan tidak
mengubah stok; master divalidasi kembali
pada simpan berikutnya atau posting. Draft tidak melarang soft delete/
nonaktif master; draft tetap terbaca via snapshot meski master berubah. Tidak
ada history/usage port baru Supplier; posted memakai snapshot dan FK restrict.
Supplier boleh nonaktif/soft delete setelah posted, tanpa mengubah histori.

PurchaseOrders mempublikasikan `Application/Contracts/ReturnPurchaseLookup` dan
`Application/DTOs/ReturnPurchaseData` untuk consumer retur pembelian nyata.
`findEligible(id)` dan `searchEligible(search)` hanya mengembalikan order posted,
aktif, dengan receipt yang cocok pada source type/id/nomor/gudang/waktu. Projection
menggunakan snapshot header/baris exact6 tanpa notes, actor, request hash,
payment snapshot atau data rekening. Adapter berada di PurchaseOrders dan tidak
mengembalikan model/repository. Search literal max150 pada nomor pembelian, kode/
nama supplier dan nama gudang; maksimal25 sumber.

Posting full melalui PostPurchaseOrder dan public Stock/PurchaseStockWriter,
DTOs PurchaseStockRequest/PurchaseStockResult, bukan repository privat.
Lock dokumen/baris, gudang, supplier, selected/base units sorted, items sorted,
receipt/saldo/layer. Kuantitas/harga mengikuti draft; base unit/ratio/base quantity/
total baris/header berbeda dari hasil revalidasi ditolak, wajib simpan ulang.
Nama/SKU diperbarui saat posting lalu immutable. Semua status/baris/audit dan
ledger Stock satu transaction retry3; gagal salah satu membatalkan semuanya.
Draft terhapus tidak boleh posted; posted tidak boleh edit/delete/restore.
posted_from_version menyimpan versi request pertama, version naik sekali.
Replay versi asal mengembalikan receipt lama sebelum lookup master, tanpa
mutation/audit baru; versi lain ditolak. Finance tidak dicatat.

Audit purchasing.purchase-orders created/updated/deleted/restored/posted wajib
tersimpan bersama mutation/baris; failure/veto rollback. Subject ULID, actor auth/null.
Audit hanya number/status/version/supplier_id/warehouse_id/total_value dan
jumlah baris, serta receipt/time saat posting, tanpa catatan, kontak atau payload
raw. Restore tidak membuat baris/audit created tambahan.
ID/number/status/total/snapshot/actor dari client
diabaikan. Field input di-whitelist.

## HTTP Dan UI

Prefix /purchasing/purchase-orders, name purchasing.purchase-orders.*.
Session web/auth/verified/CSRF; index/data permission purchasing.purchase-orders.view;
store/update/destroy/restore purchasing.purchase-orders.manage; post memakai
purchasing.purchase-orders.post, pay memakai purchasing.purchase-orders.pay.
Keempatnya terpisah; tidak auto-grant.
GET index Inertia purchasing/purchase-orders/index, props records/filters/options;
GET data JSON {data,meta}. Pagination25 descending id, eager lines bounded100.
Filter search literal max150 pada number/supplier_name/warehouse_name,
supplier_id/warehouse_id opsional ULID, status all/draft/posted default all,
trashed0/1, page positif.
Options published aktif maksimum1000 per master; item hanya satuan pembelian
dengan unit aktif. Batas lookup mengikuti baseline, remote search ditunda.
JSON create201/id; mutation200/id; web redirect/Sonner. Validation/invariant/
stale422, malformed/missing ULID404, unauthorized403/guest401 atau login redirect.
Restore hanya trashed draft; update/delete hanya nontrashed draft. Manage/post
tidak memberi view. POST /{id}/post hanya menerima version (CSRF diizinkan),
field lain ditolak422. JSON200 id/status/version/stock_receipt_id/posted_at;
web redirect/Sonner. Server menjadi authority, bukan state tombol frontend.

UI admin shadcn/Lucide mengikuti theme existing; komponen fitur lokal. Daftar,
filter/loading/error/empty/pagination, dialog form/detail/lifecycle, Sonner,
dropdown shortcut di atas workspace, nav/palette. Alt+F cari, Ctrl/Cmd+Shift+N
tambah, Ctrl/Cmd+Enter simpan/konfirmasi, Esc tutup. Desktop/tablet Light/Dark,
expanded/collapsed; mobile ditunda. Tombol Terima hanya draft aktif berizin post,
konfirmasi supplier/gudang/baris/total sebelum posting. Badge Draft/Diterima,
filter status, detail posted readonly menampilkan waktu server. Tidak memberi
kesan pembayaran lunas; error/loading mempertahankan dialog untuk koreksi/retry.

## Demo Dan QA

Seeder access catalog saja. Demo local/testing melalui Application, memakai
master supplier/gudang/satuan/barang CCTV/kelistrikan existing, tidak men-seed
ulang module lain atau mengubah master. Lookup public digunakan mencari kode/SKU
di pilihan aktif. demo_key internal tetap, seeder memeriksa semua draft termasuk
trashed sebelum menambah; idempotent, tidak menghidupkan ulang user-edit/trashed
bahkan bila catatan diubah pengguna. PurchaseOrdersReceiptDemoSeeder eksplisit
local/testing memakai action posting; skip posted/trashed/versi berubah/catatan
diubah. Tidak membuat akun, grant, Sales atau Finance.
Demo tidak otomatis dimasukkan database bisnis lokal karena merupakan dokumen
operasional; dijalankan pada QA disposable atau eksplisit oleh developer.
Aktivasi lokal hanya migration dan catalog permission, tanpa grant/draft user.
Fingerprint existing (termasuk stok/finance/audit) wajib identik.

QA RED/GREEN math, application/audit/validation/lifecycle/stale, HTTP/permission/
CSRF/query/seeder, MySQL disposable unique/FK/current read/up-down, DevTools dahulu
dan browser fixture. Draft lifecycle tidak mengubah stok/finance; posting
memutasi Stock atomic dan Finance tetap identik. Native MySQL disposable
membuktikan duplicate post serta receipt-versus-sale tanpa lost update.
Lihat [hasil penerimaan](../../../work-items/purchase-full-receipt/tasks.md).

## Pembayaran Lunas Sesudah Penerimaan

POST /{id}/pay hanya version dan payments1..10 {method_id,amount}; field lain422,
CSRF diizinkan. Total dari snapshot order, amount canonical string positif max24
digit integer/6 pecahan. Metode unik, active/compatible melalui public Finance
PurchasePaymentWriter/DTOs; opsi aktif via PurchasePaymentMethods hanya diberikan
kepada pemegang pay. Tidak membutuhkan hak CRUD master Finance.

Order wajib posted/receipt stok tersedia dan tidak terhapus. Supplier/item
posted tidak divalidasi ulang. Lock purchase/baris lalu akun/metode sorted dan
ledger, retry3. Finance disbursement dan pointer/snapshot/dua audit satu transaksi.
Tidak mengubah Stock/FIFO, status receipt atau receipt kas masuk penjualan.
paid_from_version menyimpan versi asal; version naik sekali. Replay versi asal
dengan split identik mengembalikan hasil lama sebelum lookup master, tanpa
mutation/audit tambahan atau mengganti actor. Versi/payload beda ditolak422.

JSON200 id/status/version/payment_disbursement_id/payment_snapshot/paid_at;
web redirect/Sonner. Snapshot publik total/payments method_id/name/type/amount,
tanpa account_id/metadata bank. Paid immutable, bukan edit posted atau soft delete.
Filter payment_status all/paid/unrecorded; unrecorded hanya posted tanpa pointer,
bukan label utang. UI badge Lunas/Belum dicatat, draft '-', dialog multi-metode
dengan format Indonesia/preview exact6, shortcut dialog Ctrl/Cmd+Enter/Esc, dan
detail readonly. Backend tetap authority untuk total tepat tanpa kembalian.

PurchaseOrdersPaymentDemoSeeder local/testing eksplisit, prerequisite master
aktif, memakai workflow receipt/pay nyata tanpa seed module lain; skip demo
edited/trashed/paid. Tidak berjalan saat aktivasi. Tidak menghitung saldo kas/
bank atau kecukupan kas. Bukti [pembayaran](../../../work-items/purchase-paid-settlement/tasks.md)
dan [ADR014](../../../decisions/ADR-014-PURCHASE-PAYMENT-DISBURSEMENTS.md).
