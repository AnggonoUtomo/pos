# Panduan Setup POS Standalone Windows

## Status Saat Ini

Ini panduan persiapan untuk admin instalasi, bukan pemberitahuan bahwa installer
sudah siap dibagikan. Paket installer Windows lengkap, wizard WebView2,
service, HTTPS/LAN dan uji Windows bersih masih belum selesai. Aplikasi toko
yang sekarang tetap berjalan memakai konfigurasi existing.

MySQL dipasang manual oleh admin. Installer POS tidak akan membawa server,
mysql atau mysqldump, mengelola service MySQL, atau memasang Laragon.

## Urutan Instalasi Yang Dituju

1. Admin menyiapkan Windows yang didukung dan memasang MySQL secara manual.
2. Admin menyediakan database khusus yang baru/kosong serta akun aplikasi dan
   maintenance yang sesuai. Jangan menggunakan database toko/developer lama.
3. Admin menyediakan `mysql` dan `mysqldump` dari instalasi manual, dengan versi
   yang sesuai. Keduanya diperlukan untuk backup dan verifikasi pemulihan.
4. Installer nantinya memeriksa runtime, koneksi/hak database dan lokasi data.
   Pemeriksaan koneksi ini belum tersedia pada preflight sekarang.
5. Proses setup menjalankan migration dan katalog permission/role produksi
   pada database baru, tanpa demo, akun default atau transaksi latihan.
6. Pemilik mengisi nama, email, password awal dan konfirmasinya. Setelah sukses,
   gunakan akun tersebut pada halaman login existing.
7. Lengkapi identitas toko, master, kas, harga, stok dan shift melalui panduan
   operasional sebelum menerima transaksi toko.

Jangan menjalankan `migrate:fresh`, `DatabaseSeeder`, atau seeder demo untuk
menyiapkan akun pemilik. Jangan mengarahkan setup ke database produksi lama.

## Lokasi Data Standalone

Rancangan default memakai binary di Program Files dan data privat di
`C:\ProgramData\POS`. Lokasi itu belum dibuat pada PC ini. Admin/installer
menyiapkan direktori dengan izin akses yang sesuai sebelum aplikasi dimulai:

```text
<data-root>/
  config/.env
  cache/<release-id>/
  storage/app/private/
  storage/app/public/
  storage/framework/cache/data/
  storage/framework/sessions/
  storage/framework/views/
  storage/logs/
  backups/
```

Env, key, backup dan direktori private tidak boleh dipublikasikan web atau
dibagikan kepada kasir. Hanya `storage/app/public` yang boleh dipetakan sebagai
media publik oleh konfigurasi server yang nanti diverifikasi.

## Mode Standalone

Proses deployment tepercaya mengisi tiga environment variable berikut:

- `POS_STANDALONE=1`: memilih mode standalone sebelum `.env` dibaca.
- `POS_DATA_ROOT`: path lokal absolut, di luar direktori binary aplikasi;
  boleh mengandung spasi. Folder config/storage/cache harus sudah tersedia.
- `POS_RELEASE_ID`: identifier rilis 1-64 karakter, huruf/angka, `_` atau `-`.

Selector mode tidak bisa dinyalakan hanya dari `.env` atau UI. Cache config,
route, event, service dan package dipisahkan per rilis, bukan memakai cache
instalasi toko. Bila mode tidak dipilih, aplikasi tetap menggunakan path existing.

Pada environment privat standalone, `POS_MYSQL_PATH` dan `POS_MYSQLDUMP_PATH`
berisi path absolut executable manual. Tidak perlu mengubah PATH global.
Backup masuk `<data-root>/backups`; pesan command tetap menampilkan
`backups/<nama-file>` untuk kompatibilitas halaman Operasional. Mode existing
tetap memakai folder `backups` pada root project dan pencarian tool existing.

## Pembuatan Pemilik Pertama

Command berikut hanya untuk proses setup pada instalasi standalone baru yang
sudah dimigrasikan dan memiliki role produksi `super-system` guard `web`:

```text
php artisan identity:initialize-owner
```

Isi nama dan email; password serta konfirmasi memakai prompt tersembunyi.
Password mengikuti batas akun internal: minimal 12 karakter dan maksimal
72 byte. Jangan memasukkan password sebagai argumen command atau menyimpannya
di riwayat terminal.
Jika terminal tidak mendukung input tersembunyi, setup ditolak; password tidak
dialihkan ke prompt yang terlihat.

Wizard lokal nantinya dapat memakai `--stdin` untuk satu objek JSON profil
melalui pipe privat. Batas input 8 KiB; field yang diterima hanya `name`,
`email`, `password` dan `password_confirmation`. Jangan menaruh JSON berisi
password pada command line, Git, screenshot, log, atau dokumen pelatihan.

Setup tidak memberikan akun/password baku dan tidak mengubah akun existing.
Database yang berisi akun, master, transaksi, perubahan pengaturan atau audit
operasional ditolak. Katalog akses, audit pembuatan role produksi dan default
pengaturan dari migration bukan transaksi latihan. Dua proses setup tidak
boleh menghasilkan dua pemilik. Akun, assignment role dan audit disimpan
bersama; kegagalan audit membatalkan perubahan.

Jika setup terinterupsi setelah akun berhasil dibuat, coba login dengan akun
yang tadi diisi. Mengulang command tidak mereset password. Bila tidak yakin
statusnya, hubungi admin; jangan menghapus akun/audit atau melakukan seed ulang.

## Backup Dan Pemeriksaan Akhir

Gunakan tombol **Backup sekarang** pada Operasional setelah instalasi siap.
Admin maintenance dapat menjalankan `pos:backup --verify` pada lingkungan uji
dengan hak database disposable yang sesuai. Jangan memberi akun web hak global
CREATE/DROP hanya agar verifikasi terlihat berhasil.

Backup SQL bukan seluruh pemulihan aplikasi: APP_KEY, config privat dan gambar
bisnis juga perlu dipulihkan secara aman. Salinan pada PC yang sama tidak
melindungi dari kerusakan PC. Uji operator, printer fisik, tablet LAN dan salinan
terpisah tetap mengikuti penerimaan operasional, bukan otomatis lulus karena
command setup atau backup berhasil.

Status pengembangan dan bukti: [checklist installer](../work-items/windows-standalone-installer/tasks.md).
