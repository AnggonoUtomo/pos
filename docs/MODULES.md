# Daftar Module

Dokumen ini menjadi indeks ownership dan dependency. Detail module berada pada
`docs/modules/{Domain}/{Module}/`.

Status yang digunakan:

- `Planned`: disepakati sebagai arah, belum lengkap di source.
- `Active`: sudah ada source dasar atau capability berjalan.
- `Pending Validation`: sebagian ada, tetapi perlu validasi route, migration,
  seeder, test, atau UI nyata.
- `Deprecated`: masih ada untuk kompatibilitas sementara.
- `Disabled`: tidak aktif.

| Domain | Module | Tanggung jawab | Dependency utama | Status |
| --- | --- | --- | --- | --- |
| Platform | ModuleRuntime | Discovery, bootstrap, dan scaffolding module; source saat ini masih tersebar pada command/framework app | Laravel application container | Pending Validation |
| Platform | Identity | Authorization, role/katalog permission, tambah/edit/status akun internal, session guard, sync audited dan seeder akses | Laravel auth, Spatie Permission, Spatie Activitylog | Active |
| Platform | CompanySettings | Identitas perusahaan tunggal, preference operasional, format nomor | Identity | Planned |
| Inventory | Units | Master satuan dan UI CRUD; base unit/konversi dimiliki Items; guard satuan terpakai | Items (ItemUnitUsage read contract), Laravel Eloquent, Spatie Permission, Spatie Activitylog | Active |
| Inventory | Items | Barang, SKU, barcode, base unit immutable, multi satuan/konversi, form matrix harga dan lookup stok/pricing; kategori/brand ditunda | Units (UnitLookup), Stock (StockUsage), Parties (ItemPriceCatalog), Laravel Eloquent, Spatie Permission, Spatie Activitylog | Active |
| Inventory | Warehouses | Master gudang, UI CRUD dan pilihan gudang untuk stok awal/POS | Stock (StockUsage), Laravel Eloquent, Spatie Permission, Spatie Activitylog | Active |
| Inventory | Stock | Stok awal, saldo/kartu stok dan pengeluaran FIFO/no-negative dengan snapshot/alokasi; SaleStockWriter dikonsumsi posting POS | Items (ItemStockLookup), Warehouses (WarehouseLookup), Units (UnitLookup) | Active |
| Sales | Parties | CRUD pelanggan/level, harga barang per satuan/level melalui workflow Items, audit dan demo | Items (ItemPricingLookup), Laravel auth, Spatie Permission, Spatie Activitylog | Active |
| Sales | POS | Katalog/keranjang nyata, posting lunas atomic/FIFO dan UI multi-payment/recovery/receipt cetak; input diskon/pajak ditunda | Parties (CustomerSalesLookup, ItemPriceCatalog), Items (SaleItemCatalog), Warehouses (WarehouseLookup), Stock (StockAvailability, SaleStockWriter), Payments (SalePaymentWriter, SalePaymentMethods) | Active |
| Sales | Returns | Retur penjualan dan koreksi transaksi posted | POS, Stock, Finance Payments | Planned |
| Purchasing | Suppliers | Supplier master | Identity | Planned |
| Purchasing | PurchaseOrders | Pembelian, penerimaan stok, HPP awal FIFO | Suppliers, Items, Warehouses, Stock | Planned |
| Purchasing | Returns | Retur pembelian dan koreksi stok/payment | PurchaseOrders, Stock, Finance Payments | Planned |
| Finance | Payments | CRUD kas/bank/metode, penerimaan penjualan snapshot/net cash/replay dan guard histori; writer dikonsumsi POS, belum running balance atau piutang/utang | Laravel auth, Eloquent, Spatie Permission, Spatie Activitylog | Active |
| Reporting | OperationalReports | Laporan stok, penjualan, pembelian, margin, kas sederhana | Inventory, Sales, Purchasing, Finance | Planned |

## Aturan Penambahan Module

- Tambahkan module hanya bila ada ownership bisnis atau teknis yang jelas.
- Catat dependency nyata, bukan dependency hipotetis.
- Module yang memiliki mutation penting harus mempertimbangkan permission,
  activity log, demo seeder, dan QA automated sejak awal.
- Module baru dibuat melalui `php artisan module:make {Domain} {Module}` dengan
  dry-run bila struktur belum pasti.
