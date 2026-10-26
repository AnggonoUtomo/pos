# Alur Global: Dari Persiapan Sampai Penutupan Hari

## 1. Kenali Cara Kerja Aplikasi

Aplikasi melayani satu perusahaan dengan beberapa gudang. Satu transaksi
memakai satu gudang. Harga jual mengikuti level pelanggan dan satuan yang
dipilih. Penjualan serta alur utama pembelian diselesaikan lunas.

| Istilah | Arti Sederhana |
| --- | --- |
| Master | Data acuan, misalnya barang, gudang, pelanggan dan metode bayar. |
| Draft | Rancangan dokumen; belum mengubah stok atau mencatat pembayaran. |
| Posting | Mengonfirmasi dokumen sehingga pengaruh bisnisnya berlaku. |
| Satuan dasar | Satuan terkecil untuk menyimpan jumlah stok. |
| Konversi | Isi satu kemasan dalam satuan dasar; ditentukan per barang. |
| FIFO | Biaya stok masuk lebih dahulu dipakai lebih dahulu dalam gudang yang sama. |
| Retur | Mengembalikan barang berdasarkan transaksi asal. |
| Refund | Pengembalian uang; terpisah dari penerimaan/penyerahan barang retur. |
| Soft delete | Menghapus dari daftar aktif; master/draft tertentu bisa dipulihkan. |

**Aturan utama:** stok tidak boleh minus. Dokumen posted tidak boleh diedit
atau dihapus bebas. Koreksi mengikuti alur retur atau koreksi stok yang
sesuai; bukan mengganti angka transaksi lama.

## 2. Masuk Dan Mengenali Layar

1. Buka alamat yang diberikan administrator, pada PC toko ini `https://pos.test`.
2. Masuk menggunakan akun pribadi. Tidak ada pendaftaran akun publik.
3. Halaman awal adalah login; setelah masuk Anda menuju Dasbor.
4. Gunakan sidebar untuk administrasi atau tombol POS untuk layar kasir.
5. Gunakan pencarian menu melalui command palette, `Ctrl+K` atau `Cmd+K`.
6. Buka dropdown **Shortcut** di atas workspace untuk pintasan halaman tersebut.
7. Atur tema melalui **Tema** jika diperlukan; perubahan warna bukan perubahan data.
8. Keluar melalui menu pengguna ketika selesai atau berganti petugas.

Jika ada peringatan sertifikat, jangan melewatinya. Minta administrator
memeriksa alamat dan sertifikat. Alamat server pada tablet perlu disiapkan
administrator; mengetik pos.test saja belum menjamin perangkat dapat terhubung.

## 3. Increment A: Siapkan Toko Dan Akses

Pelaksana: administrator sistem bersama pemilik.

1. Pastikan akun aktif dan tugas setiap petugas sudah dibagi.
2. Atur identitas toko yang akan tercetak pada transaksi baru.
3. Tentukan format struk A4, 58 mm atau 80 mm di **Operasional**.
4. Pastikan akun kas/bank dan metode pembayaran yang akan digunakan benar.
5. Uji cetak melalui dialog browser. Printer dipilih melalui perangkat/driver,
   bukan memilih USB/LAN dari aplikasi.
6. Pastikan prosedur backup dan penanggung jawabnya jelas.

**Boleh lanjut jika:** petugas dapat masuk, akses sesuai tugas, identitas dan
metode bayar siap. Jangan menggunakan akun super-system untuk semua kasir.

## 4. Increment B: Siapkan Data Master

Pelaksana: admin master; data keuangan disiapkan staf keuangan berizin.

Urutan yang dianjurkan:

```text
Gudang + Satuan + Level pelanggan
              |
              v
Barang + konversi + harga lengkap
              |
              v
Pelanggan + Supplier + Kas/bank + Metode pembayaran
```

1. Buat gudang dan satuan terlebih dahulu.
2. Siapkan level pelanggan, misalnya Eceran, Grosir dan Installer.
3. Buat barang; pilih satuan dasar dan isi satuan tambahan dengan konversinya.
4. Lengkapi harga setiap satuan jual untuk semua level pelanggan aktif.
5. Buat pelanggan dengan satu level aktif, lalu buat supplier.
6. Buat akun kas/bank sebelum metode pembayaran yang mengarah ke akun tersebut.

