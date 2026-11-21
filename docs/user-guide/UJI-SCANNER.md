# Panduan Uji Scanner Virtual

Halaman ini untuk pengujian local/testing, bukan transaksi toko. Fitur default
nonaktif dan tidak tersedia di production. Admin/developer menyiapkan
environment uji terpisah; jangan mengubah konfigurasi toko agar bisa membukanya.

## Sebelum Memulai

1. Gunakan akun uji dengan hak inventory.view dan katalog uji yang sudah tersedia.
2. Buka `/platform/hardware/scanner` pada server local/testing yang disiapkan.
3. Perhatikan tanda Virtual / fisik belum diuji. Hasil bukan bukti scanner atau
   printer fisik sudah bekerja. Tidak perlu memasang driver untuk uji virtual.

## Memeriksa Barcode

1. Klik input Scan barcode atau tekan Alt+S.
2. Ketik barcode satuan jual atau SKU yang tersimpan, lalu tekan Enter.
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
- Halaman404: pastikan environment uji/flag benar. Di production penolakan ini
  memang disengaja, bukan alasan mengaktifkan fitur toko.

## Membersihkan Dan Keluar

Klik Bersihkan hasil atau Alt+R, lalu konfirmasikan. Hasil dan antrean uji
dibersihkan, bukan master barang atau transaksi. Saat halaman ditutup/dimuat
ulang, hasil sementara tidak disimpan. Buka panduan halaman melalui F1.

Uji USB/dongle/Bluetooth HC-4208DB serta cetak/scan-back label CT420D akan
dilakukan terpisah dengan perangkat nyata dan persetujuan pemilik.
