# Specification: POS Data Dan Keranjang Nyata

## Scope Dan Ownership

Composition root app/Modules/Sales/POS/ServiceProvider.php; route di Routes.
Application mengomposisi published read contract/DTO Items, Units, Warehouses,
Parties dan Stock. Domain menghitung quantity/base quantity/subtotal exact
dengan brick/math existing, bebas Laravel. Posting memakai published Stock dan
Finance writer dalam transaction default bersama, bukan model privat module lain.

Gudang dan pelanggan aktif dipilih eksplisit, tanpa default demo/fallback.
Level berasal dari pelanggan aktif yang levelnya juga aktif. Data kontak tidak
dikirim ke kasir. Harga pasangan item/unit/level diambil dari ItemPriceCatalog;
client tidak boleh menentukan amount/ratio/customer level/stock/total.

## Contract HTTP

Auth/session/CSRF; HasMiddleware can:sales.pos.invoices.create untuk setiap
action. GET /pos tetap pos.index; GET /pos/catalog: warehouse_id/customer_id
ULID wajib, search maksimum 150, page positif, 25 kandidat per page dan has_more.
Pencarian SKU/nama/barcode satuan jual aktif. Hanya barang/base unit/unit aktif
non-trashed. Barang tanpa harga tetap terlihat tetapi satuan tanpa harga disabled.
Saldo per gudang ditampilkan dalam base unit; missing balance berarti nol.

POST /pos/quote: warehouse_id/customer_id dan lines list 1..100, masing-masing
item_id/unit_id ULID dan quantity decimal string positif, maksimal 14 digit
integer/enam pecahan. Pasangan item/unit tidak boleh berulang. Unit jual harus
milik barang dan aktif. Backend membaca ulang seluruh master/price/stock dalam
read transaction, menghitung ulang, dan menjumlah kebutuhan base unit per item
lintas satuan. Stok kurang/zero dan harga hilang ditolak 422 termasuk super-system.
Field asing seperti price/discount/tax/payment ditolak, bukan dipercaya.

Output quote: lines dengan snapshot nama/SKU/satuan/ratio, quantity/base_quantity,
unit_price/subtotal exact dan subtotal keranjang. Quantity/base quantity scale6
harus exact/muat20,6; nilai uang scale2 harus exact/muat30,2. Kebutuhan rounding
ditolak sampai kebijakan transaksi ditetapkan; tidak membulatkan diam-diam.
Quote bukan dokumen posted, bukan reservasi, dan tidak menjamin stok/harga saat
posting nanti. Tidak ada ID transaksi, success payment atau ledger/audit mutation.

## UI

resources/js/pages/sales/pos, komponen dekat page. Pilihan context, server search,
barcode, pagination, cart multi-satuan dan quantity Indonesia. Subtotal hanya
hasil backend; perubahan context/line langsung membatalkan quote lama. Tidak
menampilkan total stale saat loading/error. Response request lama tidak boleh
menimpa context/line terbaru. Reset konfirmasi saat cart berisi. Shortcut dropdown
di atas workspace: Alt+F cari, Ctrl/Cmd+Enter periksa keranjang. Payment disabled.
Loading/empty/error, Sonner, shadcn/Lucide/theme existing. Desktop/tablet QA.

## Data, Demo Dan Risiko

Scope keranjang tidak membuat dokumen mutable atau audit read. Schema posted
ditambahkan pada bagian posting di bawah. POSDemoSeeder local/testing no-op dengan
penjelasan; fixture mengomposisi seeder module pemilik secara eksplisit.
Permission catalog boleh ditambahkan idempotent, tidak mengubah grant user.
Read-only quote tidak dicatat sebagai business mutation pada activity log.
Pelanggan/pilihan unit/gudang memakai bounded list maksimal 1000; katalog
barang dipaginasi. Customer remote search dapat menjadi pekerjaan berikutnya.

## Posting Lunas Atomic

