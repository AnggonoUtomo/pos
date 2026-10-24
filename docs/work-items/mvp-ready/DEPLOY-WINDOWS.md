# Operasional Windows Dan Jaringan Toko

Target disetujui: PC Windows/Laragon ini sebagai server, kasir dan tablet pada
LAN toko. Status: persiapan; konfigurasi produksi aktif BELUM diubah.
User mengonfirmasi tablet belum tersedia; akses LAN/TLS client tetap pending.

## Jaringan Dan HTTPS

1. Tetapkan IP LAN server melalui reservasi DHCP router bersama pengelola
   jaringan. Jangan menganggap IP Wi-Fi saat audit akan selalu sama.
2. Gunakan satu hostname, misalnya `pos.test`, pada PC dan tablet. Hosts di
   Windows server saja tidak membuat tablet mengenal hostname; siapkan DNS
   lokal router atau resolver LAN yang benar-benar dipakai tablet.
3. Pastikan sertifikat valid untuk hostname tersebut dan CA terpercaya pada
   setiap perangkat. PC sudah merespons HTTPS 200, trust tablet belum diuji.
   Jangan mengakali warning TLS atau mematikan validasi sertifikat.
4. Batasi firewall Apache ke subnet/perangkat toko pada profil jaringan Private.
   Tidak membuka port MySQL, melakukan port-forward router atau membuka POS
   ke Internet. Jangan menonaktifkan firewall Windows secara keseluruhan.
5. Dari tablet, buka HTTPS/login dan lakukan login/logout. Konfirmasikan
   callback/link/asset tetap di hostname HTTPS yang sama.

## Apache Laragon

Vhost yang ditemukan: `C:/laragon/etc/apache2/sites-enabled/auto.pos.test.conf`.
Saat audit DocumentRoot masih `C:/laragon/www/pos`; sebelum produksi arahkan
HTTP/HTTPS POS ke `C:/laragon/www/pos/public`. Jangan mengubah host project lain.

Simpan backup konfigurasi di luar document root. Gunakan custom vhost yang tidak
ditimpa autogenerator Laragon, tanpa meninggalkan dua vhost POS yang bentrok.
Pada vhost HTTPS, gunakan pola berikut dengan sertifikat existing yang valid:

```apache
DocumentRoot "C:/laragon/www/pos/public"
<Directory "C:/laragon/www/pos/public">
    AllowOverride All
    Options -Indexes
    Require all granted
</Directory>
```

Vhost HTTP harus mengarahkan ke `https://pos.test/` tanpa memakai Host header
bebas sebagai tujuan redirect. Uji sintaks dengan executable Apache Laragon
(`httpd.exe -t`) sebelum reload. Pastikan `/login`, asset dan `/up` berhasil,
sementara `/.env`, `/composer.json` dan `/storage/app/private/` tidak dapat
diakses. Jangan menampilkan isi file sensitif saat pemeriksaan.

## Environment Dan Aktivasi

