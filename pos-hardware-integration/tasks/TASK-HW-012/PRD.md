# Kebutuhan: Gateway Label Dan Konfigurasi Hardware

**Status:** Disetujui dan diimplementasikan pada TASK-HW-012; UAT fisik pending.

## Tujuan

Admin dapat memilih printer label yang terpasang, menyimpan ukuran label dan
menguji koneksi. Operator dapat mencetak barcode barang tanpa mengubah stok atau
transaksi. Saat perangkat belum ada, pratinjau dan pengujian software tetap
tersedia tanpa mengaku bahwa cetak fisik sudah berhasil.

## Pengguna Dan Hak Akses

- `platform.hardware.view`: membuka workspace dan riwayat sendiri.
- `platform.hardware.manage`: konfigurasi printer, nonaktif/hapus lunak/restore,
  serta melihat seluruh riwayat hardware.
- `platform.hardware.print`: membuat dan mengirim label sendiri; katalog barang
  juga memerlukan `inventory.view`.
- `platform.hardware.reprint`: membuat cetak ulang dengan alasan wajib;
  memerlukan hak print dan akses terhadap job asal.

Permission baru didaftarkan tanpa otomatis mengubah role/akun existing.
Super-system mengikuti Gate existing, bukan bypass flag atau invariant.
Diagnostik scanner existing tetap berizin inventory.view seperti sebelumnya.

## Alur Penggunaan

1. Admin memasang driver CT420D dan QZ Tray sendiri sesuai OS/perangkat.
2. Buka Perangkat POS, hubungkan QZ, lalu pilih Cari printer. Daftar berasal
   dari OS lewat QZ; bukan daftar model contoh yang ditampilkan sebagai nyata.
3. Simpan profil: nama tampil, nama printer OS, gateway yang diizinkan, ukuran
   label, orientasi dan DPI. Tidak otomatis memilih printer pertama/default OS.
4. Pilih barang dan satuan dengan Autocomplete. Barcode harus sudah tersimpan;
   fitur ini tidak membuat/mengganti barcode master secara diam-diam.
5. Periksa pratinjau, jumlah label dan printer. Kirim hanya lewat perintah user.
6. Tampilkan hasil gateway sebagai Diterima gateway, Gagal sebelum dikirim,
   atau Hasil belum pasti; jangan menulis Berhasil tercetak dari ack software.
7. Jika perlu cetak ulang, periksa hasil fisik terlebih dahulu dan isi alasan.
   Job baru ditautkan ke job asal, tidak mengulang penjualan atau pembayaran.
8. Pada tes scanner, fokus input dan scan kode dengan akhiran Enter. Tampilkan
   teks persis termasuk nol awal. Scan-back dapat dibandingkan dengan barcode
   label yang dipilih, tanpa mengklaim asal input pasti perangkat fisik.

## Rancangan Workspace

Sidebar/command palette menampilkan Perangkat POS hanya jika hardware aktif dan
user berizin. Workspace menggunakan shell/theme existing, dropdown shortcut
dan PageGuide kanan/F1. Pilihan tab vertikal pada desktop, tetap dapat dipakai
tablet tanpa membuat design system baru:

- Printer: status koneksi QZ, Cari printer, daftar hasil kosong/gagal/tersedia,
  profil tersimpan, form ukuran dan perintah Test label yang eksplisit.
- Label: pencarian barang/satuan, jumlah, pratinjau hitam-putih, printer tujuan.
- Riwayat: status, waktu WIB, barang/satuan, jumlah, operator dan alasan reprint;
  pencarian/filter dan TablePagination global.
- Scanner: tes input/Enter dan pembandingan kode; tautan diagnostik barang
  existing tetap tersedia. Tidak mengakses keyboard global atau mengklaim nama
  HC-4208DB diketahui hanya dari keystroke.

Status gateway QZ terhubung tidak sama dengan printer online. Printer ditemukan
di OS tidak sama dengan kertas tersedia atau hasil label dapat dipindai.
Empty state tidak membuat printer dummy aktif. Seeder contoh hanya untuk QA.

## Label Dan Driver

Scope pertama: label identitas nama barang, SKU, satuan dan Code128 dari barcode
tersimpan, tanpa harga jual atau data pelanggan. Semua satuan aktif yang punya
barcode dapat dipilih, termasuk satuan khusus pembelian; contract label berbeda
dari SaleItemCatalog yang memang hanya satuan jual.

