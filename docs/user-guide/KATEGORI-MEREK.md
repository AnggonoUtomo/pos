# Kategori Dan Merek Barang

Kategori adalah kelompok barang, misalnya **Kamera dan perekam CCTV** atau
**Kelistrikan dan kabel**. Merek adalah merek produknya, bukan nama supplier.
Master bisa disesuaikan dengan jenis toko; contoh bukan pilihan wajib.

## 1. Siapkan Master

1. Buka Inventori > Kategori barang atau Merek barang.
2. Klik Tambah, isi kode unik dan nama, lalu Simpan.
3. Gunakan Cari, filter status dan jumlah baris untuk menelusuri data.
4. Edit untuk memperbaiki nama/status. Hak menambah dan mengubah mengikuti
   izin kelola inventori; akun baca-saja hanya melihat data.

Kategori satu tingkat, tanpa subkategori. Kode kategori dan merek masing-masing
unik dan tetap direservasi setelah dihapus. Jangan membuat merek untuk setiap
supplier jika produk sebenarnya memakai merek yang sama.

## 2. Hubungkan Ke Barang

1. Buka Barang > Tambah/Edit.
2. Cari kode/nama pada Kategori dan Merek, lalu pilih hasilnya.
3. Setiap barang boleh memiliki satu kategori dan satu merek; keduanya opsional.
   Pilih Tanpa kategori/Tanpa merek untuk mengosongkan.
4. Periksa harga/satuan seperti biasa, lalu Simpan.

Barang lama tidak otomatis diisi klasifikasi dan tetap valid. Form tambah
barang dari draft pembelian menyediakan pilihan yang sama. Mengganti
klasifikasi tidak mengubah harga, stok, HPP/FIFO atau transaksi posted.

## 3. Cari Barang Lebih Cepat

- Di daftar Barang, gabungkan filter kategori/merek dengan pencarian SKU,
  nama atau barcode. Pilih Semua untuk melepas filter. Filter/ukuran halaman
  kembali ke halaman pertama dan pilihan lain tetap dipertahankan.
- Di katalog POS Klasik maupun Visual, pilih kategori/merek di bawah Cari
  barang. Keranjang yang sudah diisi tidak dihapus saat filter berganti.
- Scan barcode mencari langsung tanpa dibatasi filter katalog. Harga tetap
  mengikuti satuan dan level pelanggan; stok diperiksa sistem seperti biasa.

## 4. Hapus, Nonaktifkan Atau Pulihkan

Master yang belum dirujuk barang dapat dinonaktifkan atau dihapus. Data yang
terhapus dapat dipulihkan dari tab Terhapus. Jika master masih dipakai barang,
termasuk barang soft-deleted, sistem menolak hapus/nonaktif agar referensinya
tidak rusak. Nama master tetap bisa diperbaiki.

Gunakan F1 untuk panduan halaman dan dropdown Shortcut untuk operasi yang
tersedia. Panduan ini tambahan Markdown/F1; PDF global/per-role lama belum
dibangun ulang untuk fitur ini.
