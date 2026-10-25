# Operasional Windows Dan Jaringan Toko

Target disetujui: PC Windows/Laragon ini sebagai server, kasir dan tablet pada
LAN toko. Status25Oktober2026: konfigurasi produksi aktif pada PC, readiness
production PASS. User mengonfirmasi tablet belum tersedia; akses LAN/TLS client
tetap pending, bukan dianggap lulus berdasarkan pemeriksaan PC.

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

Vhost aktif: `C:/laragon/etc/apache2/sites-enabled/pos.test.conf`, dengan
DocumentRoot `C:/laragon/www/pos/public` dan HTTP301 ke hostname HTTPS tetap.
Template terverifikasi: [apache-pos.conf](apache-pos.conf). File auto sebelumnya
dipindah ke rollback private agar tidak ada dua vhost POS. Tidak mengubah host
project lain. Nama custom tanpa auto. dipertahankan Laragon saat reload.

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
(`httpd.exe -t`) sebelum reload. Apache ini bukan service: gunakan Reload dari
Laragon, bukan memasang service baru. Pastikan `/login`, asset dan `/up` berhasil,
sementara `/.env`, `/composer.json` dan `/storage/app/private/` tidak dapat
diakses. Jangan menampilkan isi file sensitif saat pemeriksaan.

## Environment Dan Aktivasi

Lakukan saat toko belum bertransaksi; backup terverifikasi terlebih dahulu.
Jangan menyalin `.env` ke Git. Nilai berikut sudah aktif/terverifikasi di PC;
setiap client LAN masih wajib menguji trust HTTPS sebelum dipakai:

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

Task `POS-OperationalScheduler` buatan agent sudah dicabut karena menampilkan
console berulang dan tidak sesuai kebutuhan user. Installer dan runner
PowerShell otomatis dihapus; tidak dipasang runner/service pengganti.

Kode aplikasi memakai Laravel Scheduler yang sama dengan Linux. Admin server
menyiapkan pemanggilan `php artisan schedule:run` tiap menit melalui penjadwal
OS secara noninteraktif dengan executable PHP absolut, working directory
project dan environment MySQL yang benar. Tidak ada instalasi task dari UI.
Untuk Linux gunakan [contoh cron](OPERASIONAL.md#scheduler-lintas-os).

Tanpa pemicu yang disiapkan admin, opsi jadwal tetap bisa disimpan tetapi backup
otomatis belum dieksekusi. Periksa heartbeat serta status backup pada UI;
LastTaskResult=0 hanya membuktikan tick penjadwal, bukan dump sukses.
Maksimal3 attempt/hari, jeda10menit setelah gagal; hari PC mati tidak dibuatkan
dump historis palsu.

- Media eksternal/NAS dan penanggung jawab belum dikonfirmasi; backup terpisah
  belum tersedia hanya karena task lokal berhasil dipasang.
- Admin menentukan PATH MySQL/mysqldump pada environment scheduler, bukan
  mengandalkan terminal VS Code.
- Cegah overlap dan pastikan pemicu berjalan saat server aktif. Eksekusi
  terlewat tetap bergantung pada PC hidup dan akun penjadwal tersedia.
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

Preferensi lokal saat ini: aktif22:00WIB sesuai user. Tombol Backup sekarang
tersedia untuk manage dengan audit; hasil root project `backups/`, bukan
unduhan publik. Directory dan file SQL diabaikan Git. Dump lama private tidak
dihapus/dipindahkan. Pemicu periodik admin belum tersedia; menyimpan jadwal
bukan bukti backup otomatis sudah bekerja. Root folder lokal bukan off-device.

## Persetujuan Dan Pemulihan

Lengkapi [UAT operator/printer](UAT-OPERATOR.md). Pemilik menetapkan penanggung
jawab layanan/backup dan persetujuan penggunaan. Pastikan Laragon/MySQL aktif
setelah restart PC; server dan client tidak sleep selama jam toko.

Jika smoke test gagal, hentikan transaksi dan pulihkan konfigurasi vhost/.env
yang baru diubah, bersihkan cache konfigurasi sesuai kebutuhan, lalu uji login.
Jangan rollback data/ledger atau restore backup bisnis tanpa rekonsiliasi.

Rollback lokal sebelum aktivasi ada di storage/app/private/deployment/
20261025-production (ignored, bukan web). Jangan commit/cetak isi .env.before.
Pulihkan hanya konfigurasi yang relevan, validasi Apache, reload, lalu bangun
ulang cache konfigurasi/route; jangan menimpa database atau APP_KEY baru.

## Sumber Teknis

- [Laravel 12: deployment](https://laravel.com/docs/12.x/deployment): document
  root public, cache konfigurasi dan debug produksi.
- [Laragon: custom vhost](https://laragon.org/docs/pretty-urls): hapus prefix
  auto. agar konfigurasi custom tidak ditimpa.
- [Apache Windows](https://httpd.apache.org/docs/2.4/platform/windows.html):
  perbedaan pengendalian proses console dan service.
- [Microsoft: pengaturan Scheduled Tasks](https://learn.microsoft.com/en-us/powershell/module/scheduledtasks/new-scheduledtasksettingsset):
  jadwal terlewat, restart dan kebijakan instance bersamaan.
