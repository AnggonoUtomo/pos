# ADR-020: Gateway QZ Untuk Label Melalui Driver OS

Status: Accepted. Tanggal: 21 November 2026.

## Konteks

Pemilik memilih CT420D untuk label dan HC-4208DB untuk scanner, tanpa printer
struk. Gateway harus lintas Windows/Linux tanpa runner PowerShell yang
mengganggu. Manual firmware CT420D belum tersedia untuk memvalidasi TSPL/CPCL.
PLAN TASK-HW-012 disetujui eksplisit, termasuk SDK, QA, schema additive dan
aktivasi aplikasi bersyarat backup terverifikasi.

## Keputusan

- QZ Tray desktop dipasang manual oleh admin. SDK npm qz-tray2.3.0 dipin dan
  dimuat hanya saat diperlukan; aplikasi tidak memasang driver/service OS.
- Koneksi WSS dimulai setelah perintah user, lalu membaca daftar printer OS.
  Default loopback; tablet memerlukan QZ print-server WSS dan trust sertifikat
  LAN yang disiapkan admin. Host dibatasi konfigurasi deployment.
- Jalur awal driver-image: PNG Code128 dari JsBarcode existing, ukuran mm,
  integer-dot/quiet zone dan interpolasi nearest-neighbor. Driver CT420D tetap
  perlu UAT; ini bukan dukungan raw TSPL/CPCL atau ESC/POS.
- Hardware memiliki profil mutable/soft delete serta snapshot job/attempt.
  Single claim/idempotency mencegah dispatch logical ganda; sesudah kemungkinan
  pengiriman, ack hilang menghasilkan uncertain tanpa retry otomatis.
- Submitted bukan bukti fisik. Reprint adalah job baru beralasan/referensi;
  tidak menjanjikan exactly-once fisik. Activitylog bukan print queue.
- ItemLabelCatalog mempublikasikan identitas semua satuan berbarcode, terpisah
  dari SaleItemCatalog. Hardware tidak mengambil model/repository privat Items.
- Scanner diuji melalui karakter+Enter/scan-back, bukan ditebak mereknya dari
  kecepatan ketik atau diasumsikan USB/Bluetooth terhubung.
- Flag print terpisah default OFF; scanner yang sudah ON tidak dimatikan.
  Tidak auto-print checkout. QZ unsigned memakai dialog bawaan; tidak ada
  generic signing endpoint atau private key dalam frontend.

## Konsekuensi

QZ/Java, driver/media, LAN TLS dan scanner nyata memerlukan setup/UAT tersendiri.
QZ tersedia di Linux tidak berarti driver CT420D sudah kompatibel di Linux.
Cetak senyap memerlukan trust/signing terpisah, bukan sertifikat demo produksi.
Blueprint outbound polling tidak dibuat paralel. Struk, drawer/cutter, raw
TSPL/CPCL dan perubahan transaksi tidak termasuk keputusan ini.

Referensi: [QZ pixel dan driver](https://qz.io/docs/pixel),
[signing](https://qz.io/docs/signing), [print-server](https://qz.io/docs/print-server).
