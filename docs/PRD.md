# PRD: POS Modular ERP-Lite

Status: Draf
Tanggal: 2026-09-12

## Ringkasan

Produk ini adalah aplikasi POS dan operasional toko/grosir berbasis web. Target fase awal adalah bisnis retail dan grosir yang membutuhkan transaksi cepat, kontrol stok multi gudang, multi satuan, harga berdasarkan level pelanggan, FIFO, retur, pajak, multi pembayaran, dan finance lite.

Referensi fungsional berasal dari kebutuhan POS/toko iPos-like yang mencakup master data, pembelian, penjualan, persediaan, hutang/piutang, laporan, dan pengaturan.

## Tujuan Produk

- Membantu kasir memproses transaksi cepat dan akurat.
- Membantu admin mengelola barang, stok, pembelian, penjualan, retur, pembayaran, dan laporan.
- Menjaga stok tidak minus dengan FIFO yang dapat diaudit.
- Mendukung grosir sejak awal melalui multi satuan dan customer-level pricing.
- Memberi laporan operasional yang cukup sebelum full accounting dibangun.

## Persona Utama

- Owner: melihat laporan penjualan, laba, stok, hutang, piutang, dan kas.
- Admin toko: mengelola master data, pembelian, stok, retur, dan pembayaran.
- Kasir: menjual barang cepat lewat layar POS fullscreen.
- Staff gudang: melakukan transfer, opname, item masuk/keluar, dan cek kartu stok.

## Scope Fase 1

Masuk scope:

- Auth internal tanpa public registration.
- Role dan permission.
- Company/settings dasar.
- Master item, satuan, konversi, kategori, merek, barcode.
- Customer, supplier, sales, customer level.
- Multi gudang.
- Customer-level pricing per item dan satuan.
- Pembelian, penjualan, retur pembelian, retur penjualan.
- POS fullscreen.
- Transfer gudang.
- Stok opname dan adjustment dasar.
- FIFO costing.
- Multi payment.
- Pajak `NON`, `INCLUDE`, `EXCLUDE`.
- Diskon persen dan nominal.
- Finance lite: kas/bank, hutang, piutang, pembayaran, refund.
- Activity log.
- Laporan operasional dasar.

Di luar scope fase 1:

- Multi cabang.
- Full accounting.
- Jurnal manual, buku besar, neraca, laba rugi akuntansi formal.
- Konsinyasi.
- Perakitan/manufacturing.
- Quantity-based price break.
- Workflow yang dioptimalkan untuk mobile phone.
- Direct thermal-printer integration jika browser print sudah cukup untuk fase awal.

## User Stories Prioritas

- Sebagai admin, saya bisa membuat item dengan multi satuan agar barang bisa dijual per pcs, pack, atau dus.
- Sebagai admin, saya bisa mengatur harga berdasarkan level pelanggan agar grosir dan retail memiliki harga berbeda.
- Sebagai kasir, saya bisa memilih gudang, scan barang, menerima multi metode pembayaran, dan mencetak struk.
- Sebagai sistem, saya menolak penjualan jika stok gudang tidak cukup.
- Sebagai owner, saya bisa melihat laba penjualan berdasarkan HPP FIFO.
- Sebagai admin, saya bisa memproses retur berdasarkan transaksi asal agar stok, pajak, dan pembayaran tetap terkontrol.

## Metrik Sukses Fase 1

- Transaksi POS dapat diproses end-to-end dari scan item sampai pembayaran.
- Penjualan stok kurang selalu ditolak.
- HPP FIFO konsisten dan dapat dilacak melalui alokasi FIFO.
- Retur tidak bisa melebihi qty transaksi asal.
- Laporan stok per gudang dan kartu stok sesuai stock movements.
- Piutang/hutang terbentuk dari transaksi partial payment.
- Activity log tersedia untuk perubahan penting.

## Risiko Produk

- FIFO, retur, dan edit transaksi dapat merusak histori jika posted transaction tidak immutable.
- Multi satuan dapat menghasilkan selisih stok jika konversi tidak disnapshot.
- Finance lite bisa melebar menjadi full accounting jika batas scope tidak dijaga.
- POS UI bisa lambat jika terlalu mengikuti pola admin table.

## Dokumen Rujukan

- `docs/SPEC.md`
- `docs/ARCHITECTURE.md`
- `docs/MODULE-COMMUNICATION.md`
- `docs/DEFINITION-OF-DONE.md`
- `docs/work-items/WORK-ITEM-REGISTRY.md`
- `docs/adr/`
