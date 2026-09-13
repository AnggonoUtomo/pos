# Project POS Modular ERP-Lite

## Status

Draft aktif. Project masih pada fase baseline arsitektur, dokumentasi, dan
fondasi module. Source Laravel 12 sudah tersedia, namun capability bisnis POS
belum dianggap final sampai work item module masing-masing selesai diverifikasi.

## Masalah

Banyak operasional toko membutuhkan aplikasi POS yang tidak hanya mencatat
penjualan, tetapi juga menjaga stok multi gudang, harga grosir, multi satuan,
level pelanggan, pembelian, retur, pembayaran, pajak, diskon, dan laporan dasar
dalam satu alur yang konsisten.

## Tujuan

Membangun aplikasi point of sales berbasis Laravel 12 dan Inertia React yang
siap untuk retail dan grosir sejak awal, dengan modular monolith DDD-lite,
arsitektur hexagonal per module, kontrol stok FIFO, dan audit trail.

## Pengguna

- Owner atau manajemen toko.
- Admin ERP.
- Kasir.
- Staff gudang.
- Staff pembelian.
- Staff finance lite.

## Scope Aktif Fase 1

- Single company.
- Multi gudang.
- Pemilihan gudang saat transaksi POS.
- Master item dengan multi satuan dan konversi ke base unit.
- Harga jual berdasarkan customer level dan satuan.
- Penjualan POS fullscreen untuk desktop atau tablet.
- Admin ERP dengan layout sidebar dan responsive dasar.
- Pembelian dan penerimaan stok.
- Stok tidak boleh minus.
- FIFO per `item + warehouse`.
- Retur penjualan dan retur pembelian.
- Diskon persen dan nominal.
- Pajak transaksi.
- Multi payment pada transaksi.
- Finance Lite untuk pencatatan pembayaran dan ringkasan operasional.
- Spatie Permission untuk authorization.
- Spatie Activitylog untuk audit trail.
- Demo seeder per module bila data demo relevan.

## Di Luar Scope Fase 1

- Multi cabang.
- Full accounting atau general ledger lengkap.
- Integrasi perangkat kasir khusus seperti thermal printer dan cash drawer
  sebagai driver native.
- Mobile app native.
- Marketplace integration.
- Sinkronisasi offline-first.
- Loyalty program kompleks.

## Stack Dan Constraint

- Laravel 12, PHP 8.4, MySQL.
- Inertia React, React 19, Vite, Tailwind CSS 4.
- shadcn/ui sebagai default UI/UX.
- Modul berada di `app/Modules/{Domain}/{Module}`.
- Route, migration, dan seeder module berada di dalam module.
- Primary key tabel utama memakai ULID.
- Nama tabel, kolom, route, permission, class, namespace, DTOs, event, contract,
  dan method memakai English technical naming.
- Dokumentasi dan UI label memakai Bahasa Indonesia.
- Public registration dimatikan.

## Kriteria Keberhasilan

- [ ] User dapat login tanpa public registration.
- [ ] Role dan permission dapat mengamankan menu dan action backend.
- [ ] Admin dapat mengelola master data penting.
- [ ] Kasir dapat melakukan transaksi POS dengan memilih gudang.
- [ ] Sistem menolak transaksi yang membuat stok minus.
- [ ] Posting stok memakai FIFO per `item + warehouse`.
- [ ] Harga jual mengikuti customer level dan satuan yang dipilih.
- [ ] Retur, void/reversal, dan adjustment tercatat audited.
- [ ] Finance Lite dapat mencatat pembayaran dan ringkasan piutang/utang dasar.
- [ ] Activity log tersedia untuk operasi penting.
- [ ] QA automated dan browser QA tercatat pada work item yang relevan.

## Pertanyaan Terbuka

- Format nomor dokumen final untuk sales, purchase, return, payment, dan stock
  adjustment.
- Batas minimal report fase 1 yang harus siap sebelum go-live.
- Strategi deployment dan CI/CD.
- Pilihan final cache, queue, dan session driver produksi.
