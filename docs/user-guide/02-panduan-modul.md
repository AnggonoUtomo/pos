# Panduan Modul: Belajar Bertahap

Gunakan bagian sesuai tugas Anda. Jangan mengerjakan semua langkah sekaligus
pada data toko. Latihan terpadu tersedia di [alur global](01-alur-global.md).
Jika menu tidak muncul, tanyakan akses kepada administrator; jangan meminjam akun.

## 1. Identitas Dan Akses

**Tujuan:** menyiapkan akun pribadi dan membagi tugas. Pelaksana administrator.

1. Buka **Identitas & Akses**, periksa tab **Permission** untuk izin yang tersedia.
2. Pada tab **Role**, buat role sesuai tugas dan pilih permission yang terdaftar.
3. Pada **Akses Pengguna**, tambah pengguna dengan nama, email, password awal
   dan konfirmasi password. Pilih role yang disepakati pemilik.
4. Sampaikan password awal secara pribadi, bukan melalui catatan transaksi.
5. Minta petugas masuk dengan akun sendiri dan memeriksa menu yang diperlukan.
6. Gunakan edit untuk nama/email atau pengaturan role; nonaktifkan akun petugas
   yang tidak lagi bekerja sesuai kewenangan Anda.

**Periksa:** akun aktif, email benar, izin tidak berlebihan. Role `super-system`
khusus administrator tepercaya; bukan role kasir harian. Sistem melindungi akun
super-system aktif terakhir. Pengelolaan akun ini belum mencakup reset password
oleh admin atau hapus akun oleh admin. Jangan menganggap role baru langsung
memiliki semua izin tanpa memilihnya.

Pada pengaturan **Profile**, pengguna dapat memperbarui **Nama** dan **Alamat
email**, lalu **Simpan perubahan**. Validasi email tetap berlaku; ikuti
verifikasi bila diminta. Password pribadi diubah melalui menu Password.
Halaman profil tidak menyediakan tombol hapus akun pribadi. Untuk perubahan
role atau penonaktifan, hubungi administrator. Pembaruan ini belum digabungkan
ke PDF global/per-role.

## 2. Pengaturan Toko

**Prasyarat:** data identitas/kontak toko sudah disahkan pemilik.

1. Buka **Pengaturan toko**.
2. Isi identitas, kontak dan alamat sesuai kolom yang tersedia.
3. Simpan, lalu periksa tampilannya pada bukti transaksi baru yang sah atau
   latihan di lingkungan pelatihan.

**Hasil:** transaksi baru menggunakan identitas toko yang disimpan. Bukti lama
memakai salinan identitas saat transaksi dibuat; mengubah pengaturan tidak
berarti mengubah seluruh struk lama.

## 3. Operasional: Cetak Dan Backup

**Prasyarat:** izin melihat/mengelola Operasional dan driver printer dari admin.

### Format Cetak

1. Buka **Operasional** dan pilih A4, 58 mm atau 80 mm sesuai kertas.
2. Simpan pengaturan, gunakan **Uji cetak**.
3. Pada dialog browser/Windows, pilih printer atau Simpan sebagai PDF.
4. Cocokkan ukuran kertas, skala, margin dan hasil cetak; jangan menutup
   transaksi baru hanya untuk mengetes printer.

Pemilihan USB/LAN ditangani driver/dialog cetak, bukan pengiriman langsung
ESC/POS dari aplikasi. Menutup dialog cetak tidak membatalkan transaksi.

### Backup Manual Dan Jadwal

1. Klik **Backup sekarang**, lalu konfirmasi **Buat backup**.
2. Tunggu status hasil; jangan menganggap klik tombol berarti sudah berhasil.
3. Pada PC ini, admin memeriksa berkas di `C:\laragon\www\pos\backups`.
   Folder ini bukan tautan unduhan publik.
4. Untuk jadwal, atur **Backup otomatis harian** dan **Jam backup (WIB)**,
   lalu **Simpan pengaturan**. Jadwal yang diminta toko adalah 22.00 WIB.
5. Periksa **Hasil terakhir**, waktu backup serta **Sinyal runner**. Tulisan
   runner merujuk pemeriksaan pemicu jadwal, bukan perintah memasang PowerShell.

