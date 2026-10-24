# Menjalankan MVP Toko

## Aktivasi Dan Backup

1. Start All Laragon, pastikan MySQL dan virtual host pos.test berjalan.
2. Hentikan transaksi sementara, lalu jalankan `php artisan pos:backup --verify`.
   Dump disimpan pada storage/app/private/backups (diabaikan Git), dipulihkan
   ke database pos_restore_qa_ acak, dibandingkan fingerprint dan dibersihkan.
   Database toko tidak ditimpa. Tool mysql/mysqldump harus tersedia di PATH.
3. Jalankan migration additive dan katalog permission (tanpa grant otomatis):

Untuk workspace local/MySQL ini, cukup jalankan
`php scripts/qa/activate-mvp-local.php` sesudah backup terverifikasi. Script
membatasi lima migration MVP dan menjaga fingerprint data/grant existing.
Rincian command untuk deployment manual:

```powershell
php artisan migrate --force
php artisan db:seed --class="App\Modules\Inventory\Stock\Database\Seeders\StockAccessSeeder"
php artisan db:seed --class="App\Modules\Inventory\Transfers\Database\Seeders\TransfersAccessSeeder"
php artisan db:seed --class="App\Modules\Platform\CompanySettings\Database\Seeders\CompanySettingsAccessSeeder"
php artisan db:seed --class="App\Modules\Reporting\OperationalReports\Database\Seeders\OperationalReportsAccessSeeder"
php artisan permission:cache-reset
npm run build
php artisan pos:readiness
```

Jangan migrate:fresh, mereset password, menjalankan demo posted otomatis, atau
mengubah grant existing. Super-system memakai bypass existing; role operator
diatur admin melalui Identitas & Akses sesuai kebutuhan toko.

Salin backup ke penyimpanan terpisah dengan akses terbatas dan enkripsi. Jadwalkan
`php artisan pos:backup` harian melalui scheduler OS; verifikasi restore berkala
saat tidak ada transaksi. Backup database belum mencakup .env/APP_KEY, file
storage bisnis atau konfigurasi printer: simpan semuanya secara aman terpisah,
bukan di Git atau dokumentasi. Tidak ada upload/retention otomatis pada fase ini.

## Konfigurasi Deploy

Untuk server produksi: arahkan document root ke public, gunakan HTTPS, set
APP_ENV=production, APP_DEBUG=false, APP_URL sesuai host dan SESSION_SECURE_COOKIE=true.
Gunakan APP_KEY unik, credential DB khusus, izin tulis hanya storage/bootstrap/cache,
dan batasi akses backup/database. Jangan menyimpan nilai secret dalam laporan.
Build asset harus tersedia dan public/hot tidak boleh menunjuk server Vite mati.
Jalankan `php artisan config:cache`, `php artisan route:cache`, lalu
`php artisan pos:readiness --production`. Jangan mengganti konfigurasi lokal
menjadi production otomatis. Jika konfigurasi berubah, ulang hanya gate terkait.

## Alur Operator

- Isi Pengaturan toko: nama/alamat/kontak/catatan struk aktual.
- Cek satuan, konversi, gudang, barang, seluruh harga level/satuan dan metode bayar.
- Gunakan pembelian draft -> Terima dan bayar: total metode harus tepat.
  Kegagalan payment membatalkan receipt/FIFO; gunakan Bayar untuk dokumen lama
  yang sudah diterima tetapi belum memiliki payment, tanpa menganggapnya lunas.
- POS: pilih gudang/pelanggan, scan/cari barang, kuantitas format Indonesia,
  diskon nota dan pajak eksplisit, periksa keranjang, bayar/print. Satu tunai
  boleh lebih bayar; kembalian hanya dari tunai. Status ambigu harus retry operasi
  yang sama, jangan membuat pembayaran ulang dengan kunci baru.
- Retur penjualan: pilih nota/baris/kuantitas -> terima barang layak jual -> refund.
  Retur supplier: pilih pembelian -> posting -> terima uang untuk pembelian lunas.
- Transfer: draft -> posting satu langkah; koreksi fisik memakai Koreksi stok
  dengan alasan, tidak mengedit saldo/ledger posted.
- Cocokkan laporan: transaksi per periode WIB/gudang; stok posisi terkini;
  pembayaran seluruh gudang adalah arus, bukan saldo bank atau laba bersih.

## UAT Pemilik Toko

- [ ] Uji satu pembelian dan penjualan nyata skala kecil, termasuk kombinasi bayar.
- [ ] Cocokkan struk dengan printer nyata (lebar kertas, dialog cetak, koneksi).
- [ ] Cocokkan hitung fisik barang dan konversi kabel per meter/rol serta kamera.
- [ ] Uji retur/refund dan laporan akhir hari bersama operator berizin.
- [ ] Tetapkan jadwal, media backup terpisah, penanggung jawab dan uji restore.
- [ ] Pemilik menyetujui mulai operasional; data demo tidak tercampur data toko.

Checklist ini tidak boleh diisi otomatis oleh agent. QA automated bukan
persetujuan operator atau bukti printer/perangkat fisik sudah diuji.

## Pemulihan

Jika rilis bermasalah, hentikan transaksi, buat backup terbaru, simpan bukti
invoice/payment, lalu gunakan commit build yang kompatibel dengan schema.
Jangan rollback migration berisi ledger posted atau menghapus baris posted.
Restore database bisnis hanya melalui prosedur admin berizin setelah seluruh
transaksi sejak backup direkonsiliasi; command --verify tidak melakukan restore
ke database bisnis dan bukan tombol reset toko.
