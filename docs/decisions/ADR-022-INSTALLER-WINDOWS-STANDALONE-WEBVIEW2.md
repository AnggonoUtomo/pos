# ADR-022: Installer Windows Standalone Dan Desktop WebView2

## Status

Accepted untuk rancangan deployment dan mulai Increment 1, 22 November 2026.
Izin redistribusi vendor/dukungan OS/signing tetap menjadi gate rilis terpisah.
Tidak memberi izin instalasi OS sekarang. User memilih aplikasi tertutup;
bundel MySQL Community belum mendapat bukti izin redistribusi yang sesuai.

## Tanggal

2026-11-22

## Konteks

User meminta installer Windows x64 offline tanpa Laragon, memilih WebView2,
server toko/LAN, opsi startup otomatis, database baru dan setup pemilik.
Core Laravel/Inertia/MySQL serta aturan bisnis tetap. WebView2 adalah host UI,
bukan runtime PHP/database. Data toko/developer tidak boleh ikut rilis.

Larangan runner OS tetap melindungi aplikasi web dari pemasangan service
otomatis. Installer deployment memerlukan pengecualian eksplisit yang sempit.

## Keputusan

- Tool deployment di `deployment/windows/`, bukan bounded context/framework
  bisnis baru. Shell C#/.NET self-contained + WebView2 memakai UI POS existing.
- Apache/PHP/MySQL privat dan kandidat Inno Setup; versi/ABI/lisensi dikunci
  sebelum build. Client tidak memerlukan tool developer atau Laragon.
- Program Files untuk binary, ProgramData untuk data/config/key/backup dengan
  ACL per fungsi, profil WebView2 per user. Desktop bukan administrator.
- Service server hanya dari installer yang dijalankan admin dengan opsi
  Automatic/Manual terlihat. Bukan izin Laravel/HTTP memasang service atau
  menjalankan installer sekarang pada PC toko.
- Pemicu Scheduler opsional/noninteraktif; aturan jadwal di OperationalSettings,
  tidak memakai runner PowerShell berulang atau scheduled task duplikat.
- Bootstrap owner melalui wizard lokal/CLI Identity yang terjaga, tanpa
  registrasi publik/seeder demo. Key unik saat install baru, bukan saat update.
- HTTPS/LAN canonical, local CA unik per instalasi dan trust yang disetujui;
  klien di-onboard tanpa certificate-error bypass.
- Repair/update/uninstall tidak purge DB/media/key/config/backup. Aktivasi
  rilis didahului backup/schema/health gate, tanpa silent restore data baru.
- QZ opsional mengikuti ADR-020/021, bukan menyalakan physical print, driver
  universal, gateway struk baru atau remote QZ tablet.

Pengecualian deployment Windows dicatat di AGENTS/struktur dan
panduan terkait. Aturan OS-neutral untuk Linux/web tetap. ADR-020/021,
identifier, permission dan invariant stok/FIFO/payment/pricing tidak diganti.

## Alternatif

- Laragon: tidak memenuhi paket klien standalone.
- Browser saja: tidak memenuhi pilihan desktop WebView2.
- Electron: runtime desktop lain, tidak dipilih setelah arahan user.
- PHP development server: bukan webserver produksi toko/LAN.
- Docker/WSL: prasyarat OS tambahan di luar scope.
- MariaDB/SQLite: tidak mengganti MySQL untuk menghindari keputusan lisensi.
- Fixed Version WebView2: lebih terisolasi tetapi distributor menanggung patch
  runtime penuh; kandidat utama Evergreen dengan installer offline.

## Konsekuensi Dan Verifikasi

Paket lebih terpadu tetapi lebih besar dan memerlukan patch runtime, supply
chain, lisensi, signing, ACL dan QA Windows tersendiri. Satu paket bukan tanpa
UAC, input akun, atau trust sertifikat tablet. Dukungan Windows 10 dinilai per
edisi, bukan dijanjikan universal. Lisensi dan Windows disposable adalah gate,
build/mock saja bukan bukti siap produksi/hardware.

[PLAN](../work-items/windows-standalone-installer/plan.md) memuat sumber primer,
allowlist/QA/recovery. [PRD](../work-items/windows-standalone-installer/PRD.md)
dan [tasks](../work-items/windows-standalone-installer/tasks.md) memisahkan
kebutuhan, hasil build/preflight dan increment yang masih pending.
