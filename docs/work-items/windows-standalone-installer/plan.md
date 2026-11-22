# PLAN: Installer POS Windows Standalone

## Status Dan Batas Izin

Disetujui user untuk mulai Increment 1, 22 November 2026. Persetujuan mencakup
tooling build privat, staging dan preflight; bukan pemasangan service atau
perubahan database/OS PC toko sekarang. Scope mengikuti [PRD](PRD.md) dan
[README](README.md). Increment berikutnya tetap mengikuti batas PLAN ini.

User memilih distribusi aplikasi tertutup dengan MySQL Community gratis.
Kebijakan OEM Oracle belum mendukung asumsi izin bundel tersebut. Sampai ada
bukti izin redistribusi yang sesuai, komponen MySQL dan EXE distribusi penuh
berstatus BLOCKED_LICENSE; tidak membeli lisensi atau mengganti engine.
Payload source aplikasi dan alat preflight internal dapat diverifikasi terpisah.

## Usulan Komponen

| Komponen | Kandidat | Batas |
| --- | --- | --- |
| Installer | Inno Setup, satu EXE x64 offline | Kunci versi dan ketentuan komersial sebelum build |
| Desktop/setup helper | C# WinForms, .NET 10 LTS self-contained win-x64 | Tidak butuh .NET SDK/runtime global klien; cek dukungan edisi Windows |
| WebView2 | Evergreen Runtime dengan offline Standalone Installer | Deteksi existing, tanpa downgrade; tetap perlu strategi patch keamanan |
| Webserver | Apache 2.4 x64, kandidat binary Apache Lounge | ASF merilis source, bukan binary Windows; sumber binary perlu diverifikasi |
| PHP | PHP 8.4 x64 Thread Safe sebagai module Apache | Cocokkan ABI/compiler/redist; extension berdasar lockfile/fitur nyata |
| MySQL | Community 8.4 LTS ZIP x64 privat | Database baru; keputusan redistribusi wajib tuntas |
| Redist | Visual C++ x64 yang cocok | Paket Microsoft terverifikasi; tidak downgrade/hapus shared runtime |
| Scheduler | Helper service .NET menjalankan `artisan schedule:run` | Opsional, single-instance, tanpa PowerShell/console berkala |
| QZ Tray | Komponen vendor offline opsional | Cek versi/hash/signature/lisensi; bukan aktivasi physical print |

Patch version, URL, SHA256, signature/publisher, dukungan OS dan lisensi dikunci
pada manifest Increment 1. Tidak memakai moving latest untuk rilis bernomor
sama. Node/npm, Composer, SDK .NET dan compiler hanya untuk build di staging.
Tidak menambah Redis, phpMyAdmin, Docker, SSR/queue worker tanpa consumer nyata,
service wrapper pihak ketiga, atau driver printer semua merek.

## Gate Keputusan Sebelum Implementasi

1. Terima/revisi komponen dan [ADR-022](../../decisions/ADR-022-INSTALLER-WINDOWS-STANDALONE-WEBVIEW2.md).
2. Windows 11 x64 kandidat utama. Windows 10 dinilai per edisi/lifecycle dan
   dukungan seluruh runtime, bukan berdasarkan berjalan di PC developer.
   OS yang tidak memenuhi kebijakan dukungan menjadi blocker rilis untuk OS itu.
3. Review lisensi redistribusi MySQL, Apache/PHP, redist, .NET, WebView2, QZ
   dan compiler installer. MIT pada composer.json bukan izin semua bundel.
   Bila ada kebutuhan lisensi komersial, minta keputusan pemilik. Jangan
   membeli, mengganti database, atau mengganti lisensi POS sendiri.
4. Terima pengecualian terbatas aturan OS: installer yang dijalankan admin boleh
   memasang komponen yang dipilih. Laravel/HTTP tetap dilarang memasang OS service.
   Tidak memberi izin install paket/service/cert/firewall di PC toko sekarang.
5. Terima rancangan CA lokal per instalasi dan onboarding trust klien LAN tanpa
   bypass TLS. Code-signing memerlukan publisher/credential milik pemilik;
   tanpa signing, catat risiko SmartScreen, jangan mematikan proteksi Windows.
6. Sediakan Windows disposable/VM bersih untuk uji install/reboot. Tidak ada
   VM/tooling berarti QA pending, bukan PASS. Build tool yang belum tersedia
   tidak otomatis dipasang global hanya karena dotnet.exe ditemukan.