**Penting:** menyimpan opsi jadwal tidak memasang cron/service. Administrator
menyiapkan pemicu Laravel Scheduler sesuai OS server. Jika **Runner belum
terhubung**, minta admin memeriksa pemicu. Jangan membuat penjadwal sendiri.
Backup di PC yang sama belum aman dari kerusakan PC; salinan terpisah masih
perlu disiapkan/disahkan. Pemulihan backup dilakukan administrator, bukan
melalui langkah coba-coba operator. Printer fisik/tablet belum diuji hanya
karena PDF berhasil disimpan.

## 4. Satuan

**Tujuan:** menamai satuan, bukan menetapkan isi setiap kemasan.

1. Buka **Satuan**, pilih tambah.
2. Isi kode dan nama sesuai kolom, misalnya Unit, Box, Meter atau Rol.
3. Simpan dan pastikan aktif sebelum dipakai pada barang.
4. Edit, nonaktifkan, hapus atau pulihkan hanya jika diizinkan dan tidak
   melanggar referensi barang.

**Periksa:** tidak ada satuan ganda dengan arti sama. Isi Box/Rol ditentukan
pada masing-masing barang. `1 Rol = 100 Meter` pada kabel A tidak menetapkan
isi Rol untuk kabel B. Satuan yang sudah dipakai barang tidak boleh dihapus
atau dinonaktifkan. Jangan mengganti nama Unit menjadi Box untuk mengubah isi.

## 5. Gudang

1. Buka **Gudang**, tambah kode/nama gudang sesuai lokasi penyimpanan nyata.
2. Simpan sebagai aktif dan periksa kembali penamaannya.
3. Gunakan gudang yang benar ketika mengisi stok, membeli, menjual atau transfer.

**Hasil:** pilihan lokasi stok, bukan cabang perusahaan baru. Saldo dan FIFO
berjalan per barang dan gudang. Barang di Cadangan tidak otomatis tersedia
untuk penjualan dari Utama. Mengubah status gudang dapat ditolak jika masih
dibutuhkan transaksi/referensi; selesaikan masalahnya, jangan menghapus database.

## 6. Level Pelanggan

1. Buka **Pelanggan**, masuk ke bagian **Level pelanggan**.
2. Tambah level, misalnya Eceran, Grosir dan Installer.
3. Periksa kode/nama serta status aktifnya.
4. Koordinasikan penambahan level dengan admin barang agar matriks harga lengkap.

**Hasil:** kategori penentu harga jual, bukan diskon otomatis berdasarkan jumlah
belanja. Level yang dirujuk pelanggan tidak boleh dinonaktifkan/dihapus, termasuk
referensi pelanggan yang dihapus sementara. Jangan membuat level baru pada jam
sibuk tanpa memastikan harga semua barang siap.

## 7. Barang, Konversi Dan Harga

**Prasyarat:** satuan dan level pelanggan aktif sudah tersedia.

1. Buka **Barang** > **Tambah barang**.
2. Isi SKU unik, nama yang jelas dan **Satuan dasar** terkecil.
3. Di **Satuan & barcode**, isi barcode bila ada dan satuan tambahan yang perlu.
4. Pada **Isi satuan dasar**, tulis isi kemasan per barang. Unit isi 1; Box isi
   4 berarti `1 Box = 4 Unit`. Itu bukan saldo stok.
5. Tandai satuan yang boleh **Jual** dan/atau **Beli** sesuai penggunaan.
6. Lengkapi **Harga jual (Rp)** untuk setiap satuan jual x level aktif. Semua
   kombinasi wajib terisi positif sebelum barang disimpan.
7. Simpan dan cek ulang SKU, barcode, konversi serta harga setiap level.

Contoh kabel: dasar Meter, Rol isi 100; kuantitas `0,5` berarti setengah,
bukan lima. Tampilan Indonesia memakai koma untuk desimal dan titik untuk
ribuan. `Rp1.250.000` berarti satu juta dua ratus lima puluh ribu rupiah.

**Periksa:** harga Unit dan Box boleh berbeda menurut kebijakan toko; jangan
berasumsi aplikasi selalu mengalikan harga Unit untuk mengisi Box. Satuan dasar
tidak bisa diganti setelah barang dibuat. Menyimpan barang tidak menambah stok.
Harga beli/HPP dimasukkan pada stok awal atau pembelian, bukan menggantikan
matriks harga jual. Perubahan harga master tidak menulis ulang transaksi lama.

## 8. Pelanggan