Lakukan saat toko belum bertransaksi; backup terverifikasi terlebih dahulu.
Jangan menyalin `.env` ke Git. Sesuaikan HANYA setelah HTTPS client siap:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://pos.test
SESSION_SECURE_COOKIE=true
LOG_LEVEL=warning
```

Pertahankan APP_KEY, credential/database, grant dan cookie domain existing yang
valid. Jangan menjalankan `key:generate` pada database yang sudah dipakai;
key hilang/berubah dapat memutus akses data terenkripsi dan session.
Jangan beralih ke SQLite, seed demo, migrate:fresh, atau reset password.

Jalankan build dan cache sesuai [OPERASIONAL.md](OPERASIONAL.md), kemudian
`php artisan pos:readiness --production`. Buka ulang login PC/tablet, transaksi
UAT dan struk. Pastikan tidak membutuhkan `npm run dev` atau VS Code terbuka.
`APP_URL` saja tidak membuktikan redirect/TLS/akses LAN sudah benar.

## Backup Terjadwal

Jadwal harian dipilih di **Operasional**: checkbox aktif/nonaktif dan jam WIB.
Default mati, nilai awal jam21:00, bukan jadwal yang dipaksakan. Pengaturan
disimpan di module Platform/OperationalSettings; tidak mengedit task OS per
perubahan jam. Menonaktifkan mencegah attempt baru, bukan membatalkan dump aktif.

Task `POS-OperationalScheduler` sudah terpasang pada PC ini: setiap menit,
akun Windows interaktif terbatas, WorkingDirectory project, executable absolut,
StartWhenAvailable dan IgnoreNew. User Windows harus tetap login, PC hidup,
MySQL/Apache aktif. Tidak menyimpan password Windows/database di definisi task.
Pada PC baru, dari root project jalankan:

```powershell
powershell.exe -NoProfile -ExecutionPolicy RemoteSigned -File scripts/operations/install-scheduler.ps1
powershell.exe -NoProfile -ExecutionPolicy RemoteSigned -File scripts/operations/install-scheduler.ps1 -Install
```

Baris pertama dry-run. Installer tidak menimpa task bernama sama dengan action
lain. RemoteSigned hanya untuk proses task, bukan mengganti kebijakan global.
Runner memanggil schedule:run; module mendaftarkan pos:backup-due, lalu memanggil
pos:backup existing hanya bila preferensi aktif dan jatuh tempo. Nilai
LastTaskResult=0 membuktikan tick scheduler, BUKAN bukti dump sukses; lihat
status backup terakhir dan keberadaan dump private. Maksimal3 attempt/hari,
jeda10menit setelah gagal; tidak ada dump historis untuk hari PC mati.

- Media eksternal/NAS dan penanggung jawab belum dikonfirmasi; backup terpisah
  belum tersedia hanya karena task lokal berhasil dipasang.
- Tentukan PATH MySQL/mysqldump pada runner, bukan mengandalkan terminal VS Code.
- Cegah overlap; gunakan StartWhenAvailable dan retry terbatas bila MySQL belum
  siap. Eksekusi terlewat tetap bergantung pada PC hidup dan akun task tersedia.
- Jangan memasukkan password database atau password Windows pada command/log.
  Jika task memakai akun interaktif, dokumentasikan kewajiban login Windows.
- Jalankan manual sekali melalui task dan cek LastTaskResult. Setelah backup
  benar-benar diaktifkan, cek status berhasil dan dump nonkosong. Status UI
  tanpa heartbeat baru berarti runner perlu diperiksa, bukan jaminan terpasang.
- Salin backup ke perangkat lain yang terlindungi; satu partisi/drive internal
  di PC sama bukan perlindungan terhadap kerusakan atau ransomware PC.
- Restore verify berkala saat transaksi berhenti, dengan akun admin yang punya
  izin database disposable; jangan memberi CREATE/DROP database ke akun aplikasi
  produksi hanya demi verifikasi. Restore tidak menyentuh database bisnis.
- Jangan menghapus backup lama otomatis sebelum kebijakan retensi dan salinan
  terpisah disetujui. Sertakan konfigurasi/APP_KEY secara aman di luar Git.

## Persetujuan Dan Pemulihan

Lengkapi [UAT operator/printer](UAT-OPERATOR.md). Pemilik menetapkan penanggung
jawab layanan/backup dan persetujuan penggunaan. Pastikan Laragon/MySQL aktif
setelah restart PC; server dan client tidak sleep selama jam toko.

Jika smoke test gagal, hentikan transaksi dan pulihkan konfigurasi vhost/.env
yang baru diubah, bersihkan cache konfigurasi sesuai kebutuhan, lalu uji login.
Jangan rollback data/ledger atau restore backup bisnis tanpa rekonsiliasi.

## Sumber Teknis

- [Laravel 12: deployment](https://laravel.com/docs/12.x/deployment): document
  root public, cache konfigurasi dan debug produksi.
- [Microsoft: pengaturan Scheduled Tasks](https://learn.microsoft.com/en-us/powershell/module/scheduledtasks/new-scheduledtasksettingsset):
  jadwal terlewat, restart dan kebijakan instance bersamaan.