## Data Dan Payload

```text
C:/Program Files/POS/
  desktop/                 WebView2 non-administrator
  tools/                   setup/maintenance, scheduler host
  runtime/                 Apache/PHP/MySQL dan .NET privat
  releases/<release-id>/   Laravel, vendor produksi, asset build
C:/ProgramData/POS/
  config/                  env dan konfigurasi privat
  database/                datadir MySQL
  storage/                 media, cache aplikasi, session, log
  cache/<release-id>/      cache bootstrap/config/route per rilis
  backups/                 backup privat
  certificates/            key/cert per instalasi
  logs/                    log instalasi/service yang disanitasi
%LOCALAPPDATA%/POS/WebView2/ profil browser per user Windows
```

Ini default usulan, belum dibuat. ProgramData tidak writable/readable untuk
Everyone: ACL dipisahkan per fungsi; kasir tidak membaca env/key/DB/backup.
Desktop hanya membaca metadata publik nonsecret URL/status/versi. Service web
bukan LocalSystem; kontrol server/maintenance memerlukan admin.

Build dari source commit dan lockfile di staging bersih, bukan copy workspace.
Allowlist payload menolak `.env*`, key, SQL, storage/media/backup toko, LSP,
hardware-drivers, config/route cache produksi, public/hot, .git, node_modules,
tests, Old-docs dan companion hardware. Vendor produksi dan asset dibangun ulang
di staging, tanpa membersihkan cache/hot instalasi produksi.

Mode standalone hanya dari environment proses deployment terpercaya, bukan
header/request. Gunakan override env/storage/cache Laravel yang tersedia;
tanpa mode itu bootstrap existing identik. Backup configurable ke data root,
fallback non-standalone tetap `base_path('backups')`. Format basename output
tetap kompatibel dengan adapter ArtisanBackupExecutor, tanpa adapter baru.

## Alur Instalasi Baru

1. Validasi payload, OS, disk, admin, path/port dan ownership instalasi. Gagal
   preflight berarti tidak mutasi. Tanpa ExecutionPolicy override atau pipe-to-exec.
2. Tampilkan opsi server otomatis, LAN, pemicu Scheduler, dan QZ. Pilihan
   backup aktif/jam tetap dari UI Laravel; tidak menyalin jadwal 22.00 toko ini.
3. Buat installation ID, ACL, APP_KEY dan credential acak per instalasi. Tidak
   menyisipkan secret pada command line, log, repository atau installer rilis.
4. Inisialisasi datadir hanya bila baru dan milik instalasi ini. MySQL bind
   loopback; akun web bukan root/global admin. Hak migration/maintenance terpisah.
5. Migration hanya DB baru terverifikasi; tanpa migrate:fresh, DROP existing,
   import dump toko, DatabaseSeeder atau demo/lifecycle seeder.
6. Manifest menjalankan hanya seeder katalog permission aman per module lalu
   role produksi. Uji tidak membuat akun/master/transaksi atau menimpa grant.
7. Wizard lokal meminta nama/email/password/konfirmasi. Usulan akun pertama
   pemilik/admin instalasi ber-role `super-system`; owner-supervisor tetap
   profil tugas existing. Tidak membuat alias super-admin atau mengubah matriksnya.
8. CLI milik Identity menerima input melalui stdin/pipe terbatas, bukan route
   HTTP atau argumen password. Action -> port -> adapter, validation/hash,
   transaction/lock dan audit tanpa secret. Tidak mengimpor model privat modul lain.
9. Menolak bootstrap pada DB berisi akun/data toko. Dua setup bersamaan hanya
   menghasilkan satu owner. Retry tidak mereset credential/APP_KEY; interupsi
   sesudah commit dideteksi, bukan diatasi dengan seed/reset. Marker file bukan
   satu-satunya pengaman. Bootstrap tertutup setelah setup, tanpa registrasi publik.
10. LAN belum dibuka sebelum setup selesai. Bangun cache rilis dan cek login/TLS.
    Buka desktop non-admin ke login. Identitas toko diisi di UI existing;
    readiness produksi boleh tetap pending sampai operator melengkapinya.
    Jangan melonggarkan `pos:readiness --production` agar wizard terlihat lulus.

