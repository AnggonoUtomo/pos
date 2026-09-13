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
| Platform | Identity | Role, permission, shared auth permission, user access baseline | Laravel auth, Spatie Permission, Spatie Activitylog | Active |
| Platform | CompanySettings | Identitas perusahaan tunggal, preference operasional, format nomor | Identity | Planned |
| Inventory | Catalog | Item, kategori, brand, satuan, multi satuan, barcode | CompanySettings | Planned |
| Inventory | Warehouses | Gudang, lokasi stok, pemilihan gudang transaksi | CompanySettings | Planned |
| Inventory | Stock | Stock movement, FIFO layer, no-negative-stock invariant | Catalog, Warehouses | Planned |
| Sales | Parties | Customer, customer level, harga level pelanggan | Identity | Planned |
| Sales | POS | Transaksi POS fullscreen, cart, pricing, discount, tax, payment | Parties, Catalog, Warehouses, Stock, Finance Payments | Planned |
| Sales | Returns | Retur penjualan dan koreksi transaksi posted | POS, Stock, Finance Payments | Planned |
| Purchasing | Suppliers | Supplier master | Identity | Planned |
| Purchasing | PurchaseOrders | Pembelian, penerimaan stok, HPP awal FIFO | Suppliers, Catalog, Warehouses, Stock | Planned |
| Purchasing | Returns | Retur pembelian dan koreksi stok/payment | PurchaseOrders, Stock, Finance Payments | Planned |
| Finance | Payments | Finance Lite: kas/bank, payment method, payment record, piutang/utang dasar | Sales POS, Purchasing PurchaseOrders | Planned |
| Reporting | OperationalReports | Laporan stok, penjualan, pembelian, margin, kas sederhana | Inventory, Sales, Purchasing, Finance | Planned |

## Aturan Penambahan Module

- Tambahkan module hanya bila ada ownership bisnis atau teknis yang jelas.
- Catat dependency nyata, bukan dependency hipotetis.
- Module yang memiliki mutation penting harus mempertimbangkan permission,
  activity log, demo seeder, dan QA automated sejak awal.
- Module baru dibuat melalui `php artisan module:make {Domain} {Module}` dengan
  dry-run bila struktur belum pasti.
