# PLAN: Installer POS Windows Standalone

## Status Dan Batas Izin

Disetujui user untuk mulai Increment 1, 22 November 2026. Persetujuan mencakup
tooling build privat, staging dan preflight; bukan pemasangan service atau
perubahan database/OS PC toko sekarang. Scope mengikuti [PRD](PRD.md) dan
[README](README.md). Increment berikutnya tetap mengikuti batas PLAN ini.

Revisi user 23 November 2026: MySQL dipasang manual oleh admin klien, bukan
dibundel ke installer. Pengajuan Oracle dihentikan sebelum dikirim. Server,
mysql dan mysqldump tidak diunduh, disalin atau didistribusikan dalam paket.
Database engine tetap MySQL. Rilis penuh tetap menunggu implementasi, pin dan
lisensi komponen yang benar-benar dibundel serta QA Windows; bukan otomatis
siap setelah MySQL dikeluarkan. Payload/preflight internal dinilai terpisah.

## Usulan Komponen

| Komponen | Kandidat | Batas |
| --- | --- | --- |
| Installer | Inno Setup, satu EXE x64 offline | Kunci versi dan ketentuan komersial sebelum build |
| Desktop/setup helper | C# WinForms, .NET 10 LTS self-contained win-x64 | Tidak butuh .NET SDK/runtime global klien; cek dukungan edisi Windows |
| WebView2 | Evergreen Runtime dengan offline Standalone Installer | Deteksi existing, tanpa downgrade; tetap perlu strategi patch keamanan |
| Webserver | Apache 2.4 x64, kandidat binary Apache Lounge | ASF merilis source, bukan binary Windows; sumber binary perlu diverifikasi |
| PHP | PHP 8.4 x64 Thread Safe sebagai module Apache | Cocokkan ABI/compiler/redist; extension berdasar lockfile/fitur nyata |
| MySQL | 8.4 LTS x64, prasyarat eksternal manual | Admin memasang server dan mysql/mysqldump; tidak dibundel atau dikelola installer |
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
3. Review lisensi redistribusi Apache/PHP, redist, .NET, WebView2, QZ
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

## Penutupan Izin Oracle Dan Lingkungan Windows

User meminta menuntaskan kedua gate pada 22 November 2026. Ini memberi scope
untuk menyiapkan pengajuan resmi dan memeriksa lingkungan uji, bukan kewenangan
menerbitkan izin Oracle, menandatangani kontrak, membeli lisensi, atau
mengaktifkan/mengubah VM existing tanpa konfirmasi pemilik.

### MySQL Manual Menggantikan Pengajuan Oracle

User membatalkan kebutuhan pengajuan/bundel MySQL: "nanti install manual".
Draft privat tidak dikirim dan ditandai dibatalkan; tidak meminta identitas
legal/login Oracle lagi. Gate izin bundel MySQL menjadi NOT_APPLICABLE untuk
rancangan tanpa binary MySQL ini, bukan bukti izin telah terbit atau pendapat
bahwa seluruh kewajiban lisensi hilang. Bundel ulang memerlukan keputusan baru.

Admin memasang MySQL dan menyiapkan database khusus baru yang kosong, akun
aplikasi terbatas serta akun migration/maintenance sesuai kebutuhan. Installer
nantinya meminta konfigurasi loopback/port/database/user dan path absolut
mysql/mysqldump yang tersedia; password lewat input privat, bukan argumen/log.
Uji koneksi, versi, hak akses, database kosong dan alat backup sebelum mutation.
Prasyarat belum terpenuhi harus menghentikan setup dengan pesan yang jelas,
bukan mengunduh MySQL diam-diam atau memakai database toko existing.

Installer tidak menginisialisasi datadir, memasang/mengubah/start/stop service
MySQL, mengganti credential server, atau menghapus MySQL saat uninstall.
Status port DB yang terpakai bukan konflik preflight maupun bukti MySQL siap;
koneksi dan hak akses harus diuji terpisah pada tahap setup. Offline berlaku
sesudah admin menyediakan prasyarat manual. Tes Windows bersih harus mencatat
MySQL dipasang manual dahulu, bukan mengklaim tanpa prasyarat eksternal.

Scope penyesuaian awal: dokumentasi ini, PRD/README/checklist/ADR-022,
`deployment/windows/components.lock.json`, pesan preflight dan test terfokus.
Koneksi/provisioning database tetap pekerjaan Increment 2, bukan sudah tersedia.

### Lingkungan Uji Yang Diperiksa