## Background, LAN Dan Desktop

- Nama kandidat: PosStandaloneDatabase, PosStandaloneWeb, PosStandaloneScheduler.
  Catat ownership/installation ID; konflik service tidak diambil alih. Opsi
  otomatis menentukan Automatic/Manual. Mode Manual punya start/stop native
  admin; menutup desktop tidak menghentikan server seluruh toko.
- Scheduler opsional memakai PHP absolut bundled, working directory rilis dan
  PATH MySQL khusus proses. Single-instance/overlap guard, timeout/shutdown/log
  aman. Hanya memicu Laravel Scheduler, bukan membuat ulang BackupWindow atau
  menambah scheduled task duplikat. Tidak menampilkan console berkala.
- Port kandidat HTTPS 8443, DB loopback 3307. Konflik ditampilkan untuk dipilih,
  bukan mematikan service lain. Tidak mengubah PATH global, hosts atau pos.test.
- Pilih alamat LAN/hostname canonical yang bisa dijangkau. Desktop dan klien
  memakai origin konsisten; gambar, route Ziggy/Inertia, upload/struk tidak
  boleh menunjuk localhost klien. DNS/reservasi DHCP tetap tugas admin jaringan.
- CA lokal unik dan leaf certificate SAN sesuai alamat; key privat dibatasi ACL.
  Trust server hanya dengan persetujuan admin. Klien mengimpor public CA dengan
  prosedur OS yang sah; jangan membagikan key CA atau bypass certificate error.
  Perubahan IP membutuhkan konfigurasi/cert ulang, bukan mematikan HTTPS.
- Firewall HTTPS hanya profil Private/subnet yang dipilih; tidak membuka
  MySQL/QZ/manajemen atau internet publik. Tidak mengganti sertifikat QZ.
- Production/debug mati, timezone Asia/Jakarta, secure cookie, document root
  public, directory listing mati. Alias/junction media hanya public storage,
  tidak mengeksekusi PHP. Env/backup/key/private harus 404/403 lewat HTTP.
- WebView2 menampilkan login/UI existing, tanpa welcome page. Profil per user;
  navigation dibatasi origin, tautan eksternal ditangani aman. Tidak ada generic
  JavaScript bridge ke process/shell/SQL, file privat atau privilege elevated.
- Uji session, focus/shortcut/scanner keyboard, upload/download, resize, popup
  struk dan print dialog pada WebView2 aktual, bukan asumsi dari Chrome.
- QZ opsional tetap tray user, bukan service/print-server tablet. Existing
  installation tidak ditimpa/di-upgrade tanpa izin. Prompt/trust tetap sah.
  Uji WebView2-WSS QZ bila dipilih. Jangan memasukkan driver APD/CT428S privat,
  menyalakan physical print, atau membuat gateway struk baru dalam packaging ini.

## Backup Dan Lifecycle Rilis

- Backup manual memakai command/UI existing dengan mysql/mysqldump bundled.
  Restore disposable `--verify` memakai credential maintenance privat dalam
  proses dan path config cache maintenance terisolasi; override DB environment
  tidak boleh mengandalkan cache aplikasi web yang sudah dibangun. Tidak
  memberi global CREATE/DROP pada akun web.
- Recovery menyertakan APP_KEY/config/media bisnis, bukan SQL saja. Bundel
  recovery privat tidak masuk installer/unduhan web. Salinan di PC sama tidak
  melindungi dari kerusakan PC; off-device tetap tanggung jawab operasional.
- Update offline: preflight, maintenance, backup terverifikasi, stop service
  milik instalasi, stage release, migration teruji, cache baru, health check,
  baru aktivasi. Tolak downgrade tidak kompatibel dan jangan reset data/key.
- Repair tidak reinitialize DB/akun. Uninstall hanya executable/service/rule
  milik instalasi; DB/media/config/key/backup dipertahankan. Purge tidak masuk scope.
  Shared QZ/WebView2/redist tidak dihapus. Cleanup trust CA dijelaskan terpisah,
  bukan pencabutan diam-diam yang merusak klien/produk lain.
- Rollback file hanya jika schema kompatibel. Tidak down-migrate/restore
  otomatis atas transaksi baru. Bila tidak aman, tahan maintenance dan minta admin.

## Allowlist Usulan

