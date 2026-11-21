# Printer Label Dan Scanner

Panduan label barang CT420D melalui gateway QZ, bukan printer struk.
Konfigurasi/pratinjau berbeda dari uji fisik. Diterima gateway bukan bukti
bahwa label telah tercetak atau dapat dipindai.

## 1. Persiapan Administrator

1. Buka **Perangkat POS** pada sidebar atau command palette.
2. Gunakan hak view untuk membuka, manage untuk profil, print untuk kirim dan
   reprint untuk cetak ulang. Katalog juga memerlukan inventory.view.
   Permission baru tidak otomatis diberikan ke role/akun existing.
3. Admin memasang [QZ Tray resmi](https://qz.io/download/) dan driver CT420D
   secara manual pada PC tujuan. Aplikasi tidak memasang driver, Java,
   sertifikat, service atau runner PowerShell.
4. Sebelum UAT fisik, pengiriman cetak nonaktif. Konfigurasi/pratinjau tetap
   tersedia; jangan membuat printer palsu agar tombol terlihat aktif.

## 2. Cari Dan Simpan Printer

1. Pada **Printer**, pilih gateway lalu **Cari printer**.
2. Jika QZ belum berjalan/izin ditolak, periksa QZ serta izinnya. Jangan melewati
   warning TLS, mematikan secure cookie atau mengubah APP_ENV.
3. Pilih printer yang benar dari hasil OS. Printer ditemukan masih bisa offline
   atau kehabisan media; jangan memilih printer pertama/default secara membabi buta.
4. Klik **Tambah profil**. Isi nama profil, printer OS, gateway, lebar/tinggi,
   orientasi dan status aktif. CT420D menggunakan203 DPI.
5. Simpan. Edit/status/arsip/restore memakai version dan audit. Profil dengan
   job belum selesai tidak dapat diarsipkan. Arsip tidak menghapus riwayat.

Default50x30 mm hanya titik awal. Sesuaikan dengan label/driver dan uji fisik.
Batas aplikasi20-100 mm lebar,20-150 mm tinggi, salinan1-100. Barcode/nama yang
tidak muat ditolak, bukan dipaksa menjadi terlalu kecil.

## 3. Pilih Dan Pratinjau Label

1. Buka **Label**, cari barang, SKU atau barcode.
2. Pilih satuan yang benar. Satuan khusus pembelian berbarcode juga dapat dipilih.
3. Pilih profil tujuan secara eksplisit dan isi jumlah label.
4. Periksa nama, SKU, satuan serta kode pada pratinjau hitam-putih.
5. Barcode kosong dibuat/simpan dahulu melalui master Barang. Halaman perangkat
   tidak mengganti barcode, harga atau saldo secara otomatis.

Label tahap ini tidak memuat harga jual/data pelanggan. Cetak label browser
dari Barang tetap tersedia seperti sebelumnya, termasuk pilihan A4.

## 4. Uji Fisik Dan Pengiriman

Admin mengaktifkan pengiriman hanya pada pengujian/perangkat yang disetujui.
POS_HARDWARE_PRINTING_ENABLED berbeda dari flag diagnostik, default OFF.
Tidak ada auto-print sesudah checkout.

1. Setelah driver/media siap dan uji diizinkan, hubungkan QZ lalu **Test label**.
2. Periksa ukuran, margin, arah, kontras, gap/media dan salinan fisik.
3. Scan dengan HC-4208DB; cocokkan kode tepat, termasuk nol awal.
4. Setelah rollout disahkan, operator dapat memakai **Kirim label**.
5. QZ awal dapat menampilkan dialog izin/cetak. Cetak senyap memerlukan trust
   dan signing terpisah; tidak memakai sertifikat demo untuk membuang warning.

Jalur ini mengirim gambar melalui driver OS, bukan raw ESC/POS atau TSPL/CPCL.
Jika driver CT420D tidak mendukung jalur gambar pada OS tujuan, hentikan dan
laporkan. Jangan mencoba protokol acak, cutter atau drawer.

## 5. Baca Riwayat

- **Siap dikirim:** job tercatat, belum diambil. Pembuat dapat mengirim atau
  membatalkan sebelum pengiriman, termasuk saat flag print kemudian dimatikan.
- **Menunggu konfirmasi:** job diambil sekali; jangan kirim dari tab lain.
- **Diterima gateway:** permintaan diterima gateway; periksa kertas fisik.
- **Tidak dikirim:** gagal/batal sebelum pengiriman, bukan transaksi gagal.
- **Hasil belum pasti:** mungkin sudah terkirim tetapi konfirmasi terputus.
  Periksa printer sebelum tindakan selanjutnya.

Cari nomor job/filter status dan gunakan pagination. Operator melihat job
sendiri; pemegang manage melihat semua. Jam WIB; job tidak dapat dihapus/edit
bebas. Cetak ulang memerlukan izin, pemeriksaan fisik dan alasan, membuat job
baru yang menaut ke asal. Bukan retry otomatis atau pengulangan transaksi.

## 6. Tes Scanner Dan Tablet

1. Pilih barang pada Label, lalu buka **Scanner**.
2. Fokus input. Scanner mode keyboard perlu mengirim kode diikuti Enter.
3. Periksa teks persis/nol awal dan scan yang sengaja diulang.
4. Hasil dibandingkan dengan barcode label pilihan. Ketikan manual juga diterima,
   sehingga hasil tidak membuktikan merek atau koneksi hardware terdeteksi.
5. Diagnostik satuan jual existing tetap tersedia untuk lookup barang.

Pada PC, localhost adalah QZ pada PC itu sendiri. Pada tablet, localhost bukan
PC toko. Admin menyiapkan QZ print-server WSS, gateway dalam allowlist
POS_QZ_ALLOWED_HOSTS, serta trust sertifikat pada tablet. Pembukaan port/firewall,
sertifikat dan pairing Bluetooth bukan tindakan otomatis aplikasi. Jangan
mengganti menjadi WS/HTTP hanya untuk melewati TLS.

F1 membuka panduan kanan. Alt+1 sampai Alt+4 memilih tab, Alt+F mencari riwayat.
Jika koneksi gagal sesudah kirim, jangan terus menekan cetak. Catat nomor job
dan hubungi admin. PDF global/per-role lama belum memuat tambahan ini.
