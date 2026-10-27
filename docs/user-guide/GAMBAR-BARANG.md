# Gambar Dan Barcode Barang

Panduan tambahan untuk admin master dan kasir. Satu barang boleh memiliki satu
gambar utama; barang tanpa gambar tetap dapat digunakan.

## Admin Master: Memasang Gambar

1. Buka **Barang**, lalu **Tambah barang** atau ikon **Edit**.
2. Lengkapi data, satuan dan harga seperti biasa.
3. Pada **Gambar utama**, pilih file JPG, PNG atau WebP. Maksimal 2 MB dan
   4096 x 4096 piksel. Gambar pilihan muncul sebagai preview.
4. Klik **Simpan**. Periksa thumbnail pada daftar barang.

Untuk mengganti gambar, edit barang dan pilih file baru, lalu Simpan. Untuk
menghapus gambar, klik ikon tempat sampah pada bagian gambar lalu Simpan.
Ikon panah balik membatalkan perubahan gambar sebelum disimpan. Tombol **Batal**
pada form tidak menyimpan perubahan. Jika simpan gagal, perbaiki isian; gambar
lama tidak diganti/hilang hanya karena percobaan simpan gagal.

## Admin Master: Barcode Per Satuan

1. Pada **Satuan & barcode**, isi barcode pada satuan yang sesuai.
2. Barcode unit dan dus harus berbeda. Contoh satu dus berisi empat kamera:
   barcode dus harus menunjuk satuan Dus dengan isi satuan dasar 4, bukan 1.
3. Isi harga setiap satuan jual dan setiap level pelanggan, lalu Simpan.

Jangan menggunakan barcode yang sama untuk dua satuan atau dua barang. Barcode
pada data terhapus juga tidak dapat dipakai ulang. Foto tidak menggantikan SKU,
barcode, satuan atau nomor serial perangkat.

## Kasir: Mengenali Dan Memindai Barang

1. Pilih gudang serta pelanggan pada POS.
2. Cari nama/SKU atau pindai barcode melalui kolom **Cari barang**.
3. Periksa foto, nama, satuan dan harga. Scanner keyboard yang mengirim Enter
   memakai alur barcode existing; scanner kamera belum ditambahkan.
4. Periksa keranjang dan lanjutkan pembayaran seperti biasa.

Gambar berasal dari master barang saat ini, bukan snapshot foto invoice lama.
Jika gambar tidak tersedia, ikon pengganti muncul tanpa menghalangi penjualan.

## Administrator: Penyimpanan Dan Backup

Gambar tersimpan di `storage/app/private/item-images/`. Hanya pengguna dengan
izin melihat inventory atau menggunakan POS yang dapat membuka URL gambarnya.
Menghapus lunak barang tidak membuang foto; saat barang dipulihkan fotonya tetap.

Backup SQL melalui **Backup sekarang** menyimpan database, bukan binary gambar.
Untuk pemulihan lengkap, salin juga direktori gambar privat beserta backup
database yang sesuai. Jangan meletakkan gambar privat di folder unduhan publik.

Panduan tambahan ini belum digabungkan ke PDF global versi sebelumnya.
