# Sales/POS

Status: Active, scope data/keranjang telah diimplementasikan dan diverifikasi.
Scope awal disetujui 14 Oktober 2026: katalog/barcode,
gudang, pelanggan, harga matrix nyata dan validasi keranjang backend.

POS memiliki komposisi workspace kasir dan pemeriksaan keranjang sementara.
Barang/satuan tetap Items/Units; gudang Warehouses; pelanggan/harga Parties;
saldo dan FIFO Stock. Tidak mengambil model/repository privat module lain.

Backend posting lunas tersedia pada Increment 4: invoice/snapshot/idempotency,
pengeluaran FIFO dan penerimaan pembayaran atomic. Increment 5 mengaktifkan UI
multi-payment, retry/recovery dan receipt cetak browser. Paket MVP menambah input
diskon nota persen/nominal, pajak tambahan, alokasi exact2, serta snapshot identitas
toko melalui CompanyProfile. Retur/refund memakai snapshot nilai bersih. Tidak
ada reservasi, piutang, pajak inklusif/multi-tarif, barang gratis atau closing shift.
Theme existing dipertahankan; desktop/tablet, bukan mobile.

Keranjang tertahan tersedia melalui Tahan/F6 dan Tertahan/F7. Draft disimpan
di database, hanya kasir pembuat yang boleh mengaksesnya (termasuk invariant
ownership bagi super-system). Tidak mereservasi stok atau mencatat uang.
Pemanggilan memeriksa harga/master/stok terkini; sumber ID/version disertakan
pada checkout dan diselesaikan atomik bersama invoice/FIFO/pembayaran agar dua
tab tidak membayar sumber yang sama. Tidak ada pengalihan, TTL, atau restore
draft yang dibatalkan. Detail: [work item](work-items/held-carts/README.md) dan
[panduan kasir](../../../user-guide/KERANJANG-TERTAHAN.md).

Published SalesReportReader dikonsumsi Reporting/OperationalReports untuk
ringkasan readonly. Detail MVP/QA pada [checklist](../../../work-items/mvp-ready/tasks.md)
dan ADR-019; identitas struk lama tidak ditulis ulang.

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
sudah selesai dalam scope Increment 1..6; endpoint posting dan detail JSON memakai
permission create/post/view terpisah, tanpa memerlukan CRUD Finance pada kasir.
Schema lokal Ran batch13, permission catalog additive tanpa grant/reset dan tanpa
invoice pengguna. UI /pos mengaktifkan Pembayaran setelah quote valid bagi user
dengan create+post; admin perlu memberi permission post pada role kasir yang sesuai.
QA dan delivery remote terverifikasi; bukti rinci ada pada work item.

[Riwayat penjualan](work-items/invoice-history/README.md) tersedia di
/sales/invoices: filter nomor/tanggal WIB/gudang/pelanggan, pagination,
detail snapshot dan cetak ulang. Post melihat miliknya, view melihat semua;
tanpa grant baru, edit/hapus/void/retur atau HPP. QA akhir/delivery riwayat lulus
17 Oktober2026, dicatat pada work item terpisah dari bukti checkout terdahulu.

Published boundary `ReturnInvoiceLookup` + `ReturnInvoiceData` dikonsumsi
Sales/Returns untuk draft retur: cari nomor maksimal25 dan snapshot invoice/line
minimal tanpa HPP, payment, actor, atau request hash. Source merupakan posted
immutable, tidak bergantung master aktif. Draft dimiliki Returns, tidak menambah
endpoint koreksi invoice atau mutation Stock/Finance pada POS.

Exact scan `/pos/scan` memakai create, contract SaleItemCatalog.scan dan DTO
SaleScanData. Input scanner + Enter diproses antrean terbatas tanpa debounce;
scan sama menambah qty satuan, aggregate stock lintas satuan diperiksa, dan
checkout/hold menunggu scan. Barang/rasio/harga/stok tetap current saat quote/post.
Detail [barcode scanner](../../../work-items/barcode-scanner/README.md).

Workspace menyediakan Klasik (kartu daftar) dan Visual (tile gambar), berbagi
state transaksi tanpa reload. Preferensi tampilan lokal browser; default awal
Gudang Utama dan level Eceran hanya bila unik, tidak mengalahkan recall/recovery.
Detail [work item](work-items/workspace-views/README.md) dan
[panduan tampilan](../../../user-guide/TAMPILAN-POS.md).
