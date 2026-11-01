# Stok Minimum Per Gudang

Untuk petugas persediaan dan supervisor. Hak melihat dan mengelola pengaturan
terpisah; admin memberikan permission melalui Identitas & Akses bila diperlukan.
Tidak ada perubahan role atau akun otomatis saat fitur diaktifkan.

## 1. Tentukan Batas

1. Buka Inventori > Stok minimum, lalu Tambah batas.
2. Pilih gudang dan cari barang memakai SKU atau nama.
3. Isi minimum dalam satuan dasar yang tampil, bukan dus/rol. Contoh kabel
   NYM bersatuan dasar meter: isi `25,5` untuk dua puluh lima setengah meter.
4. Centang Peringatan aktif lalu Simpan.

Satu pasangan barang/gudang hanya mempunyai satu pengaturan. Minimum `0`
boleh dipakai untuk memantau barang habis. Barang yang belum diatur tidak
dianggap stok rendah. Tidak ada angka minimum bawaan yang dipasang pada toko.

## 2. Periksa Setiap Hari

1. Buka Lihat peringatan pada dasbor atau filter Perlu diperiksa pada daftar.
2. Saldo sama dengan atau di bawah minimum menghasilkan peringatan.
3. Bandingkan Saldo, Minimum dan satuannya. Selisih ke batas adalah selisih
   menuju minimum, bukan rekomendasi jumlah pembelian; pada saldo tepat minimum
   selisihnya nol walaupun tetap diperingatkan.
4. Pilih gudang/status, cari SKU/nama, dan atur jumlah baris bila daftar panjang.

Saldo dibaca dari persediaan nyata. Barang tanpa saldo dihitung nol.
Penjualan, penerimaan, transfer, retur dan koreksi yang sudah diposting akan
mengubah hasil peringatan pada pembukaan/refresh berikutnya. Dasbor tidak
melakukan polling otomatis; gunakan Muat ulang ringkasan untuk memperbarui.
Ringkasan menghitung pasangan barang/gudang, bukan menjumlahkan unit berbeda.

## 3. Ubah Atau Hentikan Peringatan

- Pilih pensil untuk mengubah minimum atau melepas Peringatan aktif.
- Pasangan barang/gudang tidak diganti dari form edit; buat pengaturan terpisah.
- Hapus bila tidak dipakai. Untuk mengembalikan, pilih Terhapus lalu Pulihkan.
- Master barang/gudang/satuan nonaktif atau terhapus tidak menghasilkan alert.
  Aktifkan kembali master sebelum mengedit atau memulihkan pengaturan.
- Jika ada perubahan di tab lain, muat ulang sebelum menyimpan lagi.

Pengaturan dan audit tidak mengubah saldo, FIFO atau uang. Peringatan tidak
memblokir transaksi dan tidak membuat pembelian otomatis. Aturan stok tidak
boleh minus tetap dijaga oleh transaksi stok, bukan fitur ini.

F1 membuka panduan halaman; dropdown shortcut tersedia di atas workspace.

## Alur Lintas Modul

Barang/satuan dan gudang aktif -> atur minimum -> periksa dasbor -> rencanakan
pembelian/transfer sesuai keputusan toko -> posting dokumen yang sah -> periksa
saldo dan peringatan kembali. Fitur tidak memulai dokumen transaksi sendiri.
