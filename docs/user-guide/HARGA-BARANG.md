# Harga Beli, Markup Dan Harga Jual

## 1. Siapkan Level Harga Sesuai Toko

Buka Level Pelanggan. Tambah atau ubah kode/nama sesuai kebijakan, misalnya
Reguler, Grosir, Member atau Reseller. Nama dan jumlah level dinamis, tidak ada
level Installer yang wajib. Level demo CCTV boleh tetap digunakan sesuai usaha.

Level yang sudah dirujuk pelanggan/harga tidak boleh dihapus atau dinonaktifkan
sembarangan. Mengubah nama tidak mengubah ID atau harga transaksi lama. Setelah
menambah level aktif, lengkapi harga seluruh satuan jual barang sebelum pelanggan
level itu bertransaksi. Pengembangan ini tidak menghapus level/harga existing.

## 2. Bedakan Harga Beli Dan Harga Jual

- Harga beli diisi pada Pembelian sesuai satuan pilihan. Satu dus isi12 dengan
  harga Rp120.000/dus berarti biaya Rp10.000 per satuan dasar.
- Penerimaan pembelian yang sudah diposting membuat persediaan dan layer FIFO.
- Harga jual tetap disimpan dalam matriks Barang: satuan jual x level aktif.
- Biaya acuan kalkulator berasal dari pembelian terakhir yang benar-benar sudah
  diterima di seluruh gudang, bukan draft atau sekadar stok awal. Pelunasan
  pembelian bukan syarat biaya acuan. Nomor dan waktu sumber ditampilkan.

## 3. Gunakan Persen Sebagai Bantuan Hitung

1. Buka Barang, cari barang lalu Edit.
2. Pada Markup dari biaya beli (%), isi persen pada level yang ingin dihitung.
   Contoh `20` berarti tambahan20% dari biaya. `12,5` berarti tambahan12,5%.
   Kosong melewati level tersebut; `0` berarti tanpa tambahan.
3. Pilih Hitung usulan (Alt+G). Periksa biaya sumber serta harga lama/usulan
   untuk masing-masing satuan jual. Konversi mengikuti isian satuan pada form.
4. Pilih Terapkan ke form (Alt+A). Ini baru mengisi form, belum menyimpan.
5. Sesuaikan harga bila diperlukan, kemudian Simpan barang (Ctrl/Cmd+Enter).

Contoh biayaRp10.000/unit dengan markup20% menghasilkanRp12.000/unit dan
Rp144.000/dus isi12. Harga dus masih bisa disesuaikan setelah penerapan.
Hasil dibulatkan HALF_UP ke dua desimal rupiah pada akhir perhitungan, bukan
membulatkan biaya satuan dasar terlebih dahulu. Nilai akhir wajib positif.

Jika belum ada pembelian diterima (termasuk barang baru), harga diisi manual.
Jangan menganggap biaya yang belum ada sebagai nol. Biaya kosong tidak dipalsukan
dari harga jual, stok awal atau rata-rata persediaan.

## 4. Periksa Sebelum Menyimpan

Mengubah persen atau satuan membatalkan usulan sebelumnya: hitung lagi sebelum
menerapkan. Error tidak mengubah harga. Membatalkan/menutup dialog membuang
usulan dan perubahan form yang belum disimpan. Persen tidak disimpan sebagai
aturan harga permanen; harga nominal final yang disimpan dan diaudit.

Markup20% berbeda dari margin20%: biayaRp100.000 + markup20% =Rp120.000,
sedangkan margin20% memerlukanRp125.000. Fitur ini menghitung markup, bukan
target margin/laba bersih dan belum memperhitungkan diskon atau biaya usaha.

## 5. Hubungannya Dengan POS

POS memakai harga tersimpan sesuai pelanggan, level dan satuan. HPP realisasi
tetap dihitung FIFO per barang/gudang. Pembelian berikutnya tidak otomatis
mengubah harga jual. Harga yang sudah tersimpan tidak mengubah snapshot nota lama.

Hanya pengelola Barang (`inventory.manage`) dapat meminta biaya/usulan. Pengguna
yang hanya melihat Barang atau kasir tidak mendapat akses endpoint biaya ini.
Menghitung harga tidak memposting transaksi stok, pembelian, pembayaran atau audit.
