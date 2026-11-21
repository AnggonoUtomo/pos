# Setup Awal Perangkat POS

QZ disiapkan sekali pada PC yang terhubung ke printer. Setelah itu pengaturan
printer/label dilakukan dari **Perangkat POS**, bukan mengubah kode Laravel.
Menyalin project ke PC klien baru tidak ikut memasang aplikasi QZ desktop.

## 1. PC Windows

1. Pasang [QZ Tray resmi](https://qz.io/download/) dan jalankan sampai ikonnya
   muncul di system tray. QZ mempunyai startup login bawaan; tidak perlu
   membuat scheduled task, service atau runner PowerShell berulang.
2. Installer resmi menyiapkan runtime dan trust TLS lokal QZ. Bila Windows
   meminta UAC, admin PC mengonfirmasi sendiri. Jangan melewati warning TLS,
   mengganti sertifikat pos.test atau mematikan pengaman browser.
3. Bila QZ sudah ada, jangan menjalankan installer kedua bersamaan. Untuk
   instalasi toko yang sudah berjalan, periksa versi sebelum upgrade.
4. Admin dapat memakai setup sekali-jalan project untuk PC Windows x64 baru.
   Dari root POS, pemeriksaan saja (tanpa download/install):

```powershell
& .\scripts\operations\setup-qz-windows.ps1
```

Pemasangan eksplisit, dari PowerShell yang dibuka admin:

```powershell
& .\scripts\operations\setup-qz-windows.ps1 -Install
```

Script memeriksa existing installation/proses, arsitektur dan hak admin sebelum
download. Asset dipin ke QZ2.3.0+1 Windows x64, diverifikasi SHA256 dan tanda
tangan publisher QZ. Hash/signature/izin salah berarti berhenti. Script tidak
mengubah ExecutionPolicy, membuat service/task, memasang driver atau menyalakan
print. Jangan menurunkan kebijakan keamanan bila script diblokir; gunakan
installer resmi melalui admin. Installer/log privat tidak masuk Git.

Paket rujukan: [release QZ2.3.0](https://github.com/qzind/tray/releases/tag/v2.3.0).
Windows x64 SHA256:
01a8ae33da31c78f386ef8335024723f2dd2cb7d8bc44ce33079bf6bc0360011.

## 2. Hubungkan Printer

1. Pasang driver printer sesuai model dan OS melalui admin. CT420D adalah
   printer label, bukan printer struk ESC/POS. Driver tidak dipasang dari UI POS.
2. Buka **Perangkat POS** memakai akun berizin. Pada halaman ini aplikasi
   mencoba menghubungkan QZ dan membaca daftar printer sekali secara otomatis.
3. Bila QZ menampilkan permintaan izin dari situs POS yang kamu buka, periksa
   situsnya lalu izinkan koneksi. Jangan mengizinkan situs yang tidak dikenal.
   Aplikasi belum memakai silent signing; prompt QZ tetap dapat muncul.
4. **Gateway terhubung** berarti koneksi ke QZ berhasil, bukan printer siap.
   Daftar OS dapat memuat Microsoft Print to PDF; jangan memilihnya sebagai
   CT420D. Bila driver/perangkat baru dipasang, gunakan **Deteksi ulang**.
5. Klik **Tambah profil**. Isi nama profil, pilih nama printer OS yang benar,
   gateway localhost untuk PC ini, ukuran label aktual, orientasi dan203 DPI.
6. Simpan profil, lalu buka **Label** untuk memilih barang/satuan, jumlah dan
   pratinjau. Tidak ada pemilihan profil atau cetak otomatis.

Jika tidak ada printer di daftar, periksa driver/antrean printer di OS lalu
deteksi ulang. Tidak perlu reset database, install QZ ulang atau membuat
printer dummy. Cetak fisik tetap nonaktif sampai perangkat dan UAT disetujui.
Lanjutan: [Printer label](PRINTER-LABEL.md).

## 3. Scanner Keyboard

1. Hubungkan scanner USB/dongle, atau pairing Bluetooth melalui OS sesuai
   perangkat. Pilih mode keyboard dan akhiran Enter menurut manual perangkat.
2. Tidak perlu memilih scanner atau memasukkannya ke QZ. Buka tab **Scanner**;
   input otomatis mendapat fokus. **Belum diuji** adalah status sesi awal.
3. Scan satu barcode. **Input diterima** berarti aplikasi menerima kode.
   Jika barcode pembanding dipilih pada Label, periksa **Sesuai/Berbeda** dan
   nol awalnya. Ketikan manual juga diterima, bukan bukti identitas hardware.
4. Scanner keyboard tetap dapat dipakai ketika QZ mati. Pada POS/pembelian,
   gunakan input scan yang sudah ada; tidak ada penangkapan keyboard global.

Tidak membuat aturan penentuan merek scanner berdasarkan kecepatan ketik.
Uji USB/Bluetooth/dongle, kode dan satuan fisik tetap dilakukan saat perangkat
tersedia. Diagnostik: [Uji scanner](UJI-SCANNER.md).

## 4. Klien Linux Dan Tablet

QZ tersedia untuk Linux, tetapi driver CT420D dan media fisik pada Linux tetap
harus diuji. Panduan ini tidak menyatakan runtime Linux sudah diverifikasi.
Admin mengambil installer sesuai arsitektur dari release resmi, memeriksa hash
asset, kemudian mengikuti [deployment resmi](https://qz.io/docs/deployment).
Untuk x86_64 QZ2.3.0+1 gunakan asset .run, bukan installer Windows .exe.

Linux x86_64 asset SHA256:
7860322099e83b36a3d6db59a5c1a406dfb8749b15ac78c1f67744dd207e9bde.
Admin menentukan dependensi OS/CUPS bila dibutuhkan; aplikasi tidak memasangnya
atau menambah service/cron secara otomatis. Setelah QZ dan driver siap,
pengaturan dari Perangkat POS mengikuti alur yang sama.

Pada tablet, localhost menunjuk tablet, bukan PC printer. Setup print-server
WSS/allowlist/trust dan LAN harus ditangani admin tersendiri. Jangan membuka
port atau menurunkan TLS hanya agar tombol terlihat berhasil.