1. Buka **Pelanggan**, pilih tambah.
2. Isi kode/nama dan pilih satu level aktif.
3. Isi kontak/alamat bila diperlukan, simpan.
4. Saat melayani di POS, pilih pelanggan yang sesuai sebelum memeriksa harga.

**Hasil:** pilihan pelanggan mengarahkan harga menurut level. Jangan memilih
pelanggan Installer untuk pembeli Eceran hanya agar harga lebih murah. Kontak
dan alamat opsional, tetapi level tidak boleh kosong. Hapus/pulihkan master
melalui fitur yang tersedia; transaksi lama tetap memiliki snapshot sendiri.

## 9. Supplier

1. Buka **Supplier**, tambah kode/nama pemasok.
2. Isi kontak/alamat jika ada dan simpan sebagai aktif.
3. Pastikan supplier benar sebelum digunakan di Order Pembelian.

**Periksa:** satu pemasok tidak dibuat berkali-kali hanya karena beda penulisan.
Gunakan status nonaktif untuk pemasok yang tidak digunakan lagi sesuai aturan
referensinya. Master supplier belum merupakan daftar utang/jatuh tempo.

## 10. Kas/Bank Dan Metode Pembayaran

**Pelaksana:** keuangan berizin; konfirmasi rekening dengan pemilik di luar aplikasi.

1. Buka **Kas dan bank**, tambah kode, nama akun dan jenis akun.
2. Untuk bank, lengkapi informasi bank yang tersedia dan periksa pemiliknya.
3. Buka **Metode pembayaran**, tambah kode/nama, jenis Tunai/Debit/Transfer.
4. Pilih **Akun tujuan** aktif yang benar, lalu simpan metode sebagai aktif.
5. Periksa pasangan metode-akun sebelum transaksi toko berjalan.

Contoh: Tunai Kasir -> Kas Utama; Transfer Bank -> akun bank toko yang benar.
Jangan mengarahkan Transfer ke akun tunai hanya agar tombol bisa digunakan.
Metode aktif ini juga dapat dipilih untuk pengeluaran/refund sesuai konteks
transaksi. Membuat akun tidak memasukkan saldo awal atau membuktikan saldo bank.
Perubahan master tidak mengganti snapshot pembayaran lama.

## 11. Stok Awal

**Prasyarat:** hasil hitung fisik awal disahkan; barang/gudang/satuan aktif.

1. Buka **Stok** > **Stok awal**, tambah draft untuk satu gudang.
2. Pilih barang, satuan, kuantitas dan harga pokok per satuan pilihan.
3. Pastikan satu barang hanya satu baris pada dokumen; simpan draft.
4. Cocokkan kembali dengan fisik dan bukti harga pokok.
5. Petugas berizin mem-posting dokumen setelah pemeriksaan.
6. Periksa **Saldo stok** dan **Kartu stok**.

Contoh: 2 Box isi 4 Unit dengan harga pokok Rp1.200.000/Box menghasilkan
8 Unit bernilai Rp2.400.000. Jangan isi harga Unit di kolom harga Box.

**Hasil:** saldo, pergerakan stok dan lapisan biaya FIFO; bukan pembayaran kas.
Pasangan barang+gudang yang sudah mempunyai stok awal posted tidak diisi ulang.
Draft bisa diperbaiki/hapus/pulihkan sesuai izin; posted tidak boleh diedit bebas.

## 12. Order Pembelian: Dari Draft Sampai Lunas

**Prasyarat:** supplier, gudang, barang dan metode pembayaran siap; uang serta
barang fisik sudah diperiksa petugas yang bertanggung jawab.

1. Buka **Order Pembelian**, tambah draft.
2. Pilih supplier/gudang, isi barang, satuan beli, jumlah dan harga beli per
   satuan pilihan. Harga wajib positif sejak draft disimpan.
3. Simpan, catat nomor dokumen dan cocokkan dengan nota supplier.
4. Periksa seluruh fisik barang. Tahap ini menerima penuh, bukan parsial.
5. Petugas yang punya izin penerimaan dan pembayaran memilih **Terima dan bayar**.
6. Di dialog **Terima dan bayar pembelian**, pilih metode dan nominal; gabungan
   beberapa metode boleh, tetapi total wajib tepat tanpa kembalian.
7. Konfirmasi **Catat pembayaran** sekali dan tunggu hasil.
8. Periksa status **Diterima**, pembayaran **Lunas**, saldo/kartu stok serta
   riwayat pembayaran.