Daftar berikut diterima untuk implementasi sesuai increment. Pada Increment 1
hanya tooling deployment, ignore build privat, dan dokumentasi terkait yang
diubah. Perluasan exact file list harus direvisi/disetujui sebelum coding;
tidak membuat folder/class kosong.

Existing: `AGENTS.md`, `.gitignore`, `bootstrap/app.php`,
`app/Console/Commands/BackupPos.php`,
`app/Modules/Platform/Identity/ServiceProvider.php`,
`tests/Feature/Console/BackupPosTest.php`, `docs/FOLDER-STRUCTURE.md`,
`docs/QUALITY.md`, `docs/README.md`, `docs/DECISIONS.md`,
`docs/modules/Platform/Identity/README.md`,
`docs/modules/Platform/OperationalSettings/README.md`,
`docs/work-items/mvp-ready/OPERASIONAL.md`,
`docs/work-items/mvp-ready/DEPLOY-WINDOWS.md`.

File baru Laravel:

```text
config/standalone.php
app/Modules/Platform/Identity/Application/Actions/CreateInitialOwner.php
app/Modules/Platform/Identity/Application/Contracts/InitialOwnerRepository.php
app/Modules/Platform/Identity/Infrastructure/Persistence/Repositories/DatabaseInitialOwnerRepository.php
app/Modules/Platform/Identity/Presentation/Console/Commands/InitializeOwnerCommand.php
tests/Feature/Modules/Platform/Identity/InitialOwnerTest.php
tests/Feature/Console/StandaloneRuntimeTest.php
docs/user-guide/INSTALLER-WINDOWS.md
```

File baru di bawah `deployment/windows/` (setiap butir adalah path exact):

```text
README.md
build.ps1
global.json
components.lock.json
app-files.json
config/httpd.conf.template
config/php.ini.template
config/my.ini.template
installer/pos.iss
src/Pos.Desktop/Pos.Desktop.csproj
src/Pos.Desktop/packages.lock.json
src/Pos.Desktop/Program.cs
src/Pos.Desktop/MainForm.cs
src/Pos.Desktop/app.manifest
src/Pos.Setup/Pos.Setup.csproj
src/Pos.Setup/packages.lock.json
src/Pos.Setup/Program.cs
src/Pos.Setup/SetupWizard.cs
src/Pos.Setup/InstallationCoordinator.cs
src/Pos.Setup/RuntimeConfiguration.cs
src/Pos.Setup/ServiceManager.cs
src/Pos.Setup/CertificateManager.cs
src/Pos.Setup/PayloadVerifier.cs
src/Pos.Setup/app.manifest
src/Pos.Scheduler/Pos.Scheduler.csproj
src/Pos.Scheduler/packages.lock.json
src/Pos.Scheduler/Program.cs
src/Pos.Scheduler/SchedulerService.cs
tests/Pos.Deployment.Tests/Pos.Deployment.Tests.csproj
tests/Pos.Deployment.Tests/packages.lock.json
tests/Pos.Deployment.Tests/InstallationSafetyTests.cs
tests/Pos.Deployment.Tests/PayloadTests.cs
tests/Pos.Deployment.Tests/SchedulerTests.cs
tests/Pos.Deployment.Tests/DesktopPolicyTests.cs
scripts/verify-payload.ps1
scripts/smoke-install.ps1
```

SDK dikunci pada global.json dan NuGet pada lockfile per project; package
tidak floating. Build dapat langsung memakai csproj, tanpa solution tambahan.
Binary/SDK/cache/report build di ignored
`storage/app/private/windows-installer/`; bin/obj diabaikan.
Tidak mengubah composer/npm dependency, migration, route/frontend bisnis,
module Hardware atau driver OS tanpa perluasan izin.

## Increment Dan QA

| No | Hasil utuh | Verifikasi |
| --- | --- | --- |
| 0 | PLAN/PRD/checklist/ADR Proposed | diff/link/review scope; tanpa runtime QA |
| 1 | Payload offline terverifikasi dan EXE bootstrap | manifest, hash/signature/license, payload safety, build staging |
| 2 | DB baru -> owner -> login -> backup | first-owner race/retry/authorization, bootstrap/path, backup/restore disposable |
| 3 | Desktop -> LAN/HTTPS -> service/scheduler pilihan | native UI aktual, close/reboot/failure, negative TLS/URL, backup terjadwal nyata |
| 4 | Update/repair/uninstall aman | failure injection, fingerprint akun/key/media/ledger, restore, schema rollback gate |
| 5 | Paket calon rilis pada Windows bersih | satu regresi lengkap, offline install, checksum/signing dan handoff |

