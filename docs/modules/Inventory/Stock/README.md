# Inventory/Stock

StockAvailability adalah published read boundary POS untuk saldo base unit per
gudang. Default read tidak mengunci/mereservasi stok atau melakukan pengeluaran;
mode lock untuk posting memerlukan transaction caller. Consumer PostInvoice
memvalidasi ulang quote dan memakai SaleStockWriter untuk pengeluaran/FIFO atomic.

## Status

Active. Draft/posting, saldo/kartu stok dan fondasi layer FIFO sudah
diimplementasikan dan QA/delivery Increment 6 selesai;
[perbaikan dependency terpisah](../../../work-items/dependency-security-patch/README.md)
juga telah lulus gate.

## Tujuan Dan Boundary

Memiliki dokumen stok awal draft/posted, saldo dalam satuan dasar, movement,
dan layer FIFO per barang + gudang. Activity log bukan ledger stok.
Tidak memiliki dokumen penjualan/pembelian, transfer, adjustment, reversal UI,
payment, pajak, diskon, atau harga level pelanggan.

## Public Boundary Dan Dependency

- Items menyediakan lookup barang/satuan melalui contract dan DTOs publik.
- Warehouses menyediakan lookup gudang melalui contract dan DTOs publik.
- Units.UnitLookup memvalidasi dan mengunci satuan aktif.
- StockUsage menyediakan guard histori untuk consumer Items/Warehouses.
- SaleStockWriter dikonsumsi PostInvoice POS untuk pengeluaran dan FIFO.
- PurchaseStockWriter dikonsumsi PostPurchaseOrder untuk receipt/movement/FIFO.
- PurchaseReturnStockWriter dikonsumsi Purchasing/Returns untuk issue dari sisa
  layer receipt pembelian asal; layer lain tidak boleh menggantikan sumber.
- Tidak membuat event atau port tanpa consumer transaksi nyata.

## Permission Dan Operasi

`inventory.stock.view`, `inventory.stock.manage`, `inventory.stock.post`.
Backend tetap authority; super-system tidak melewati invariant bisnis.
Migration, permission seeder dan demo seeder berada di module.
Demo hanya dijalankan eksplisit dan tidak mengubah akun/role/credential.

## Operasi

Halaman `/inventory/stock` memiliki Stok awal, Saldo stok dan Kartu stok.
Migration additive dan permission seeder sudah diterapkan pada MySQL lokal;
tidak ada demo Stock yang diposting otomatis pada data user.

```powershell
php artisan db:seed --class="App\Modules\Inventory\Stock\Database\Seeders\StockAccessSeeder"
php artisan test --filter=Stock
```

StockDemoSeeder hanya local/testing dan membutuhkan demo Warehouses, Units,
Items yang aktif lebih dahulu. Jalankan eksplisit pada database demo/disposable,
bukan sebagai bagian otomatis deployment. Pasangan berhistori tidak ditambah
lagi; marker demo existing termasuk terhapus tidak ditimpa.

Ledger/layer posted tidak menggunakan soft delete. Draft bisa soft delete dan
restore. Koreksi posted memerlukan capability reversal/adjustment terpisah.
Pengeluaran internal melalui IssueStock sudah menyimpan saldo/movement keluar,
consumption FIFO, snapshot dan audit secara atomic. Tidak ada route pengeluaran
bebas; consumer PostInvoice POS memakai SaleStockWriter/DTOs sejak Increment 4.
Referensi source dan line opaque ULID milik penjualan, bukan FK circular POS.

Pekerjaan lanjutan [checkout lunas](../../../work-items/pos-paid-checkout/README.md):
Domain FifoAllocation dipakai action internal IssueStock. MySQL disposable
menguji current read dan worker simultan berebut stok terakhir. Ledger memakai
quantity/nilai signed; issue line dan alokasi tetap positif. Local migration
additive sudah diterapkan tanpa pengeluaran demo, grant atau reset data.

Lihat [specification.md](specification.md), [plan.md](plan.md), dan
[tasks.md](tasks.md) untuk aturan dan hasil implementasi aktual.

[Penerimaan pembelian penuh](../../../work-items/purchase-full-receipt/README.md)
menambah receipt ledger immutable dan saldo tanpa anchor opening wajib. Saldo
purchase-only tampil bersama legacy melalui snapshot fallback. MySQL batch16
aktif lokal tanpa seed/posting user; receipt dan sale diserialisasi per gudang.
Duplicate post/dua urutan receipt-versus-sale terbukti lewat worker MySQL nyata.

[Posting retur pembelian](../../../work-items/purchase-return-posting/README.md)
menambah issue `purchase_return`, movement negatif, konsumsi FIFO dan audit
atomik tanpa mutation pada pembelian/receipt asal atau Finance. Validasi saldo
seluruh layer dilakukan sebelum alokasi hanya pada layer sumber.
