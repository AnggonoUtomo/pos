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
| Platform | CompanySettings | Identitas toko singleton/version/audit, snapshot struk melalui CompanyProfile | Identity, Laravel/Spatie | Active |
| Platform | OperationalSettings | Format struk A4/58/80, preferensi backup opsional/jam WIB, backup manual berizin/audited dan status terpisah, retry/lease | Identity, pos:backup existing (folder root backups dan lock bersama), Laravel Scheduler; pemicu OS disiapkan admin | Active |
| Inventory | Units | Master satuan dan UI CRUD; base unit/konversi dimiliki Items; guard satuan terpakai | Items (ItemUnitUsage read contract), Laravel Eloquent, Spatie Permission, Spatie Activitylog | Active |
| Inventory | Transfers | Draft/version/lifecycle dan posting penuh atomik antar-gudang, FIFO provenance, permission/audit/UI | Warehouses, Items, Units, Stock (TransferStockWriter) | Active |
| Inventory | Items | Barang, SKU, barcode, base unit immutable, multi satuan/konversi, form matrix harga dan lookup stok/pricing; kategori/brand ditunda | Units (UnitLookup), Stock (StockUsage), Parties (ItemPriceCatalog), Laravel Eloquent, Spatie Permission, Spatie Activitylog | Active |
| Inventory | Warehouses | Master gudang, UI CRUD dan pilihan gudang untuk stok awal/POS | Stock (StockUsage), Laravel Eloquent, Spatie Permission, Spatie Activitylog | Active |
| Inventory | Stock | Stok awal, receipt/issue/retur/transfer, koreksi fisik audited, saldo/kartu stok, FIFO/no-negative, report reader | Items, Warehouses, Units | Active |
| Sales | Parties | CRUD pelanggan/level, harga barang per satuan/level melalui workflow Items, audit dan demo | Items (ItemPricingLookup), Laravel auth, Spatie Permission, Spatie Activitylog | Active |
| Sales | POS | Checkout lunas/multi-payment/FIFO, keranjang tertahan, diskon/pajak exact2, recovery/struk/riwayat, shift laci eksklusif dan review selisih | Parties, Items, Warehouses, Stock, Payments (CashDrawerPosition, SalePaymentWriter/Methods), CompanySettings (CompanyProfile), OperationalSettings (PrintingPreferences via Inertia); ActiveShiftLookup dikonsumsi Identity | Active |
| Sales | Returns | Draft snapshot/multi-satuan/version/audit/demo; posting layak jual/FIFO biaya asal; quote/refund exact2/nol scope invoice atomic/replay, UI konfirmasi/recovery dan hasil readonly; schema refund lokal Ran batch22, belum karantina/tukar | POS (ReturnInvoiceLookup), Stock (SaleReturnStockWriter), Finance (SaleRefundPaymentWriter/Methods), Laravel auth, Spatie Permission, Spatie Activitylog | Active |
| Purchasing | Suppliers | CRUD master supplier/kontak/status, soft delete/restore, permission/audit atomic, demo CCTV/kelistrikan; SupplierLookup redacted dikonsumsi draft pembelian | Laravel auth, Eloquent, Spatie Permission, Spatie Activitylog | Active |
| Purchasing | PurchaseOrders | Draft multi-satuan/harga positif, snapshot exact, CRUD/version/audit/demo; penerimaan penuh dan pembayaran lunas atomic/replay/readonly; belum parsial/utang | Suppliers (SupplierLookup), Items (ItemStockLookup), Units (UnitLookup), Warehouses (WarehouseLookup), Stock (PurchaseStockWriter), Payments (PurchasePaymentWriter, PurchasePaymentMethods) | Active |
| Purchasing | Returns | Draft/lifecycle, posting dari layer receipt asal, quote dan penerimaan uang supplier pembelian lunas exact6 dengan residual/nol/replay/audit, UI readonly/recovery | PurchaseOrders (ReturnPurchaseLookup/refundBasis), Stock (PurchaseReturnStockWriter), Finance (PurchaseRefundReceiptWriter/Methods) | Active |
| Finance | Payments | Master kas/bank/metode, penerimaan penjualan exact2, pengeluaran pembelian exact6, refund penjualan exact2 dan receipt supplier exact6; snapshot/replay/guard/audit dan history readonly empat sumber; Buku Kas saldo awal/cutover per akun, mutasi/transfer/pembalik dan saldo berjalan exact6; belum piutang/utang | Laravel auth, Eloquent, Spatie Permission, Spatie Activitylog | Active |
| Reporting | OperationalReports | Ringkasan penjualan/pembelian/retur, stok terkini, arus pembayaran dan dashboard nyata readonly berizin | Public report readers POS, PurchaseOrders, kedua Returns, Stock, PaymentHistoryReader, InvoiceHistoryReader (scope kasir) | Active |

## Aturan Penambahan Module

- Tambahkan module hanya bila ada ownership bisnis atau teknis yang jelas.
- Catat dependency nyata, bukan dependency hipotetis.
- Module yang memiliki mutation penting harus mempertimbangkan permission,
  activity log, demo seeder, dan QA automated sejak awal.
- Module baru dibuat melalui `php artisan module:make {Domain} {Module}` dengan
  dry-run bila struktur belum pasti.