**Penting:** menyimpan barang tidak menambah stok. Jangan mengartikan angka
konversi sebagai saldo. `1 Box = 4 Unit` berarti isi kemasan, bukan stok 4.

## 5. Increment C: Sediakan Stok

Ada dua kebutuhan berbeda. Pilih yang sesuai, jangan menghitung barang yang
sama dua kali.

| Kebutuhan | Alur |
| --- | --- |
| Barang sudah ada saat mulai memakai aplikasi | Stok awal per barang/gudang, lalu posting. |
| Barang baru dibeli dari supplier | Draft Order Pembelian, periksa fisik, lalu Terima dan bayar. |

Stok awal hanya dapat diposting satu kali untuk pasangan barang dan gudang.
Harga pokok stok awal maupun pembelian wajib positif, per satuan yang dipilih.
Jangan menggunakan stok awal untuk menambah stok pembelian berikutnya.

**Boleh lanjut jika:** saldo di gudang yang akan dipakai POS sudah benar dan
barang/satuan/pelanggan/metode bayar aktif.

## 6. Increment D: Jual Dan Cetak Bukti

Pelaksana: kasir dengan izin membuka POS dan posting penjualan.

1. Pilih gudang serta pelanggan yang benar.
2. Cari/scan barang, pilih satuan, lalu masukkan jumlah.
3. Periksa harga yang muncul dari level pelanggan, bukan mengetik harga bebas.
4. Isi diskon nota atau pajak bila memang digunakan pada transaksi ini.
5. Klik **Periksa keranjang**. Ini validasi, belum penjualan berhasil.
6. Buka **Pembayaran**, pilih metode dan nominal yang diterima.
7. Klik **Bayar dan posting** satu kali, tunggu hasilnya.
8. Pastikan bukti/nomor penjualan tampil, lalu cetak atau simpan PDF melalui browser.

Penjualan berhasil mengurangi stok, memakai biaya FIFO, dan mencatat pembayaran
bersama-sama. Mengubah keranjang sesudah diperiksa memerlukan pemeriksaan ulang.
Jika hasil belum pasti karena jaringan terputus, gunakan pemeriksaan/pemulihan
yang ditampilkan, bukan membuat transaksi baru dengan isi yang sama.

## 7. Increment E: Tangani Retur Sebagai Dua Tahap

```text
Retur pelanggan: invoice -> draft -> terima barang -> refund
Retur supplier:  pembelian -> draft -> serahkan barang -> terima uang
```

Posting barang dan penyelesaian uang adalah dua langkah berbeda. Selesai
posting retur belum berarti uang sudah kembali. Gunakan transaksi asal, bukan
mengisi harga barang saat ini. Jumlah retur tidak boleh melebihi sisa jumlah asal.

Retur pelanggan saat ini untuk barang layak jual ke gudang invoice asal.
Jangan memasukkan barang rusak ke stok jual melalui alur ini. Retur supplier
yang uangnya akan diterima harus berasal dari pembelian yang sudah lunas.

## 8. Increment F: Pindahkan Atau Koreksi Stok

- **Transfer antar-gudang:** asal dan tujuan berbeda, simpan draft lalu posting
  setelah barang benar-benar siap dipindahkan. Tidak mencatat pembayaran.
- **Koreksi stok:** gunakan selisih fisik yang telah diperiksa dan alasan yang
  jelas. Ini bukan cara menghapus penjualan atau mengganti pembayaran.
- **Kartu stok:** telusuri asal perubahan sebelum melakukan koreksi.

Saat menghitung selisih, saldo sistem 10 dan fisik 8 berarti **Kurangi stok 2**,
bukan memasukkan 8 pada kolom kuantitas selisih.

## 9. Increment G: Periksa Hasil Dan Akhiri Hari

Pelaksana: kasir, keuangan, gudang dan pemilik menurut aksesnya.

1. Kasir memeriksa riwayat penjualan miliknya dan kebutuhan cetak ulang.
2. Keuangan mencocokkan bukti pembayaran dan riwayat empat sumber transaksi.
3. Gudang memeriksa saldo serta kartu stok jika ada perbedaan fisik.
4. Pemilik memeriksa Dasbor/Laporan Operasional dengan tanggal dan gudang yang sesuai.
5. Petugas berizin memeriksa status backup; lakukan backup manual sesuai prosedur.
6. Catat persoalan yang belum selesai, serahkan nomor dokumennya, lalu logout.