**Hasil:** penerimaan stok/FIFO dan pembayaran berhasil bersama atau dibatalkan
bersama. Draft belum menambah stok atau uang keluar. Tidak perlu menambah stok
awal untuk barang pembelian yang sama.

Jika dokumen lama sudah Diterima tetapi pembayaran **Belum dicatat**, petugas
berizin dapat memakai **Bayar pembelian**; langkah ini tidak menambah stok lagi.
Status tersebut bukan fasilitas utang/tempo. Tombol utama Terima dan bayar
memerlukan kedua izin; staf draft saja tidak otomatis boleh mengeksekusinya.

## 13. POS: Penjualan Langsung Lunas

**Prasyarat:** izin membuka dan posting POS, stok cukup di gudang pilihan,
pelanggan/level/harga/satuan/metode aktif.

1. Buka **POS**, pilih gudang dan pelanggan.
2. Cari atau scan barcode. Pastikan hasil, satuan dan jumlah benar.
3. Periksa harga berdasarkan level; gabungkan baris hanya sesuai kebutuhan.
4. Isi diskon nota persen/nominal atau pajak jika memang berlaku. Periksa total
   hasil hitung aplikasi sebelum menerima pembayaran.
5. Klik **Periksa keranjang**. Perbaiki pesan validasi; langkah ini belum
   menyimpan penjualan dan tidak memesan stok.
6. Buka **Pembayaran**, isi metode/nominal, lalu **Bayar dan posting**.
7. Tunggu bukti penjualan. Cocokkan uang diterima, kembalian dan total, lalu
   cetak melalui browser. Selesaikan layar bukti sebelum pelanggan berikutnya.

Contoh tagihan Rp100.000: Transfer Rp60.000 + Tunai Rp50.000 menghasilkan
kembalian Rp10.000. Kas tunai bersih Rp40.000; transfer Rp60.000. Maksimal satu
baris Tunai, hanya Tunai boleh menghasilkan kembalian; non-tunai tidak boleh
melebihi sisa tagihan. Tidak menerima pembayaran kurang/tempo pada tahap ini.

**Jika koneksi terganggu:** jangan membuat penjualan baru untuk mengulang
pembayaran yang belum pasti. Gunakan pemeriksaan hasil yang disediakan, cek
riwayat atau minta supervisor menelusuri nomor dokumen. Stok tidak boleh minus.

## 14. Riwayat Penjualan Dan Cetak Ulang

1. Buka **Riwayat Penjualan**.
2. Cari nomor invoice atau gunakan filter tanggal, gudang dan pelanggan.
3. Buka detail; cocokkan nomor, pelanggan, barang, satuan dan pembayaran.
4. Cetak ulang bukti dari transaksi yang sama jika diperlukan.

**Hasil:** salinan bukti transaksi lama, bukan transaksi baru. Kasir dengan
izin posting melihat transaksinya sendiri; melihat semua transaksi memerlukan
izin khusus. Halaman ini readonly, tanpa edit/hapus/void bebas. Salah cetak
atau batal dialog printer tidak mengharuskan penjualan diulang.

## 15. Retur Penjualan Dan Refund Pelanggan

**Prasyarat:** invoice posted ditemukan, barang benar-benar diterima dan layak
jual. Barang rusak/karantina belum masuk alur ini.

1. Buka **Retur Penjualan**, buat draft berdasarkan invoice asal.
2. Pilih baris/jumlah yang kembali. Satuan/harga berasal dari snapshot invoice;
   jumlah tidak boleh melebihi sisa yang dapat diretur.
3. Simpan dan periksa fisik. Koreksi draft bila perlu sebelum posting.
4. Petugas berizin memilih **Terima barang retur** dan mengonfirmasi.
5. Periksa stok masuk ke gudang invoice asal. Biaya berasal dari FIFO penjualan
   asal, dengan layer retur baru; transaksi asal tidak ditulis ulang.
6. Keuangan memilih **Refund**, periksa nominal otomatis, metode dan akun.
7. Isi pembayaran tepat sebesar nominal refund, lalu **Konfirmasi refund**.
8. Periksa status hasil dan riwayat pembayaran; serahkan bukti kepada pelanggan.

