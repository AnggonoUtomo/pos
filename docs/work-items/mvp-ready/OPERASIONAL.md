# Menjalankan MVP Toko

Target penggunaan: PC Windows/Laragon dan tablet pada jaringan toko.
Panduan spesifik [Windows/LAN](DEPLOY-WINDOWS.md) dan
[lembar UAT operator/printer](UAT-OPERATOR.md) melengkapi dokumen ini.

## Aktivasi Dan Backup

1. Start All Laragon, pastikan MySQL dan virtual host pos.test berjalan.
2. Hentikan transaksi sementara, lalu jalankan `php artisan pos:backup --verify`.
   Dump baru disimpan pada backups di root project (diabaikan Git), dipulihkan
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
backup opsional dari halaman Operasional; runner OS mengikuti
[pengaturan scheduler](#scheduler-lintas-os). Verifikasi restore berkala
saat tidak ada transaksi. Backup database belum mencakup .env/APP_KEY, file
storage bisnis atau konfigurasi printer: simpan semuanya secara aman terpisah,
bukan di Git atau dokumentasi. Tidak ada upload/retention otomatis pada fase ini.

## Scheduler Lintas OS

Seluruh jadwal pekerjaan dikelola Laravel Scheduler. UI hanya menyimpan opsi
aktif/nonaktif dan jam WIB; jam boleh disiapkan saat backup nonaktif. Tidak ada
installer cron, service, atau task PowerShell otomatis dari aplikasi.

Admin memasang SATU pemicu setiap menit di server aplikasi, bukan di setiap
komputer kasir. Untuk Linux, contoh pada crontab user aplikasi:

```cron
* * * * * cd /var/www/pos && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

Sesuaikan path deployment/PHP dan PATH mysql/mysqldump pada environment cron;
jangan menyisipkan credential pada crontab. Untuk Windows, admin mengatur
pemicu noninteraktif yang menjalankan command Laravel yang sama, menggunakan
path PHP absolut dan working directory project. Bukan console PowerShell yang
dibuka berulang pada desktop kasir. Aplikasi tidak mengubah scheduler OS.

Verifikasi admin: `php artisan schedule:list`, tick `schedule:run`, heartbeat
dan hasil backup pada UI. Tick yang berhasil bukan bukti dump/restore berhasil.
Opsi aktif di UI tidak otomatis memasang atau membuktikan pemicu OS tersedia.
Panduan Windows/LAN berada di [DEPLOY-WINDOWS.md](DEPLOY-WINDOWS.md).

Lihat [Laravel 12: scheduler](https://laravel.com/docs/12.x/scheduling#running-the-scheduler).

## Konfigurasi Produksi

Untuk server produksi: arahkan document root ke public, gunakan HTTPS, set
APP_ENV=production, APP_DEBUG=false, APP_URL sesuai host dan SESSION_SECURE_COOKIE=true.
Pertahankan APP_KEY existing; jangan regenerate key saat database sudah dipakai.
Gunakan credential DB khusus, izin tulis hanya storage/bootstrap/cache,
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

## Status PC Toko25Oktober2026

Konfigurasi produksi PC sudah aktif dan pos:readiness --production PASS.
Gunakan https://pos.test; HTTP otomatis redirect. APP_KEY/DB_* dipertahankan.
Backup manual dapat diminta melalui **Operasional > Backup sekarang** oleh
pemegang manage, dengan konfirmasi/audit. Command manual tetap `php artisan
pos:backup`, verifikasi restore memakai `php artisan pos:backup --verify`.
Hasil baru ada pada `C:/laragon/www/pos/backups`, bukan folder public atau URL
download. File lama pada storage/app/private/backups dipertahankan.

Jadwal otomatis lokal disimpan aktif22:00WIB atas arahan user. Tidak ada
pemicu OS yang dipasang agent; admin masih harus menghubungkan Laravel
Scheduler agar jadwal benar-benar dieksekusi. Folder backups di PC yang sama
bukan salinan terpisah. Printer fisik belum tersedia dan UAT operator belum
disahkan; aktivasi konfigurasi bukan persetujuan go-live.
