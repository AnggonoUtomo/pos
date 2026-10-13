# Sales/POS

Status: Active, scope data/keranjang telah diimplementasikan dan diverifikasi.
Scope awal disetujui 14 Oktober 2026: katalog/barcode,
gudang, pelanggan, harga matrix nyata dan validasi keranjang backend.

POS memiliki komposisi workspace kasir dan pemeriksaan keranjang sementara.
Barang/satuan tetap Items/Units; gudang Warehouses; pelanggan/harga Parties;
saldo dan FIFO Stock. Tidak mengambil model/repository privat module lain.

Tidak menyimpan penjualan, reservasi, pengeluaran FIFO, diskon/pajak/payment,
retur, struk atau closing shift pada scope ini. Tidak ada klaim pembayaran sukses.
Theme existing dipertahankan; desktop/tablet, bukan mobile.

Public input route tetap /pos (pos.index). Permission existing
sales.pos.invoices.create dipakai untuk index/catalog/quote; invoices.post
tidak memberikan akses create dengan sendirinya. Super-system bypass permission,
bukan invariant. POSAccessSeeder hanya provisioning catalog tanpa grant.

Seeder demo POS tidak membuat transaksi: memakai demo master/stock/pricing
existing secara eksplisit pada fixture disposable. Data pengguna tidak di-seed
ulang. Tidak membuat tabel baru atau audit read-only sebagai transaksi palsu.

Detail: [specification](specification.md), [plan](plan.md), [tasks](tasks.md),
[work item](../../../work-items/pos-live-cart/README.md).
