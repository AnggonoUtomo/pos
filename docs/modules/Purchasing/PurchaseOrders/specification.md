# Spesifikasi Draft Pembelian

## Scope

Daftar/detail/tambah/edit/soft delete/restore draft pembelian, satu supplier dan
satu gudang per dokumen. Maksimal 100 baris, satu barang satu baris. Satuan
terdaftar pada barang dan is_purchase_unit=true; master/satuan dasar harus aktif.
Nomor otomatis PO-{ULID}, status draft dan created_at berasal server.
Catatan opsional maksimal500; tidak disalin ke audit karena dapat sensitif.
Tidak ada nomor invoice supplier, tanggal custom, diskon/pajak, approval,
posting/penerimaan parsial, stok/FIFO, utang, pembayaran, retur atau cetak.
Penambahan tahap tersebut memerlukan work item tersendiri.

## Data Dan Presisi

purchase_orders: ULID, number unique max32, supplier_id/warehouse_id FK restrict,
snapshot code/name supplier/gudang, status draft, version integer mulai1,
notes nullable, demo_key nullable unique internal seeder, total_value
decimal(30,6), timestamps/softDeletes. demo_key tidak diterima dari request.
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
boleh diubah, termasuk super-system; tidak ada endpoint posting. Restore
mempertahankan snapshot dan tidak mengubah stok; master divalidasi kembali
pada simpan berikutnya (dan posting kelak). Draft tidak melarang soft delete/
nonaktif master; draft tetap terbaca via snapshot meski master berubah. Tidak
ada history/usage port baru sebelum posted consumer dibangun.

Audit purchasing.purchase-orders created/updated/deleted/restored harus tersimpan
bersama mutation/baris; failure/veto rollback. Subject ULID, actor auth/null.
Audit hanya number/status/version/supplier_id/warehouse_id/total_value dan
jumlah baris, tanpa catatan, kontak atau payload raw. Restore tidak membuat
baris/audit created tambahan. ID/number/status/total/snapshot/actor dari client
diabaikan. Field input di-whitelist.

## HTTP Dan UI

Prefix /purchasing/purchase-orders, name purchasing.purchase-orders.*.
Session web/auth/verified/CSRF; index/data permission purchasing.purchase-orders.view;
store/update/destroy/restore purchasing.purchase-orders.manage. Tidak auto-grant.
GET index Inertia purchasing/purchase-orders/index, props records/filters/options;
GET data JSON {data,meta}. Pagination25 descending id, eager lines bounded100.
Filter search literal max150 pada number/supplier_name/warehouse_name,
supplier_id/warehouse_id opsional ULID, trashed0/1, page positif.
Options published aktif maksimum1000 per master; item hanya satuan pembelian
dengan unit aktif. Batas lookup mengikuti baseline, remote search ditunda.
JSON create201/id; mutation200/id; web redirect/Sonner. Validation/invariant/
stale422, malformed/missing ULID404, unauthorized403/guest401 atau login redirect.
Restore hanya trashed; update/delete hanya nontrashed. Manage tidak memberi view.

UI admin shadcn/Lucide mengikuti theme existing; komponen fitur lokal. Daftar,
filter/loading/error/empty/pagination, dialog form/detail/lifecycle, Sonner,
dropdown shortcut di atas workspace, nav/palette. Alt+F cari, Ctrl/Cmd+Shift+N
tambah, Ctrl/Cmd+Enter simpan/konfirmasi, Esc tutup. Desktop/tablet Light/Dark,
expanded/collapsed; mobile ditunda. Tidak menampilkan tombol posting palsu.

## Demo Dan QA

Seeder access catalog saja. Demo local/testing melalui Application, memakai
master supplier/gudang/satuan/barang CCTV/kelistrikan existing, tidak men-seed
ulang module lain atau mengubah master. Lookup public digunakan mencari kode/SKU
di pilihan aktif. demo_key internal tetap, seeder memeriksa semua draft termasuk
trashed sebelum menambah; idempotent, tidak menghidupkan ulang user-edit/trashed
bahkan bila catatan diubah pengguna.
Demo tidak otomatis dimasukkan database bisnis lokal karena merupakan dokumen
operasional; dijalankan pada QA disposable atau eksplisit oleh developer.
Aktivasi lokal hanya migration dan catalog permission, tanpa grant/draft user.
Fingerprint existing (termasuk stok/finance/audit) wajib identik.

QA RED/GREEN math, application/audit/validation/lifecycle/stale, HTTP/permission/
CSRF/query/seeder, MySQL disposable unique/FK/current read/up-down, DevTools dahulu
dan browser fixture. Ledger stok/finance harus identik sepanjang lifecycle.