Command rencana (belum dijalankan), pada staging/VM/DB disposable saja:

```powershell
php artisan test --compact tests/Feature/Modules/Platform/Identity/InitialOwnerTest.php tests/Feature/Console/StandaloneRuntimeTest.php tests/Feature/Console/BackupPosTest.php
dotnet test deployment/windows/tests/Pos.Deployment.Tests/Pos.Deployment.Tests.csproj
node scripts/qa/page-guides.mjs
```

Untuk Increment 1, test deployment menggunakan executable assertion harness
tanpa dependency NuGet: `dotnet run --project deployment/windows/tests/Pos.Deployment.Tests/Pos.Deployment.Tests.csproj`.
`dotnet test` di atas baru berlaku jika test SDK benar-benar ditambahkan.
Ini menghindari package tanpa consumer; setiap assertion gagal menghasilkan
exit code nonzero dan nama kasus, bukan sekadar script yang selalu sukses.

Build staging: `composer check-platform-reqs --no-dev`, `npm ci`,
`npx tsc --noEmit`, lint source aktif nonmutating, `npm run build`.
Test PHP lengkap satu kali di gate akhir; gunakan kembali hasil source/artifact
sama dan ulang hanya gate terdampak perubahan.

QA fixture utuh: setup -> isi toko/master fixture -> shift -> POS lunas ->
stok/FIFO/payment/invoice konsisten -> backup/restore. Tidak ada transaksi QA
pada DB toko. Uji desktop/tablet representatif, bukan semua kombinasi tema.
Chrome DevTools MCP untuk web bila tersedia; catat BLOCKED/fallback sesuai
QUALITY. WebView2 remote debugging hanya build QA/loopback, mati di produksi.
Browser Chrome/mock bukan bukti WebView2; VM bersih/reboot/native tetap wajib.
Tablet/printer/scanner fisik yang tidak tersedia tetap PENDING.

Pisahkan BUILD_VERIFIED, INSTALL_VERIFIED, LAN_VERIFIED, STORE_READY dan
PHYSICAL_PENDING. Tick Scheduler bukan bukti backup/restore. Commit scoped per
increment lulus; push hanya sesudah paket selesai/instruksi eksplisit. Binary
dan secret tidak masuk Git. Ukuran/waktu/disk minimum baru ditetapkan dari artifact.

## Batas Berhenti

Sumber binary/lisensi/ABI/OS belum jelas, payload berisi data toko, first-owner
guard gagal, ownership instalasi tidak terbukti, recovery berisiko hilang data,
atau TLS hanya bekerja setelah bypass: hentikan dan minta keputusan. Jangan
membeli lisensi/signing, mengganti stack, atau memperbaiki dependency lain sendiri.

## Referensi Primer

- [WebView2 distribution/offline](https://learn.microsoft.com/en-us/microsoft-edge/webview2/concepts/distribution).
- [.NET self-contained](https://learn.microsoft.com/en-us/dotnet/core/deploying/)
  dan [dukungan Windows](https://learn.microsoft.com/en-us/dotnet/core/install/windows).
- [Apache Windows](https://httpd.apache.org/docs/2.4/platform/windows.html).
- [PHP Windows/TS/redist](https://www.php.net/downloads.php?os=windows).
- [MySQL Windows](https://dev.mysql.com/doc/refman/8.4/en/windows-installation.html),
  [service](https://dev.mysql.com/doc/refman/8.4/en/windows-start-service.html),
  [kebijakan OEM/ISV](https://www.mysql.com/about/legal/licensing/oem/).
- [Inno Setup dan lisensi yang ditautkan vendor](https://jrsoftware.org/inno-setup).
- [Laravel deployment](https://laravel.com/docs/12.x/deployment)
  dan [Scheduler](https://laravel.com/docs/12.x/scheduling#running-the-scheduler).
- [QZ deployment](https://qz.io/docs/deployment).

Diakses 22 November 2026. Verifikasi versi/dukungan kembali ketika manifest
dikunci. Gate lisensi adalah kebutuhan keputusan, bukan opini hukum.
