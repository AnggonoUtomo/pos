# ADR-021: Setup QZ Sekali Dan Kesiapan Scanner Keyboard

Status: Accepted. Tanggal: 21 November 2026.
Menggantikan hanya pilihan pemasangan manual dan koneksi manual di ADR-020;
keputusan driver-image, lifecycle, boundary, auth dan physical gate tetap berlaku.

## Konteks

User ingin setup klien lebih sederhana: QZ disiapkan sekali, kemudian hanya
pasang driver dan konfigurasi printer dari Perangkat POS. Scanner dipilih mode
keyboard langsung scan + tes kesiapan, bukan enumerasi/claim USB/HID.
PLAN TASK-HW-013 disetujui. User kemudian memasang QZ sendiri secara manual;
agent tidak menjalankan installer kedua atau mengubah trust/startup milik user.

## Keputusan

- Setup native tetap tahap deployment OS, bukan HTTP endpoint yang menjalankan
  installer. Sediakan helper Windows x64 sekali-jalan untuk admin dengan pin
  release/SHA256/Authenticode, pemeriksaan existing installation dan privilege.
  Tidak remote-script pipe-to-exec, ExecutionPolicy override atau runner OS.
- QZ memakai tray/startup bawaan dan trust TLS lokal dari installer resminya.
  Tidak membuat scheduled task/service atau mengganti sertifikat situs POS.
  Linux didokumentasikan lewat installer sesuai OS, tanpa klaim runtime PASS.
- Koneksi/discovery satu kali saat mount workspace Hardware bagi pemegang
  manage/print. Tidak socket pada shell/login/POS atau polling/retry otomatis.
  Printer tetap dipilih/disimpan eksplisit; tidak auto-print atau default pertama.
- Scanner keyboard tidak bergantung pada QZ. Tab Scanner fokus input, Enter,
  status sesi Belum diuji/Input diterima dan pembanding barcode. Input manual
  mungkin diterima; tidak menyatakan device/merek terdeteksi dari keystroke.
- Native QZ install, native WSS/printer list dan uji fisik adalah bukti terpisah.
  Unsigned QZ tetap dapat meminta izin; silent signing bukan bagian keputusan.
  Flag print tetap OFF dan driver/media/scan-back fisik tetap perlu UAT.

## Konsekuensi

Klien tetap memasang QZ satu kali pada PC gateway; copy folder Laravel bukan
installer desktop. UI perangkat lebih praktis tetapi dapat menampilkan prompt
QZ pertama kali. Perangkat OS virtual bukan bukti printer nyata siap digunakan.
Autodiscovery harus dibatalkan/diabaikan aman ketika halaman ditutup.

Referensi: [Deployment QZ](https://qz.io/docs/deployment),
[Getting Started](https://qz.io/docs/getting-started),
[release2.3.0](https://github.com/qzind/tray/releases/tag/v2.3.0).
