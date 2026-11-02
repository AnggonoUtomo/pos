# Mencari Master Dan Menambah Data Dari Pembelian

## Mencari Pilihan

1. Klik kolom barang, supplier, gudang, satuan, level pelanggan, akun atau
   metode pembayaran yang ingin dipilih.
2. Ketik sebagian kode atau nama. Klik hasil yang sesuai; lewat keyboard,
   gunakan panah atas/bawah lalu Enter. Enter memilih, bukan menyimpan form.
3. Esc menutup daftar pilihan. Nama yang diketik tanpa memilih hasil belum
   mengganti pilihan tersimpan. Pilihan terkunci tetap tidak dapat diubah.
4. Jika hasil terlalu banyak, persempit kata pencarian. Daftar menampilkan
   maksimal 100 hasil sekaligus dan tetap dapat digulir.

Perilaku ini berlaku pada form master, stok, opname, transfer, pembelian,
retur, POS, Buku Kas, shift dan filter riwayat/laporan/log terkait. Pilihan
tetap seperti status, jenis transaksi dan jumlah baris tabel tidak berubah.
Pencarian pelanggan POS dan pembelian asal pada retur mempertahankan alur
pencarian yang sudah tersedia.

## Supplier Belum Ada Saat Membeli

1. Buka **Order pembelian > Tambah draft pembelian**.
2. Cari supplier. Jika belum ada, klik **Tambah supplier** di samping kolomnya.
3. Isi kode, nama dan kontak yang diperlukan, lalu **Simpan**. Supplier baru
   dibuat aktif dan otomatis dipilih pada draft.
4. Jika hanya ingin kembali, klik **Batal** pada form supplier. Isian draft
   pembelian tidak hilang dan supplier tidak dibuat.

Tombol ini hanya muncul bagi akun yang memiliki izin mengelola supplier.

## Barang Belum Ada Saat Membeli

1. Pada baris pembelian yang ingin diisi, klik **Tambah barang**.
2. Isi SKU, nama, satuan dasar, satuan/barcode serta seluruh harga jual per
   satuan jual dan level pelanggan aktif, seperti pada master Barang.
3. Barang baru harus aktif dan memiliki sedikitnya satu satuan beli. Gambar
   utama tetap opsional sesuai aturan master Barang.
4. Klik **Simpan**. Barang dan satuan belinya otomatis dipilih pada baris
   tersebut; jumlah, harga beli, catatan, gudang dan baris lain tidak dihapus.
5. Periksa kembali satuan, jumlah dan harga beli sebelum **Simpan draft**.

Tombol ini hanya muncul bagi akun yang memiliki izin mengelola barang.
Membuat master tidak menambah stok, menerima pembelian atau mencatat uang.

## Sebelum Melanjutkan

- Master yang berhasil dibuat tetap ada walaupun draft pembelian dibatalkan.
- Jika validasi gagal, perbaiki isian pada popup. Jangan menutup draft induk.
- Jika master sudah tersimpan tetapi pilihan gagal dimuat, coba kembali dari
  popup yang sama; sistem memuat pilihan tanpa membuat master kedua. Jika
  hasil penyimpanan tidak pasti, periksa daftar master sebelum mencoba ulang.
- Pencarian lokal mengikuti kumpulan pilihan yang disediakan halaman, bukan
  janji pencarian seluruh database tanpa batas. Sebagian lookup existing
  dibatasi 1.000 data; master baru dari popup tetap diambil melalui ID-nya.
- Uang, stok, FIFO, aturan posted dan permission backend tidak berubah.

Panduan ini adalah tambahan Markdown/F1; PDF global/per-role lama belum
dibangun ulang untuk perubahan ini.
