# Kebutuhan Setup Awal Perangkat POS

Status: kebutuhan produk dikonfirmasi; implementasi menunggu approval PLAN.

## Hasil Yang Diinginkan

Saat menyiapkan PC toko, QZ dipasang sekali. Setelah printer tersedia, admin
memasang driver dan membuka Perangkat POS untuk menemukan printer, memilih
tujuan serta menyimpan ukuran label. Operator tidak perlu mengatur QZ berulang.
Pada scanner mode keyboard, hubungkan/pair melalui OS lalu langsung scan di
input aplikasi yang fokus; QZ tidak menjadi syarat scanner tersebut.

## Setup PC Dan Klien

- Target eksekusi sekarang Windows x64 pada PC ini, bukan memasang ke PC lain.
- Sediakan skrip setup sekali-jalan untuk admin Windows yang dapat dipakai
  ulang saat deployment klien; bukan runner, scheduled task atau service.
- Gunakan installer resmi QZ 2.3.0+1, versi dipin, hash dan tanda tangan dicek.
  QZ berjalan sebagai tray dengan startup login bawaannya, bukan konsol berulang.
- Instalasi default QZ mencakup file aplikasi/runtime, integrasi Windows dan
  trust TLS lokal QZ. Jelaskan efek ini pada approval; jangan menonaktifkan TLS.
- UAC ditangani user; tidak menyimpan password admin atau melewati UAC.
- Dokumentasikan setup Linux menggunakan installer resmi; tidak menjalankan
  installer Linux di Windows atau mengklaim Linux runtime teruji.
- Installer/runtime/private key tidak disimpan di Git atau webroot publik.
  Menyalin folder Laravel saja tidak memasang QZ pada PC klien.

## Printer

Perangkat POS memeriksa gateway dan mengambil daftar printer OS satu kali
saat workspace dibuka oleh user berizin manage/print. SDK tetap tidak dipakai
di login/dashboard/POS atau halaman module lain. Tidak ada polling/retry tanpa
batas. Tombol Deteksi ulang tersedia untuk printer yang baru dihubungkan.

Daftar printer tidak otomatis dipilih/disimpan sebagai profil atau memicu
cetak. Operator tetap mengonfirmasi printer/ukuran; printer OS virtual dapat
muncul dan tidak boleh disebut sebagai CT420D nyata. Gateway tersedia,
printer terdaftar dan hasil cetak fisik adalah tiga status berbeda.

QZ unsigned tetap dapat meminta izin koneksi. Tidak menjanjikan pengalaman
silent tanpa trust/signing. Tidak mengubah sertifikat situs pos.test, membuka
firewall/LAN atau mengaktifkan mode print-server tanpa keputusan tersendiri.

## Scanner

Mode yang dipilih: keyboard/HID dengan akhiran Enter. Saat tab Scanner dibuka,
input siap difokuskan. Tampilkan status sesi "Belum diuji" lalu "Input diterima"
dan hasil kode; bila ada barcode pembanding, tampilkan Sesuai/Berbeda.

Jangan menulis "scanner terdeteksi/terhubung" dari kecepatan ketik atau input
manual. Tidak perlu memilih model/port, tidak claim perangkat QZ USB/HID,
tidak menangkap keyboard global, dan tidak mengubah scanner POS/pembelian.
Putus QZ tidak boleh menonaktifkan tes scanner keyboard. Hasil sementara tidak
disimpan sebagai master/transaction baru.

## Batas Produk

Tidak mengubah harga, stok/FIFO, transaksi, role/grant, payment atau receipt.
Tidak membuat migration/seeder/package baru. Cetak fisik tetap OFF sampai UAT.
Tidak memasang driver perangkat, CUPS, Bluetooth pairing atau service OS.
Tablet/LAN dan silent signing tetap setup terpisah, bukan diasumsikan selesai
dari pemasangan QZ pada PC ini.
