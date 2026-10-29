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

## Dashboard

Dashboard `/dashboard` tetap dapat dibuka setiap user yang login. Ringkasan
toko hanya dikirim bila Gate mengizinkan `reporting.operational.view`.
Periode hari ini memakai Asia/Jakarta dengan batas akhir eksklusif, mengikuti
query laporan existing. Penjualan merupakan nilai invoice posted sebelum
pengurang retur, pembelian merupakan dokumen diterima (bukan hanya lunas),
arus pembayaran merupakan mutasi seluruh gudang (bukan saldo/laba).
Stok adalah posisi terkini barang + gudang, bukan jumlah barang unik.

Lima penjualan terbaru menggunakan contract publik `InvoiceHistoryReader`
Sales/POS: `sales.pos.invoices.view` melihat semua, sedangkan pemegang
`sales.pos.invoices.post` hanya miliknya. Izin laporan tidak otomatis memberi
akses daftar invoice. Backend tidak mengirim ringkasan atau invoice yang tidak
diizinkan. Super-system mengikuti Gate::before. Tidak ada indikator contoh,
persentase kapasitas gudang, tren, shift atau batas stok menipis buatan.
Refresh manual; tidak ada polling, migration, mutation atau demo seeder baru.

## Widget Visual

Dashboard dan laporan menampilkan perbandingan nilai penjualan/pembelian/
refund selesai dan donut arus pembayaran dari props ringkasan existing.
Tidak membuat data tren atau indikator pertumbuhan buatan. Filter gudang
berlaku pada komposisi transaksi; donut tetap pembayaran seluruh gudang,
bukan saldo rekening/laba. Angka Indonesia exact, rasio visual dihitung lewat
integer decimal; Number hanya untuk persentase geometri.
Animasi finite saat data/widget masuk, tooltip keyboard, empty/zero serta
prefers-reduced-motion tersedia. Ringkasan pembayaran laporan kini disajikan
oleh widget, tidak diulang pada panel teks terpisah. Detail scope dan QA:
[Tabel dan chart](../../../work-items/workspace-tables-charts/README.md).
