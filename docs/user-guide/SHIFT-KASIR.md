# Shift Kasir

Shift adalah sesi kerja satu kasir pada satu laci kas. Semua pembayaran POS
baru, termasuk pembayaran sepenuhnya non-tunai, harus mempunyai shift aktif.
Panduan ini berlaku untuk tampilan POS Klasik dan Visual.

## 1. Persiapan Admin / Keuangan

1. Buat akun jenis **Kas** dan metode **Tunai** yang mengarah ke akun tersebut.
2. Tetapkan saldo awal dan tanggal mulai akun di **Buku Kas**. Masukkan posisi
   uang yang benar, bukan nilai contoh. Modal shift tidak diisi otomatis.
3. Bila perlu tambahan uang kembalian, catat transfer Buku Kas ke laci sebelum
   shift dibuka. Jangan membuat pemasukan baru hanya untuk membuka shift.
4. Jika ada beberapa kasir bersamaan, siapkan akun laci dan metode Tunai yang
   berbeda untuk masing-masing kasir.

Kasir menggunakan izin posting POS yang sudah ada. Supervisor membutuhkan
`sales.pos.shifts.view` untuk melihat semua shift dan
`sales.pos.shifts.review` untuk memeriksa selisih. Admin mengatur izin tersebut
di Identitas & Akses; penambahan fitur tidak mengganti role akun existing.

## 2. Buka Shift

1. Masuk memakai akun kasir sendiri lalu buka **POS**.
2. Klik **Buka shift**, pilih **Laci kas**, dan periksa modal dari Buku Kas.
3. Klik **Mulai shift**. Status berubah menjadi **Shift aktif**.
4. Lanjutkan scan barang, pilih satuan/pelanggan, periksa keranjang, dan bayar
   seperti biasa. Metode Tunai yang tersedia mengikuti laci shift. Debit dan
   Transfer tetap masuk ke akun bank masing-masing.

Satu kasir dan satu laci hanya boleh mempunyai satu shift aktif. Shift tidak
mereservasi stok. Buka/tutup melalui dialog tidak mengosongkan keranjang saat
ini. Reload browser bukan penyimpanan keranjang; gunakan **Tahan** jika perlu.

## 3. Tutup Dan Hitung Uang

1. Pastikan pembayaran yang sedang diproses sudah terkonfirmasi.
2. Klik **Shift aktif**. Periksa modal, penjualan tunai bersih, non-tunai,
   dan saldo seharusnya.
3. Hitung uang di laci, lalu isi **Uang fisik terhitung** dengan format
   Indonesia, misalnya `500.000` atau `499.999,5`.
4. Jika uang fisik berbeda dari saldo seharusnya, isi **Alasan selisih**.
5. Klik **Tutup shift**. Kasir/laci dilepas dan boleh dipakai shift berikutnya.

Saldo seharusnya mengikuti seluruh pergerakan akun laci di Buku Kas: termasuk
penjualan bersih setelah kembalian, refund tunai, biaya operasional dan
transfer. Penjualan non-tunai tidak menambah uang laci. Saldo diperiksa ulang
saat penutupan; angka pada dialog sebelumnya merupakan posisi sementara.

Selisih = uang fisik dikurangi saldo seharusnya. Nilai negatif berarti kurang,
nilai positif berarti lebih. Shift berselisih tetap ditutup dengan status
**Menunggu review**. **Tidak ada koreksi Buku Kas otomatis.** Saldo minus Buku
Kas tetap menjadi peringatan, bukan pemblokiran pembayaran.

## 4. Review Supervisor

1. Buka menu **Shift Kasir**. Kasir melihat miliknya; pemegang izin lihat semua
   dapat melihat seluruh kasir.
2. Buka detail shift **Menunggu review**. Cocokkan hitungan, alasan dan mutasi
   Buku Kas. Ringkasan mutasi kas lainnya memisahkan arus di luar penjualan.
3. Isi **Catatan pemeriksaan**, lalu klik **Sahkan review**.
4. Bila perlu koreksi uang, lakukan mutasi Buku Kas audited setelah diperiksa.
   Review sendiri tidak membuat mutasi atau mengubah hitungan kasir.

Hasil tutup tidak dapat diedit, dibuka kembali, atau dihapus. Mutasi/backdate
yang dibuat kemudian tidak menulis ulang snapshot shift lama. Tidak tersedia
force-close atau pengalihan kasir pada tahap ini. Akun kasir dengan shift aktif
tidak boleh dihapus/dinonaktifkan; tutup shift terlebih dahulu.

## 5. Jika Jaringan Bermasalah

- Jika buka/tutup belum terkonfirmasi, gunakan **Periksa ulang**. Browser
  menyimpan permintaan yang sama pada tab/sesi user tersebut, termasuk setelah
  reload. Jangan menebak hasil atau membuat shift pengganti.
- Retry penjualan yang sudah berhasil tetap menampilkan invoice yang sama
  walaupun shift telah ditutup. Penjualan baru tetap memerlukan shift aktif.
- Jika penyimpanan pemulihan browser tidak tersedia, operasi shift dikunci
  dan menampilkan pesan; hubungi admin.

Panduan ini merupakan tambahan Markdown; PDF global/per-role versi sebelumnya
belum mencakup Shift Kasir.