Belum ada fitur buka/tutup shift atau hitung selisih kas. Pemeriksaan akhir hari
di sini adalah prosedur kerja manusia, bukan tombol Tutup shift.

## 10. Latihan Terpadu Di Lingkungan Pelatihan

Contoh fiktif, tidak otomatis ada dalam aplikasi. Siapkan Gudang Utama dan
Gudang Cadangan; barang **Kamera Latihan 2MP**, satuan dasar Unit, `1 Box = 4
Unit`, serta pelanggan Installer. Harga jual Unit: Eceran Rp500.000, Grosir
Rp475.000, Installer Rp450.000. Harga Box masing-masing empat kali harga Unit.
Lengkapi semua kombinasi satuan jual/level sebelum menyimpan barang.

Jangan isi stok awal untuk barang latihan ini; latihan memakai pembelian.

| Tahap | Tindakan | Saldo Utama | Saldo Cadangan | Pengaruh Pembayaran |
| --- | --- | --- | --- | --- |
| Awal | Master saja | 0 Unit | 0 Unit | Tidak ada |
| Beli | Terima dan bayar 2 Box x Rp1.200.000/Box | 8 Unit | 0 Unit | Keluar Rp2.400.000 |
| Jual | Pelanggan Installer membeli 2 Unit, tanpa diskon/pajak | 6 Unit | 0 Unit | Masuk bersih Rp900.000 |
| Retur pelanggan | Terima 1 Unit lalu refund | 7 Unit | 0 Unit | Keluar Rp450.000 |
| Retur supplier | Serahkan 0,25 Box = 1 Unit lalu terima uang | 6 Unit | 0 Unit | Masuk Rp300.000 |
| Transfer | Posting 2 Unit ke Cadangan | 4 Unit | 2 Unit | Tidak ada |
| Koreksi | Kurangi 1 Unit sesuai selisih fisik terkonfirmasi | 3 Unit | 2 Unit | Tidak ada |

Pada penjualan, contoh uang tunai diterima Rp1.000.000 dan kembalian Rp100.000;
pembayaran bersih tetap Rp900.000. Pada retur supplier, gunakan satuan snapshot
asal Box; `0,25 Box` bukan mengganti konversi barang. Bila saldo/layer sumber
tidak lagi cukup, jangan memaksa posting retur supplier.

**Lulus latihan jika:** saldo akhir 3 Unit dan 2 Unit, bukti penjualan benar,
retur barang dan uang selesai terpisah, serta riwayat pembayaran memuat empat
sumber di atas. Selisih arus pembayaran Rp1.650.000 keluar bukan saldo kas
aktual atau laba; belum memperhitungkan uang awal dan transaksi lainnya.

## 11. Kesalahan Yang Sering Terjadi

| Kejadian | Yang Dilakukan |
| --- | --- |
| Tombol/menu tidak tersedia atau 403 | Periksa role/permission dan status dokumen bersama admin; jangan memakai akun orang lain. |
| Harga tidak sesuai dugaan | Cek pelanggan, level, satuan dan matriks harga; jangan mengubah konversi untuk mengakali harga. |
| Stok kurang padahal ada di gudang lain | Pilih gudang yang benar atau transfer resmi; jangan membuat stok minus. |
| Data sudah berubah oleh petugas lain | Muat ulang, periksa kembali nilai terbaru, lalu ulangi tindakan yang masih diperlukan. |
| Posting/pembayaran belum pasti | Jangan klik berkali-kali; periksa hasil/recovery dan nomor dokumen. |
| Sudah posted tetapi salah | Hentikan transaksi lanjutan, catat nomor dan hubungi supervisor; jangan edit database. |
| Cetak batal/gagal | Periksa riwayat dan cetak ulang; jangan mengulangi penjualan. |
| Backup otomatis aktif tetapi tidak jalan | Jadwal tersimpan belum membuktikan scheduler server berjalan; hubungi administrator. |

## 12. Fitur Yang Belum Menjadi Prosedur Aktif

Belum termasuk transaksi tempo/piutang/utang, pembayaran parsial, shift kasir,
keranjang tertahan, penerimaan pembelian parsial, serial/garansi/servis,
karantina, paket jasa, stock opname bersesi atau saldo berjalan kas/bank.
Pencatatan multi-satuan kabel tidak sama dengan pelacakan tiap potongan rol.
Lihat backlog untuk rencana pengembangan, bukan untuk langkah operasional saat ini.
