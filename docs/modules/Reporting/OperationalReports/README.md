# Reporting/OperationalReports

Scope MVP readonly: penjualan, pembelian diterima, retur posted, pembayaran
masuk/keluar, dan nilai/saldo stok terkini. Periode maksimal 366 hari dan gudang
opsional; default hari ini. Filter periode berlaku pada waktu posting masing-
masing sumber. Stok adalah posisi saat ini, bukan posisi historis akhir periode.

Angka uang memakai decimal string/BigDecimal, bukan float. Laporan mengambil
published report contracts milik Sales/POS, Sales/Returns, Purchasing/
PurchaseOrders, Purchasing/Returns, Finance/Payments dan Inventory/Stock.
Tidak mengimpor model privat module sumber. Arus pembayaran memakai periode
tetapi seluruh gudang (label eksplisit), karena ledger Finance belum memiliki
segmentasi gudang. Filter gudang berlaku untuk transaksi/stok. Arus pembayaran
bukan saldo rekening.

Permission reporting.operational.view; tanpa mutation, soft delete, ekspor,
akuntansi/laba bersih. Ringkasan tidak menampilkan nomor rekening/kontak pribadi.
Tidak perlu migration tabel laporan atau demo transaksi khusus: menggunakan
data posted nyata/fixture relasional disposable. Seed hanya katalog permission.
Rencana/checklist/QA: [MVP siap toko](../../../work-items/mvp-ready/tasks.md).
