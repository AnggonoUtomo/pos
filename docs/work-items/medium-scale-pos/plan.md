# Plan: POS Skala Menengah

## Tahap 1: Operasional Harian

1. `item-identification`: gunakan barcode per satuan existing; tambahkan gambar
   melalui adapter Media Library di Inventory/Items. Publikasikan hanya metadata
   tampilan yang dibutuhkan POS, bukan model atau path filesystem internal.
2. `held-carts`: simpan keranjang belum posted dengan ownership kasir; harga dan
   stok diperiksa ulang saat dipanggil/checkout. Tahan bukan reservasi stok.
3. `cashbook`: sepakati awal saldo dan mutasi; bedakan saldo dengan histori
   pembayaran serta laba. Jangan membuat saldo awal rekaan dari dataset lama.
4. `cashier-shifts`: tentukan laci kas, modal, penanggung jawab, selisih dan
   hubungan refund/setoran sebelum mengubah syarat checkout.

## Tahap 2: Stok Dan Purnajual

1. `stock-counts`: mulai dari adjustment audited existing, lalu workflow sesi
   hitung dan review. Putuskan perlakuan transaksi selama sesi berjalan.
2. `minimum-stock`: ambang per barang/gudang dan peringatan; tidak otomatis
   memesan barang atau mengubah stok.
3. `serial-warranty`: tetapkan jenis barang wajib serial, awal garansi,
   penerimaan/penjualan/retur dan klaim. Serial tidak menggantikan valuasi FIFO.

## Tahap 3: Grosir Dan Proyek

1. `credit-terms`: aturan tempo, limit, jatuh tempo dan alokasi pelunasan.
2. `quotations`: aturan harga/masa berlaku, konversi dan reservasi bila diperlukan.
3. `component-bundles`: komponen nyata, jasa non-stok, harga dan retur paket.
4. `partial-receipts`: pisahkan status pesanan, penerimaan dan pembayaran;
   kuantitas sudah diterima tidak boleh diterima ulang.

Urutan di dalam tahap dapat disesuaikan berdasarkan kebutuhan toko, tetapi
dependency saldo/ledger dan integritas stok tidak boleh dilewati.

## Pola Pelaksanaan

- Buat work item capability pada ownership module atau lintas module sebelum
  coding; README, plan, tasks dengan acceptance dan verification per task.
- Gunakan module existing; generator hanya bila boundary baru benar-benar perlu.
- Permission backend, audit, shortcut, Sonner dan format Indonesia mengikuti
  baseline. Seeder baru bersifat additive/idempotent, bukan reset database toko.
- Uji alur berubah di database disposable dan desktop/tablet representatif.
- Commit otomatis setiap increment terverifikasi; push ketika work item selesai.
- Update panduan penggunaan berdasarkan fitur yang benar-benar tersedia.

## Titik Pertama Yang Menunggu Keputusan

Untuk `item-identification`, pilih satu gambar utama per barang atau galeri.
Rekomendasi awal: satu gambar utama yang dapat diganti/dihapus dan tampil pada
master serta katalog POS. Barcode tetap per satuan barang. Belum ditetapkan
barcode wajib, format kode baru, cetak label, scanner kamera, impor massal,
kategori atau merek hanya dari permintaan gambar/barcode ini.