Pemeriksaan lokal read-only menemukan Windows 10 Enterprise x64 build 19045,
RAM sekitar 7,9 GB dan ruang kosong C sekitar 109,3 GB. VirtualBox 7.2.14
sudah terpasang di Program Files dengan Authenticode Valid/Oracle. Binary
tidak berada di PATH, sehingga pemeriksaan PATH saja semula tidak menemukannya.
Ini bukan alasan memasang hypervisor baru.

Pada pemeriksaan awal, registry VirtualBox default memiliki dua VM:
`WS-RTR2-01` bertipe Windows11_64 dan `DFIR-RTR2-01` bertipe Ubuntu_64.
Saat itu hanya metadata konfigurasi dibaca, sebelum izin clone diberikan.
Nama/tipe OS bukan bukti Windows sudah terpasang, berlisensi atau bersih.
Baseline guest yang bersih, status lisensi dan kemampuan boot belum terbukti.
HypervisorPresent terdeteksi, tetapi firmware virtualization/SLAT dilaporkan
false oleh CIM; kapabilitas nested VM tidak disimpulkan dari hasil ini.

Pemilik kemudian memilih `WS-RTR2-01` dan secara eksplisit menyetujui clone
`POS-QA-WS-RTR2-01` terisolasi untuk QA. Discovery VirtualBox menemukan VM asal
poweroff, tanpa snapshot, dengan adapter host-only dan shared folder host
writable/auto-mount. Karena itu VM asal tidak diboot untuk pemeriksaan ini.
Clone penuh hanya dari current state, di ignored folder build privat;
nonaktifkan seluruh NIC/shared folder clone sebelum boot. Jangan mengubah
network/share, reset OS, restore snapshot, menghapus disk atau menjalankan
installer pada VM asal. Checkpoint clone sebelum QA bukan bukti baseline
bersih. Verifikasi konfigurasi/disk asal sebelum dan setelah clone.

Discovery berikutnya memastikan `RTR2` merupakan global mapping VirtualBox,
bukan share lokal VM. Pemilik mengizinkan menonaktifkannya sementara hanya
ketika QA, dengan VM lain mati, lalu memulihkannya persis setelah clone QA
mati. Jangan menghapus global share ketika hanya sedang menyiapkan clone.
RAM host tersedia sekitar 2,5-3,6 GB saat workspace dipakai. VS Code tidak
perlu ditutup untuk melanjutkan pengembangan; jangan memaksa boot guest pada
sesi kerja yang terbatas. Penyiapan clone powered-off dan uji guest adalah
dua tahap berbeda; boot tetap memerlukan pemeriksaan resource/isolasi ulang.

Clone pertama terputus dengan RPC_S_SERVER_UNAVAILABLE/0x800706ba. User
menyampaikan mungkin mengakhiri proses Oracle/VirtualBox melalui Task Manager.
Ini penjelasan yang mungkin sesuai gejala, bukan bukti crash internal server
atau bug POS. Tidak ditemukan event crash VBox terkait pada jendela yang
diperiksa. Pertahankan artefak gagal; retry terbatas satu kali di direktori
privat baru, tanpa menimpa atau mendaftarkan image parsial sebagai VM sehat.

Pemulihan 23 November 2026 menemukan retry kedua juga tidak selesai: hanya
disk parsial 3.170.893.824 byte pada `vms-attempt-2/`, tanpa file konfigurasi
atau registrasi VM QA. Session eksekusi lama tidak tersedia lagi. Log
`VBoxSVC.log.3` merekam `WM_QUERYENDSESSION` dan `WM_ENDSESSION: shutdown`
pada 22 November 2026 pukul 17.16 WIB; sesi Windows berakhir ketika clone
berjalan. Tidak menyimpulkan kehabisan RAM atau crash VirtualBox dari ini.
Tidak menjalankan retry ketiga otomatis, mem-boot image parsial, atau
menghapus kedua hasil parsial. VM asal dan VM DFIR tidak dijalankan;
global share tetap seperti semula. Uji Windows bersih masih BLOCKED_ENVIRONMENT.
SHA256 konfigurasi dan seluruh VDI asal cocok dengan fingerprint sebelum
percobaan. Bukti privat: `qa-vm-source-before.json`, `qa-vm-source-after.json`
dan `qa-vm-recovery-report.json` di folder build privat. Ini verifikasi VM
asal tidak berubah, bukan bukti clone atau Windows bersih lulus.

