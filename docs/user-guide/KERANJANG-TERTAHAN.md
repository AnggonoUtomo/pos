# Keranjang Tertahan Kasir

Panduan tambahan untuk kasir, 28 Oktober 2026. Gunakan ketika pelanggan belum
selesai memilih barang, supaya kasir bisa melayani pelanggan berikutnya.

## Sebelum Mulai

- Login dengan akun sendiri, buka POS, pilih gudang dan pelanggan yang benar.
- Akun perlu izin membuat transaksi POS. Membayar tetap perlu izin posting.
- Keranjang tertahan hanya terlihat oleh akun pembuat, tidak dapat dialihkan
  antar-kasir. Jangan memakai akun bersama untuk beberapa kasir.
- Tahan bukan penjualan dan bukan pemesanan stok. Barang tetap dapat dijual
  kasir lain; harga juga dapat berubah sebelum pelanggan kembali.

## 1. Tahan Keranjang

1. Masukkan barang, satuan dan jumlah. Atur diskon/pajak bila diperlukan.
2. Klik **Tahan** atau tekan **F6**.
3. Isi label agar mudah dikenali, misalnya "Pelanggan meja 2". Label boleh kosong.
4. Klik **Simpan tertahan**, tunggu berhasil. Keranjang aktif menjadi kosong.
5. Lanjutkan melayani pelanggan lain. Draft tetap tersimpan meski halaman direload.

Gudang, pelanggan, barang, satuan, jumlah, diskon dan pajak disimpan. Tidak ada
pengurangan stok atau uang masuk. Jika simpan belum terkonfirmasi, gunakan
**Ulangi simpan**; jangan membuat ulang keranjang yang sama dari tab lain.

## 2. Panggil Dan Periksa

1. Klik **Tertahan** atau tekan **F7**.
2. Temukan label/pelanggan, klik ikon **Panggil keranjang**.
3. Jika keranjang aktif masih berisi barang, aplikasi meminta konfirmasi.
   **Kembali** mempertahankan isian aktif; **Panggil keranjang** menggantinya.
   Tahan dahulu isian aktif jika masih diperlukan.
4. Periksa satuan, jumlah, gudang, pelanggan dan total harga terkini.
5. Jika ada pesan harga/stok/barang tidak tersedia, perbaiki baris terkait.
   Baris bermasalah tidak dihapus otomatis. Klik **Periksa keranjang** sesudah edit.

Tidak ada janji harga lama. Jika master pelanggan/gudang nonaktif, pilih yang
aktif sesuai instruksi supervisor. Jika perlu menunda lagi, klik **Tahan**;
aplikasi memperbarui draft yang sama, bukan menambah draft baru.

## 3. Bayar

1. Setelah keranjang valid, klik **Pembayaran** atau tekan **F8**.
2. Pilih metode dan isi nominal seperti penjualan biasa, lalu **Bayar dan posting**.
3. Tunggu bukti penjualan. Draft yang sudah dibayar hilang dari daftar tertahan.

Satu draft tidak dapat dibayar dua kali meskipun terbuka pada dua tab. Jika tab
kedua menyatakan draft berubah/batal/sudah dibayar, muat daftar dan periksa
bukti transaksi; jangan menyalin isinya menjadi penjualan baru tanpa pemeriksaan.
Jika status pembayaran belum pasti, gunakan **Ulangi pembayaran**. Setelah
reload, aplikasi memulihkan percobaan yang sama agar tidak membuat invoice kedua.

## 4. Batalkan

1. Buka **Tertahan**, klik ikon **Batalkan keranjang** pada draft yang benar.
2. Konfirmasi **Batalkan keranjang**. Draft tidak dapat dipanggil lagi.
3. Jika draft itu sedang aktif, isian aktif ikut dikosongkan agar tidak salah bayar.

Pembatalan tidak mengubah stok/uang. Tombol **Reset** hanya membersihkan isian
workspace, bukan membatalkan draft di database. Gunakan pembatalan di daftar
untuk pelanggan yang tidak jadi bertransaksi.

## Pembagian Tugas

Kasir menahan, memanggil dan membatalkan miliknya sendiri. Supervisor membantu
memeriksa masalah harga/stok, tetapi tidak mengambil alih draft kasir lain.
Administrator mengatur akses akun; tidak perlu menambah permission baru untuk
fitur ini. Tidak ada jadwal hapus otomatis, pemindahan kasir, atau reservasi stok.

Panduan ini tidak mengesahkan pending UAT operator/printer atau backup toko.
