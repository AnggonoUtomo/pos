# Panduan Uji Scanner

Halaman ini untuk memeriksa barcode, bukan membuat transaksi toko. Fitur default
nonaktif; admin dapat mengaktifkannya, termasuk pada production, dengan prosedur
deployment berizin. Login dan hak inventory.view tetap wajib. Perangkat fisik
belum diuji; fitur ini tidak mengaktifkan printer atau gateway.

## Sebelum Memulai

1. Gunakan akun sendiri dengan hak inventory.view dan katalog yang sudah tersedia.
2. Buka `/platform/hardware/scanner`, pada toko ini melalui
   `https://pos.test/platform/hardware/scanner` setelah admin mengaktifkannya.
3. Perhatikan tanda Perangkat fisik belum diuji. Hasil bukan bukti scanner atau
   printer fisik sudah bekerja. Tidak perlu memasang driver untuk uji virtual.

## Memeriksa Barcode

1. Klik input Scan barcode atau tekan Alt+S.
2. Ketik barcode satuan jual atau SKU yang tersimpan, lalu tekan Enter.
   Jika memakai scanner mode keyboard/HID, fokuskan input lalu pindai barcode;
   perangkat perlu diatur mengirim Enter setelah kode. Jangan membuka pengaturan
   driver atau memasang layanan tambahan tanpa arahan admin.
3. Periksa nama barang dan satuannya, misalnya kamera CCTV Unit atau Dus.
4. Kode dengan nol awal, misalnya0012345, harus tetap menampilkan nol tersebut.
5. Scan kode sama dua kali untuk melihat dua hasil. Ini tidak menambah barang
   ke keranjang, tidak mengurangi stok dan tidak mencatat pembayaran.

Katalog dipakai dari master existing, bukan dibuat otomatis dari kode yang
diketik. Satuan khusus pembelian tidak tampil dalam uji satuan jual ini.

## Hasil Belum Berhasil

- Tidak ditemukan: periksa kode, status barang dan status satuan jual.
- Antrean penuh: tunggu hasil sebelum mengirim uji berikutnya. Maksimal20 uji
  menunggu; layar menampilkan20 hasil terakhir saja.
- Habis waktu/gangguan koneksi: sistem tidak mengirim ulang secara otomatis.
  Periksa koneksi uji dan hasil yang sudah muncul sebelum mencoba lagi.
- Akses ditolak: hubungi admin tentang izin akun uji. Jangan meminjam akun.
- Halaman404: hubungi admin untuk memeriksa flag dan cache deployment. Jangan
  mengubah konfigurasi sendiri atau meminjam akun untuk melewati pembatasan.

## Membersihkan Dan Keluar

Klik Bersihkan hasil atau Alt+R, lalu konfirmasikan. Hasil dan antrean uji
dibersihkan, bukan master barang atau transaksi. Saat halaman ditutup/dimuat
ulang, hasil sementara tidak disimpan. Buka panduan halaman melalui F1.

Uji USB/dongle/Bluetooth HC-4208DB serta cetak/scan-back label CT420D akan
dilakukan terpisah dengan perangkat nyata dan persetujuan pemilik.

## Untuk Admin

Source tetap default OFF. Aktivasi berizin memakai POS_HARDWARE_ENABLED=true
serta rebuild `php artisan config:cache` dan `php artisan route:cache` setelah
backup konfigurasi privat. OFF kembali menutup halaman tanpa mengubah data.
Jangan mengubah APP_ENV/debug, memasang runner OS atau membuat transaksi uji
pada data toko hanya untuk memeriksa scanner. Status aktivasi instalasi dan
hasil QA ada pada TASK-HW-011; cetak CT420D masih pekerjaan terpisah.

Workspace [Perangkat POS](PRINTER-LABEL.md) menambahkan konfigurasi QZ,
pratinjau label dan tes scan-back. Ini terpisah dari lookup satuan jual pada
halaman ini. Diagnostik ON tidak mengaktifkan print fisik; flag print dan UAT
tersendiri tetap diperlukan.
