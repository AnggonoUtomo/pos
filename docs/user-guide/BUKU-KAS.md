# Buku Kas

Panduan Finance/Payments untuk toko CCTV dan kelistrikan, 28 Oktober 2026.
Buka **Keuangan > Buku Kas** menggunakan akun sendiri.

## 1. Siapkan Akun Dan Akses

- Kas toko, bank operasional, dan metode pembayaran tetap memakai master
  Kas dan bank/Metode pembayaran yang sudah ada.
- Administrator memberi izin sesuai tugas: `finance.cashbook.view` untuk baca,
  `finance.cashbook.open` menetapkan saldo awal, `finance.cashbook.manage`
  mencatat mutasi/transfer, `finance.cashbook.reverse` membalik mutasi manual.
- Permission baru masuk catalog, tidak otomatis ditambahkan ke role/akun
  existing. Super-system memakai bypass permission existing, bukan bypass rule.

## 2. Tetapkan Saldo Awal Satu Kali Per Akun

1. Tentukan tanggal mulai dan posisi uang sebenarnya bersama penanggung jawab.
2. Klik **Saldo awal**, pilih akun aktif yang belum diinisialisasi.
3. Isi nominal dengan format Indonesia, misalnya `1.000.000` atau `1.250,5`.
4. Pilih tanggal mulai. Nilai ini adalah posisi **sebelum** transaksi pukul
   00.00 WIB tanggal tersebut, bukan saldo penutupan hari itu.
5. Periksa nominal/tanggal/akun lalu klik **Tetapkan saldo awal**.

Contoh: mulai 1 November, saldo awal Rp500.000, penjualan bersih hari itu
Rp100.000, pengeluaran Rp25.000. Saldo tercatat menjadi Rp575.000.

Transaksi sebelum tanggal mulai tetap ada pada riwayat, tetapi tidak ikut
saldo Buku Kas. Pembayaran existing sejak tanggal mulai ikut otomatis. Jangan
memasukkan lagi pembayaran yang sudah tercatat sebagai mutasi manual.

Akun belum diinisialisasi **bukan berarti saldo nol**. Saldo awal tidak dapat
diedit/dihapus setelah tercatat. Salah nominal dapat dikoreksi dengan mutasi
audited; salah tanggal mulai perlu diperiksa administrator, bukan direset atau
ditimpa. Saldo awal bertanda minus boleh untuk mencatat posisi aktual.

## 3. Baca Saldo Dan Mutasi

1. Pilih **Akun kas/bank**, isi periode bila perlu, klik **Tampilkan**.
2. Periksa saldo sebelum periode, total masuk, total keluar, dan saldo akhir.
3. Baca daftar berurutan waktu WIB. Setiap baris memiliki saldo setelah mutasi.
4. Gunakan pagination; pada tablet tabel dapat digeser horizontal.

Penjualan masuk sebesar uang bersih setelah kembalian. Pembelian, refund
pelanggan dan uang kembali supplier masuk ke akun yang benar sesuai snapshot
pembayaran, termasuk multi-payment. Baris bukan total nota jika nota terbagi
ke beberapa metode/akun. Transfer internal bukan pendapatan atau biaya.

Angka disimpan presisi hingga enam desimal mengikuti pembelian existing;
angka nol di belakang tidak ditampilkan. Saldo Buku Kas bukan laba bersih,
bukan saldo bank yang diambil langsung dari bank, dan bukan bukti uang fisik
sudah dihitung. Saldo minus diberi peringatan tetapi tidak memblokir transaksi.

## 4. Catat Pemasukan Atau Pengeluaran Operasional

1. Klik **Catat mutasi**, pilih jenis pemasukan atau pengeluaran operasional.
2. Pilih akun aktif yang sudah mempunyai saldo awal.
3. Isi nominal positif, tanggal sejak awal Buku Kas sampai hari ini WIB, dan
   keterangan. Contoh: biaya transport pemasangan atau pembelian alat kebersihan.
4. Periksa lalu klik **Catat mutasi**. Mutasi langsung tercatat, bukan draft.

Untuk pembayaran penjualan, pembelian dan retur, tetap gunakan modul asal.
Jangan mencatat ulang di sini agar uang tidak dihitung dua kali.

## 5. Transfer Antar-Akun

Pilih **Transfer antar-akun**, akun asal dan tujuan yang berbeda, nominal,
tanggal serta keterangan. Kedua akun wajib aktif dan sudah diinisialisasi.
Satu pencatatan menghasilkan keluar di asal dan masuk di tujuan secara
bersamaan. Ini pencatatan internal, bukan instruksi transfer bank sebenarnya.

## 6. Koreksi Dengan Pembalik

Pada baris mutasi manual, klik ikon **Balik mutasi**, isi alasan dan konfirmasi.
Baris asal tetap ada; baris lawan dibuat pada waktu pembalikan. Transfer
dibalik pada kedua akun sekaligus. Satu mutasi hanya dapat dibalik satu kali;
baris pembalik tidak dapat dibalik lagi. Buat mutasi baru jika diperlukan.

Pembayaran dari modul lain tidak memiliki tombol pembalik di Buku Kas.
Koreksinya tetap lewat alur retur/reversal pemilik transaksi tersebut.

## 7. Bila Koneksi Terputus

Jika status belum terkonfirmasi, jangan membuat mutasi baru yang sama.
Tutup dialog dan gunakan **Ulangi pengiriman yang sama**. Pengiriman pending
disimpan per akun pengguna dan per percobaan di browser; reload tetap dapat
memulihkan. Retry memakai kunci yang sama sehingga hasil yang sudah tercatat
tidak digandakan. Jangan membersihkan penyimpanan browser saat status pending.
Bila penyimpanan tidak tersedia atau akses ditolak saat pemulihan, hubungi
administrator dan periksa mutasi sebelum mencoba dari browser lain.

Dropdown **Shortcut** mencantumkan operasi halaman. Panduan tambahan ini belum
digabungkan ke PDF global/per-role versi sebelumnya. Latihan dilakukan pada
database pelatihan, bukan dengan memasukkan saldo rekaan ke database toko.
