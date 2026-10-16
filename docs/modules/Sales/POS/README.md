# Sales/POS

Status: Active, scope data/keranjang telah diimplementasikan dan diverifikasi.
Scope awal disetujui 14 Oktober 2026: katalog/barcode,
gudang, pelanggan, harga matrix nyata dan validasi keranjang backend.

POS memiliki komposisi workspace kasir dan pemeriksaan keranjang sementara.
Barang/satuan tetap Items/Units; gudang Warehouses; pelanggan/harga Parties;
saldo dan FIFO Stock. Tidak mengambil model/repository privat module lain.

Backend posting lunas tersedia pada Increment 4: invoice/snapshot/idempotency,
pengeluaran FIFO dan penerimaan pembayaran atomic. UI pembayaran/receipt belum
diaktifkan sampai Increment 5. Tidak ada reservasi, input diskon/pajak, retur,
piutang atau closing shift. Snapshot diskon/pajak eksplisit nol.
Theme existing dipertahankan; desktop/tablet, bukan mobile.

Public input route tetap /pos (pos.index). Permission existing
sales.pos.invoices.create dipakai untuk index/catalog/quote; invoices.post
tidak memberikan akses create dengan sendirinya. Super-system bypass permission,
bukan invariant. POSAccessSeeder hanya provisioning catalog tanpa grant.

Seeder demo POS tidak membuat transaksi: memakai demo master/stock/pricing
existing secara eksplisit pada fixture disposable. Data pengguna tidak di-seed
ulang. Posted demo hanya fixture disposable; read-only tidak diaudit sebagai transaksi.

Detail: [specification](specification.md), [plan](plan.md), [tasks](tasks.md),
[work item](../../../work-items/pos-live-cart/README.md).

Tahap [checkout lunas/multi-payment](../../../work-items/pos-paid-checkout/README.md)
sudah disetujui. Increment 1..4 selesai; endpoint posting dan detail JSON memakai
permission create/post/view terpisah, tanpa memerlukan CRUD Finance pada kasir.
Schema lokal Ran batch13, permission catalog additive tanpa grant/reset dan tanpa
invoice pengguna. UI belum menyalakan pembayaran; QA rinci ada pada work item.