**Penting:** selesai Terima barang belum berarti uang dikembalikan. Refund
memakai nilai bersih penjualan asal, bukan harga master saat ini, dengan dua
desimal rupiah. Bagian retur dibulatkan turun; penyelesaian seluruh jumlah
mengambil sisa pembulatan. Metode aktif boleh berbeda dari pembayaran asal.
Tidak ada nominal bebas, kembalian atau refund parsial.

Jika nilai hasilnya nol, gunakan penyelesaian **Tanpa pengembalian uang**;
jangan membuat pembayaran nol. Jika hasil konfirmasi tidak pasti, gunakan
**Periksa ulang hasil**, bukan menyerahkan uang dua kali.

## 16. Retur Pembelian Dan Uang Kembali Supplier

**Prasyarat:** pembelian sudah Diterima, barang dikembalikan ke pemasok dan
saldo sumber cukup. Penerimaan uang saat ini hanya untuk asal pembelian lunas.

1. Buka **Retur Pembelian**, buat draft dari pembelian asal.
2. Pilih baris/jumlah menggunakan snapshot satuan dan harga beli asal.
3. Periksa fisik, alasan dan jumlah; simpan draft.
4. Petugas berizin memilih **Posting serah barang** ketika barang diserahkan.
5. Periksa saldo/kartu stok. Pengurangan mengikuti layer penerimaan asal;
   stok barang dari sumber lain tidak otomatis dapat menggantikannya.
6. Setelah supplier mengembalikan uang, keuangan memilih **Terima uang supplier**.
7. Periksa nilai otomatis, pilih metode aktif/akun dan isi nominal gabungan
   dengan total tepat. Konfirmasi **Konfirmasi penerimaan** satu kali.
8. Pastikan **Uang diterima penuh** dan riwayat pembayaran sesuai bukti aktual.

**Hasil:** penyerahan barang mengurangi stok; penerimaan uang tidak mengurangi
stok lagi. Nilai uang mengikuti nilai beli snapshot, bukan biaya FIFO atau
harga jual sekarang. Presisi perhitungan enam desimal, bagian dibulatkan turun
dan bagian akhir menjaga sisa pembulatan. Jika nilai nol, selesaikan tanpa
pembayaran nol. Jangan menandai uang diterima hanya karena draft retur dibuat.
Belum ada potong utang/kredit supplier atau penerimaan uang parsial.

## 17. Transfer Antar-Gudang

1. Buka **Transfer antar-gudang**, tambah draft.
2. Pilih gudang asal dan tujuan berbeda, lalu barang/satuan/jumlah.
3. Simpan dan cocokkan barang fisik serta stok asal.
4. Koordinasikan perpindahan dengan petugas kedua lokasi, lalu **Posting transfer**.
5. Periksa pengurangan di asal dan penambahan di tujuan melalui kartu stok.

**Hasil:** kedua sisi diposting sekaligus, biaya FIFO tetap terjaga dan jumlah
total antargudang tidak berubah. Ini bukan pengiriman dengan stok transit atau
penerimaan bertahap. Jangan membuat transfer balik untuk sekadar mencoba
tombol. Draft boleh diedit/hapus/pulihkan sesuai izin; posted tidak diedit bebas.

## 18. Saldo, Kartu Dan Koreksi Stok

### Menelusuri Selisih

1. Buka **Stok** > **Saldo stok**; periksa barang dan gudang yang tepat.
2. Buka **Kartu stok** untuk menelusuri dokumen masuk/keluar.
3. Hitung fisik dalam satuan dasar. Pastikan tidak salah mengalikan isi Box/Rol.
4. Cari transaksi belum selesai, gudang salah atau perpindahan yang belum dicatat.

### Koreksi Yang Sudah Disahkan

1. Petugas berizin khusus membuka **Koreksi stok**.
2. Pilih gudang/barang, **Kurangi stok** atau **Tambah stok**.
3. Isi **Kuantitas selisih (satuan dasar)**, bukan saldo fisik akhir.
4. Untuk tambahan, isi harga pokok positif per satuan dasar yang diminta.
5. Isi **Alasan koreksi**, periksa kembali, lalu **Posting koreksi**.
6. Periksa pergerakan baru dan saldo akhir.

