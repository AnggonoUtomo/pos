# Inventory/Stock

## Status

In Progress. Draft/posting, saldo/kartu stok dan fondasi layer FIFO sudah
diimplementasikan. Penutupan Increment 6 menunggu gate final dan
[perbaikan dependency terpisah](../../../work-items/dependency-security-patch/README.md).

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

Lihat [specification.md](specification.md), [plan.md](plan.md), dan
[tasks.md](tasks.md) untuk aturan dan hasil implementasi aktual.