Jika baseline VM pilihan tidak dapat dibuktikan bersih, pemilik menyediakan
ISO Windows 11 yang sah atau PC uji terpisah. Opsi
ISO resmi tersedia di [Windows 11 Enterprise Evaluation](https://www.microsoft.com/en-us/evalcenter/evaluate-windows-11-enterprise);
registrasi/persetujuan lisensi evaluasi dilakukan pemilik, bukan aktivasi
produksi atau penggantian Windows PC toko. Tidak mengunduh ISO dari mirror
acak atau menggunakan bypass TPM/Secure Boot/aktivasi.

Urutan uji setelah media/VM dan izin penggunaan dikonfirmasi:

1. Gunakan VM khusus dari install/snapshot bersih; validasi OS/edisi/arsitektur,
   lifecycle, status evaluasi/lisensi dan tidak ada Laragon/tool developer/POS.
2. Isolasi dari database toko dan jaringan produksi. Input hanya artefak
   build yang hash-nya cocok; tidak share repository, env, backup, sertifikat
   privat, credential atau disk VM existing. Jangan mengubah adapter VM lab.
3. Ambil snapshot bersih milik QA. Catat ID VM/snapshot, versi OS dan checksum
   artefak. Jangan me-restore snapshot atau uninstall pada VM kerja pemilik.
4. Sekarang hanya EXE preflight yang tersedia: uji launch native, verifier,
   penolakan konflik/prasyarat dan absence of mutation. Ini bukan install QA.
5. Setelah Increment 2-4 siap dan gate distribusi tuntas, admin memasang MySQL
   manual pada OS uji; uji installer penuh offline: validasi MySQL eksternal
   -> runtime privat -> owner -> login -> alur fixture -> backup/restore
   -> close/reboot -> repair/update/uninstall. Internet mati saat pengujian
   offline; uji LAN terpisah pada jaringan uji dengan trust TLS sah.
6. Simpan hasil dan bukti aktual. Preflight atau compiler PASS tidak boleh
   menaikkan INSTALL_VERIFIED, LAN_VERIFIED, STORE_READY atau PHYSICAL status.

Windows Sandbox bukan pengganti otomatis VM Windows 11/reboot/LAN. Dukungan
dan persistensi restart berbeda menurut versi; gunakan
[dokumentasi Microsoft](https://learn.microsoft.com/en-us/windows/security/application-security/application-isolation/windows-sandbox/)
jika opsi itu dipilih. Tidak mengaktifkan fitur Windows, service atau restart
host dari pekerjaan penutupan gate ini.

## Data Dan Payload

```text
C:/Program Files/POS/
  desktop/                 WebView2 non-administrator
  tools/                   setup/maintenance, scheduler host
  runtime/                 Apache/PHP dan .NET privat, tanpa MySQL
  releases/<release-id>/   Laravel, vendor produksi, asset build
C:/ProgramData/POS/
  config/                  env dan konfigurasi privat
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
Datadir/service MySQL berada pada instalasi manual milik admin, bukan folder
atau service milik POS. Repair/update/uninstall POS tidak mengelolanya.

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
3. Validasi koneksi MySQL manual loopback, database khusus kosong, hak akun
   terpisah dan mysql/mysqldump. Tidak membuat datadir/service atau mengambil
   alih instance/akun database existing; kegagalan prasyarat menghentikan setup.
4. Buat installation ID, ACL dan APP_KEY unik per instalasi. Simpan konfigurasi
   koneksi privat tanpa secret di command line, log, repository atau rilis.
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

- Nama kandidat: PosStandaloneWeb, PosStandaloneScheduler. MySQL tetap service
  eksternal yang disiapkan admin, tidak termasuk start/stop atau autostart POS.
  Catat ownership/installation ID; konflik service tidak diambil alih. Opsi
  otomatis menentukan Automatic/Manual. Mode Manual punya start/stop native
  admin; menutup desktop tidak menghentikan server seluruh toko.
- Scheduler opsional memakai PHP absolut bundled, working directory rilis dan
  path mysql/mysqldump manual khusus proses. Single-instance/overlap guard, timeout/shutdown/log
  aman. Hanya memicu Laravel Scheduler, bukan membuat ulang BackupWindow atau
  menambah scheduled task duplikat. Tidak menampilkan console berkala.
- Port kandidat HTTPS 8443; port DB mengikuti MySQL manual loopback milik admin.
  Konflik listener HTTPS ditampilkan untuk dipilih,
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

- Backup manual memakai command/UI existing dengan mysql/mysqldump instalasi manual.
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
  MySQL manual dan shared QZ/WebView2/redist tidak dihapus. Cleanup trust CA dijelaskan terpisah,
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
