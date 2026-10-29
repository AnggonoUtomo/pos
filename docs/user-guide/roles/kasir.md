# Panduan Role: Kasir

**Tujuan:** menjual barang sampai lunas dan memberikan bukti yang benar.
Usulan role `cashier-operational`; bukan jaminan role demo `cashier` sudah
lengkap. Kasir perlu izin membuka POS **dan** posting penjualan. Panduan ini
tidak mengubah akses akun.

## Tahap 1: Sebelum Melayani

1. Login memakai akun pribadi, bukan akun pemilik atau kasir lain.
2. Buka **POS**, pastikan gudang jual yang benar.
3. Pastikan pelanggan/level, barang, harga dan metode bayar siap.
4. Jika harga/stok tidak benar, hubungi admin master/gudang; jangan mengubah
   konversi atau membuat stok awal agar transaksi lolos.

Sebelum checkout, klik **Buka shift**, pilih laci dan periksa modal dari Buku
Kas. Rincian persiapan/pemulihan: [Shift Kasir](../SHIFT-KASIR.md).
Gunakan dropdown Shortcut sesuai
halaman bila perlu; `Ctrl+K`/`Cmd+K` pada top nav admin untuk mencari menu.

## Tahap 2: Isi Dan Periksa Keranjang

1. Pilih gudang dan pelanggan yang benar sebelum memeriksa harga.
2. Cari/scan barang, cocokkan nama dan satuan. Barcode kemasan jangan dianggap
   selalu satu Unit.
3. Isi jumlah; `1 Box` dengan isi 4 berarti stok berkurang 4 Unit saat berhasil.
4. Periksa harga yang mengikuti level pelanggan. Harga bukan input bebas.
5. Masukkan diskon nota persen/nominal atau pajak hanya sesuai kebijakan toko.
6. Klik **Periksa keranjang**, baca dan perbaiki pesan validasi.

**Perhatikan:** pemeriksaan keranjang belum penjualan berhasil, belum menerima
uang dan belum memesan stok. Perubahan isi setelah diperiksa perlu validasi lagi.

## Tahap 3: Terima Pembayaran Dan Posting

1. Sampaikan total tagihan, lalu buka **Pembayaran**.
2. Pilih metode aktif dan isi nominal yang benar-benar diterima/dikonfirmasi.
3. Bila gabungan metode, periksa total serta akun masing-masing.
4. Klik **Bayar dan posting** sekali, tunggu hasil.
5. Pastikan nomor dan bukti penjualan muncul sebelum menyerahkan barang/bukti.

Contoh tagihan Rp100.000: Transfer Rp60.000 + Tunai Rp50.000. Kembalian
Rp10.000; uang bersih transaksi Rp100.000. Maksimal satu baris Tunai;
Debit/Transfer tidak boleh menghasilkan lebih bayar/kembalian. Tidak ada
penjualan tempo atau pembayaran sebagian pada alur ini.

Jika jaringan terputus, **jangan langsung membuat penjualan baru atau menerima
uang dua kali**. Gunakan pemeriksaan hasil/pemulihan yang ditampilkan dan
hubungi supervisor bila belum pasti. Catat waktu serta nomor bila tersedia.

## Tahap 4: Cetak Dan Pelanggan Berikutnya

1. Cetak dari bukti penjualan menggunakan dialog browser.
2. Pilih printer dan ukuran kertas yang disiapkan administrator.
3. Jika cetak batal/gagal, buka **Riwayat Penjualan** dan cetak ulang transaksi
   yang sama; jangan mengulang penjualan.
4. Pastikan layar bukti selesai sebelum mulai pelanggan berikutnya.

Kasir tanpa izin melihat semua invoice hanya melihat transaksi miliknya.
Jangan memakai akun petugas lain untuk mencari transaksi mereka.

## Tahap 5: Keluhan Dan Akhir Hari

- Barang kembali: catat invoice, barang, satuan dan jumlah, serahkan kepada
  petugas retur/supervisor. Jangan langsung mengambil uang kas untuk refund.
- Harga salah setelah posted: catat nomor, jangan mengedit transaksi lama.
- Stok kurang: pastikan gudang benar; minta perpindahan resmi bila ada di lokasi
  lain. Stok tidak boleh minus.
- Akhir hari: pastikan pembayaran terkonfirmasi, klik **Shift aktif**, hitung
  uang fisik dan tutup shift. Jika selisih, isi alasan; tunggu review supervisor
  tanpa mengubah Buku Kas sendiri. Setelah selesai, logout.

## Checklist Setiap Penjualan

- [ ] Gudang, pelanggan/level, barang, satuan dan jumlah benar.
- [ ] Harga, diskon/pajak dan total sudah diperiksa.
- [ ] Pembayaran terkonfirmasi, nominal dan kembalian cocok.
- [ ] Nomor/bukti penjualan tampil, tidak ada hasil posting yang belum pasti.
- [ ] Bukti diberikan/dicetak tanpa menggandakan transaksi.

Rincian: [modul POS dan riwayat](../02-panduan-modul.md).
Alur toko: [panduan global](../01-alur-global.md).
