# Inventory/Stock

StockAvailability adalah published read boundary POS untuk saldo base unit per
gudang. Tidak mengunci/mereservasi stok, memuat nilai FIFO, atau melakukan
pengeluaran. Consumer quote wajib memvalidasi ulang ketika posting dibuat nanti.

## Status

Active. Draft/posting, saldo/kartu stok dan fondasi layer FIFO sudah
diimplementasikan dan QA/delivery Increment 6 selesai;
[perbaikan dependency terpisah](../../../work-items/dependency-security-patch/README.md)
juga telah lulus gate.

## Tujuan Dan Boundary

Memiliki dokumen stok awal draft/posted, saldo dalam satuan dasar, movement,
dan layer FIFO per barang + gudang. Activity log bukan ledger stok.
Tidak memiliki penjualan, pembelian, transfer, adjustment, reversal UI,
payment, pajak, diskon, atau harga level pelanggan.

## Public Boundary Dan Dependency

- Items menyediakan lookup barang/satuan melalui contract dan DTOs publik.
- Warehouses menyediakan lookup gudang melalui contract dan DTOs publik.
- Units.UnitLookup memvalidasi dan mengunci satuan aktif.
- StockUsage menyediakan guard histori untuk consumer Items/Warehouses.
- Tidak membuat event atau port pengeluaran FIFO tanpa consumer transaksi.

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
consumption FIFO, snapshot dan audit secara atomic. Belum ada route pengeluaran
publik atau consumer checkout POS. Referensi source dan line opaque ULID milik
penjualan kelak; bukan FK ke model POS privat.

Pekerjaan lanjutan [checkout lunas](../../../work-items/pos-paid-checkout/README.md):
Domain FifoAllocation dipakai action internal IssueStock. MySQL disposable
menguji current read dan worker simultan berebut stok terakhir. Ledger memakai
quantity/nilai signed; issue line dan alokasi tetap positif. Local migration
additive sudah diterapkan tanpa pengeluaran demo, grant atau reset data.

Lihat [specification.md](specification.md), [plan.md](plan.md), dan
[tasks.md](tasks.md) untuk aturan dan hasil implementasi aktual.