Backend posting tersedia pada Increment 4 checkout; UI Pembayaran tetap disabled
sampai Increment 5. Quote menambah warehouse, base_unit_id/base_unit_name per
line dan fingerprint deterministic SHA256 atas context/snapshot/total tanpa
checked_at. Bukan reservasi atau otoritas harga. Posting menghitung ulang current
master/harga/stok di bawah lock dan menolak quote_fingerprint yang berubah422.

POST /pos/invoices (pos.invoices.store): idempotency_key ULID, warehouse_id,
customer_id, quote_fingerprint64 hex lowercase, lines seperti quote, dan
payments list1..10 method_id ULID/amount decimal string positif scale2.
Field asing root/line/payment ditolak; actor/number/price/ratio/HPP/total/
discount/tax/status berasal server. Action PostInvoice memvalidasi input juga
untuk caller non-HTTP. Auth/CSRF dan middleware create+post wajib, bukan finance
CRUD. GET /pos/payment-methods (pos.payment-methods) memakai permission sama,
menghasilkan methods id/code/name/type aktif/compatible tanpa metadata rekening.

GET /pos/invoices/{id} (pos.invoices.show): snapshot JSON, pemilik dengan post
atau staff invoices.view. Guest401/redirect login, tanpa hak403, ID tidak ada404.
Create/retry JSON201, invalid422, CSRF419. Receipt tidak memuat HPP, hash,
idempotency key, actor ID, kontak pelanggan atau metadata rekening. Tidak ada
PUT/DELETE/soft-delete posted. View tidak memberi hak post dan sebaliknya.

sales_invoices menyimpan ULID/number POS-ULID, actor_id/idempotency_key unik,
request_hash canonical, snapshot gudang/pelanggan/level, subtotal/total,
discount_amount/tax_amount eksplisit0, total_cost, payment snapshot aman serta
referensi opaque Stock issue/Finance receipt. sales_invoice_lines menyimpan
ULID, line_number, snapshot item/unit/base/ratio/quantity/base_quantity/harga/
subtotal/diskon/pajak/HPP. FK master/header RESTRICT, tanpa FK circular sumber.
Money30,2, quantity20,6 dan FIFO cost30,6 exact; SQLite disposable memakai string
untuk kolom POS agar tidak kehilangan digit. Tidak ada audit read palsu.

Payload canonical sort item/unit dan method, normalize ID/decimal; scope key per
actor. Replay sama mengembalikan snapshot sebelum validasi ulang customer/item,
bukan harga saat ini. Payload beda ditolak. Race unique index me-rollback seluruh
mutation calon invoice lalu membaca pemenang dengan current read. Actor lain
memiliki ruang key sendiri. Tidak menjamin pengembalian replay untuk gudang yang
diubah nonaktif melalui SQL di luar guard aplikasi.

Lock urutan: warehouse -> active levels terurut -> customer level/customer ->
unit terurut -> item/relasi terurut -> price -> stock balance/layers -> Finance
account/method -> invoice/audit. Discovery assignment/base unit/satuan diperiksa
ulang current; drift ditolak. Stock SaleStockWriter::issue(SaleStockRequest)
menghasilkan SaleStockResult (issue ID/map source line cost); Finance
SalePaymentWriter::receive(SalePaymentRequest) menghasilkan SalePaymentResult
(receipt ID/snapshot aman). Kedua writer wajib di dalam transaction caller.
Semua data/audit Stock, Finance dan POS commit/rollback bersama. Audit POS
sales.pos/posted wajib tersimpan, veto/disabled logger menyebabkan rollback.

Lock active levels sengaja mengikuti editor harga existing; dapat menserialisasi
checkout lintas gudang. Ini baseline konservatif, bukan klaim throughput/load
test. Optimasi granularity memerlukan audit urutan mutation tersendiri.

actor_id adalah identifier historis tanpa FK user, mengikuti posted_by Stock/
Finance. Lifecycle/retention akun tidak diubah oleh increment ini. Sebelum
penggunaan produksi dengan transaksi real, penghapusan akun dan keterlacakan
actor tetap memerlukan keputusan retention/ownership seperti gap Identity
existing; bukan alasan menghapus invoice atau audit ketika user dihapus.