Gunakan JsBarcode existing untuk encoding, quiet zone dan renderer PNG pada
resolusi profil, tanpa interpolasi yang mengaburkan batang barcode. Ukuran tidak
boleh mengecilkan barcode di bawah batas aman renderer; tampilkan validasi jika
barcode/nama tidak muat. Default profil50x30 mm,203 DPI; batas awal20-100 mm
lebar,20-150 mm tinggi, salinan1-100. Ini batas aplikasi, bukan sertifikasi ukuran
printable CT420D; kemampuan driver/media sesungguhnya wajib diuji.

QZ mengirim gambar label melalui driver OS. Tidak ada raw ESC/POS, TSPL/CPCL,
cutter, drawer, arbitrary file/URL atau perintah perangkat dari browser/API.
Jika driver CT420D tidak mendukung jalur gambar pada OS tujuan, jangan
mengklaim kompatibel atau diam-diam mengganti protokol; butuh evaluasi tersendiri.
Cetak label browser existing di Barang tetap tersedia dan tidak diubah.

## Penemuan Perangkat Dan Jaringan

QZ SDK dimuat saat user membuka fungsi gateway; koneksi dimulai setelah klik
Hubungkan/Cari printer, bukan polling atau socket pada setiap halaman POS.
Nama/driver/DPI adalah metadata kandidat dari OS, bukan bukti fisik siap pakai.
Tidak memanggil QZ USB/HID/serial/socket/file API atau mengambil data keyboard OS.

Default gateway localhost pada PC yang menjalankan QZ. Tablet tidak menjalankan
QZ desktop: admin harus menyiapkan mode print-server QZ pada PC toko dan WSS
beserta trust sertifikat yang benar. Host hanya dari allowlist deployment,
tidak menerima URL/port bebas dari browser. Jangan melewati warning TLS atau
fallback ke WS/HTTP tanpa enkripsi. Setup LAN/sertifikat/driver tidak otomatis.

## Keamanan Pengiriman

Backend membangun snapshot label dari contract master, bukan mempercayai teks,
harga, raw image atau command dari POST. Endpoint menerima ID barang/satuan,
profil, jumlah dan idempotency key yang tervalidasi.

Snapshot immutable, claim pengiriman satu kali, token attempt yang disimpan
hash, dan locking mencegah dua tab mengambil job yang sama. Token tidak masuk
log, audit, localStorage atau dokumentasi. Snapshot tidak mengandung secret.

Status minimum: queued, sending, submitted, failed_before_send, uncertain.
Ack QZ bukan bukti tinta/kertas keluar. Setelah pemanggilan print dimulai,
timeout/disconnect/reload atau ack hilang diperlakukan konservatif sebagai
uncertain; tidak auto-retry atau otomatis mengklaim ulang lease yang habis.
Pengguna meninjau hasil dan membuat reprint baru beralasan jika diperlukan.

QZ awal memakai dialog izin/cetak bawaannya. Tidak membuat endpoint penandatangan
payload bebas, private key frontend, sertifikat demo production atau bypass
peringatan. Cetak senyap memerlukan keputusan/config trust tersendiri.

## Batas Aktivasi

Flag hardware diagnostik yang sudah ON tidak dimatikan. Tambahkan flag print
terpisah default OFF agar deploy baru tidak langsung membuka pengiriman printer.
Konfigurasi/pratinjau dapat dipakai ketika print OFF; create/claim job ditolak
di backend. Tidak ada auto-print sesudah checkout atau perubahan scheduler.

Approval implementasi mencakup QA virtual, bukan memasang QZ/driver, membuka
port/firewall, mencetak fisik atau mengaktifkan pilot printer production.
Status akhir software dan hardware dilaporkan terpisah.

## Referensi Primer

- [QZ Getting Started](https://qz.io/docs/getting-started).
- [QZ daftar printer](https://qz.io/api/qz.printers).
- [QZ pixel/image printing dan batas driver](https://qz.io/docs/pixel).
- [QZ trust/signing](https://qz.io/docs/signing).
- [QZ print-server](https://qz.io/docs/print-server).
- [QZ lisensi dan cetak senyap](https://qz.io/docs/faq).
- [Batas keyboard pada WebHID](https://developer.chrome.com/docs/capabilities/hid).

Metadata registry npm dibaca tanpa install: qz-tray2.3.0, lisensi LGPL-2.1,
21 November2026. Instalasi SDK baru dilakukan setelah approval exact PLAN.