Contoh saldo 10 Meter, fisik 8,5 Meter: kurangi `1,5` Meter, bukan `8,5`.
**Penting:** koreksi langsung diposting, bukan draft approval. Pemeriksaan
supervisor adalah prosedur manusia. Koreksi bukan pengganti retur transaksi
atau cara membuat stok minus; salah posting harus dilaporkan, bukan mengedit
ledger/database. Tidak ada sesi stock opname formal pada tahap ini.

## 19. Riwayat Pembayaran

1. Buka **Pembayaran** untuk melihat riwayat.
2. Pilih tanggal, arah masuk/keluar, akun/metode atau cari nomor transaksi.
3. Buka detail snapshot dan cocokkan dengan bukti kas, bank atau transaksi asal.
4. Bila berbeda, catat nomor dan arahkan ke penanggung jawab sumber transaksi.

| Sumber | Arah Uang | Dibuat Melalui |
| --- | --- | --- |
| Penjualan | Masuk | Bayar dan posting di POS |
| Pembelian | Keluar | Terima dan bayar / pembayaran pembelian diterima |
| Refund pelanggan | Keluar | Penyelesaian uang Retur Penjualan |
| Refund supplier | Masuk | Penerimaan uang Retur Pembelian |

Riwayat ini readonly: tidak menambah pembayaran manual, menghapus pembayaran
atau mengubah akun transaksi lama. Total masuk dikurangi keluar bukan saldo
aktual bank, karena belum mencakup saldo awal/seluruh aktivitas di luar aplikasi.

## 20. Dasbor Dan Laporan Operasional

1. Buka **Dasbor** untuk ringkasan data nyata yang dapat diakses akun Anda.
2. Gunakan **Muat ulang ringkasan** bila memerlukan data terbaru.
3. Untuk pemeriksaan periode, buka **Laporan Operasional**.
4. Pilih tanggal dan gudang yang sesuai, lalu cocokkan ringkasan dengan detail
   penjualan/pembelian/retur, kartu stok serta riwayat pembayaran.
5. Catat selisih beserta periode, gudang dan nomor dokumen; serahkan ke pemilik.

**Periksa:** cakupan tanggal dan gudang sama sebelum membandingkan angka.
Ringkasan tidak boleh dianggap laporan laba bersih atau neraca akuntansi.
Tampilan bergantung izin; kasir tidak otomatis mendapat seluruh ringkasan
keuangan atau penjualan petugas lain.

## 21. Pola Umum Master Dan Draft

- **Simpan:** tunggu feedback berhasil, kemudian cek baris/status di daftar.
- **Edit:** hanya pada data/draft yang masih boleh diubah.
- **Nonaktif:** mencegah pemakaian baru sesuai aturan referensi; bukan menghapus
  transaksi lama.
- **Hapus/pulihkan:** gunakan daftar/filter status yang tersedia. Pemulihan
  dapat ditolak jika data bertabrakan atau referensinya tidak valid.
- **Posting:** periksa seluruh isi dan fisik/uang dahulu. Hak akses tidak
  membolehkan melewati validasi bisnis.
- **Gagal:** baca pesan kolom/toast, perbaiki penyebab dan ulangi hanya tindakan
  yang dipastikan belum berhasil.

## 22. Serah Terima Lintas Modul

Gunakan nomor dokumen sebagai penghubung, bukan foto layar tanpa konteks.

| Dari -> Ke | Yang Diserahkan | Titik Periksa |
| --- | --- | --- |
| Admin master -> Kasir | SKU, satuan, level dan harga | Semua kombinasi harga siap; stok belum otomatis ada |
| Pembelian -> Gudang/Keuangan | Nomor draft, nota, jumlah dan satuan | Fisik penuh dan dana siap sebelum Terima dan bayar |
| Gudang -> Kasir | Barang, gudang, saldo siap jual | Saldo di lokasi pilihan, bukan jumlah semua gudang |
| Kasir -> Petugas retur | Nomor invoice dan barang kembali | Sisa kuantitas asal dan kelayakan jual |
| Petugas retur -> Keuangan | Nomor retur posted dan bukti fisik | Nominal otomatis dan belum pernah diselesaikan |
| Keuangan -> Pemilik | Bukti pembayaran dan nomor transaksi | Empat sumber, akun, metode dan periode cocok |
| Semua petugas -> Administrator | Waktu, nomor dokumen dan pesan gagal | Tanpa membagikan password atau secret |

Lanjutkan dengan [pembagian role](03-role-dan-akses.md) untuk menentukan siapa
yang boleh mengerjakan setiap tahap.
