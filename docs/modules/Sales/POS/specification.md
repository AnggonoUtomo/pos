# Specification: POS Data Dan Keranjang Nyata

## Scope Dan Ownership

Composition root app/Modules/Sales/POS/ServiceProvider.php; route di Routes.
Application mengomposisi published read contract/DTO Items, Units, Warehouses,
Parties dan Stock. Domain menghitung quantity/base quantity/subtotal exact
dengan brick/math existing, bebas Laravel. Tidak ada mutation lintas module.

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

Tidak ada schema/tabel baru, soft delete atau event baru: belum ada dokumen
mutable/posted milik POS. POSDemoSeeder local/testing sengaja no-op dengan
penjelasan; fixture mengomposisi seeder module pemilik secara eksplisit.
Permission catalog boleh ditambahkan idempotent, tidak mengubah grant user.
Read-only quote tidak dicatat sebagai business mutation pada activity log.
Pelanggan/pilihan unit/gudang memakai bounded list maksimal 1000; katalog
barang dipaginasi. Customer remote search dapat menjadi pekerjaan berikutnya.
