# Installer POS Windows Standalone

## Status

Draft, 22 November 2026. Kebutuhan inti dikonfirmasi user. PLAN teknis menunggu
persetujuan; penyusunan dokumen bukan izin coding atau deployment.

## Owner Dan Lokasi

- Lintas deployment, Platform/Identity, dan Platform/OperationalSettings.
- Calon tool deployment: `deployment/windows/`, bukan module bisnis baru.
- [PRD](PRD.md), [PLAN](plan.md), [checklist](tasks.md).
- Usulan keputusan: [ADR-022](../../decisions/ADR-022-INSTALLER-WINDOWS-STANDALONE-WEBVIEW2.md).

## Kebutuhan Yang Dikonfirmasi

1. Satu installer offline Windows x64 tanpa Laragon, memakai desktop WebView2.
2. Webserver, PHP, MySQL, redist, dan dependency runtime disertakan.
3. PC utama sekaligus server/kasir; PC/tablet lain melalui browser LAN.
4. Opsi server otomatis saat Windows menyala; tutup kasir tidak mematikan server.
   Tidak ada jendela terminal/PowerShell yang muncul berkala.
5. Database baru dan setup pemilik pertama; tidak membawa akun, master,
   transaksi, gambar, backup atau credential toko/developer ini.
6. Data persisten terpisah dari aplikasi agar tidak tertimpa update.

## Kondisi Awal Terverifikasi

- Source runtime: commit `095b625c1b73c2ea1df7e289dbaa4603ba5367c0`.
- Laravel 12/PHP 8.4/MySQL, Inertia React dan aturan bisnis existing dipertahankan.
- Belum ada installer standalone/shell WebView2; helper operasi yang ada
  hanya setup QZ. Binary QZ/driver OS bukan paket aplikasi POS.
- `DatabaseSeeder` memanggil `IdentityAccessSeeder` dan membuat Test User.
  IdentityAccessSeeder dapat mengganti credential. Keduanya dilarang sebagai
  provisioning produksi; katalog permission dan role produksi sudah terpisah.
- User/Role/Permission existing memakai integer starter kit/package, sedangkan
  tabel utama module memakai ULID. Tidak ada migrasi identifier pada installer.
- `BackupPos` menulis `base_path('backups')`, memakai mysql/mysqldump dari PATH.
- Laravel menyediakan override env/storage/cache; bootstrap project belum
  mempunyai mode standalone. `pos:readiness --production` mensyaratkan profil
  toko dan HTTPS/cookie secure, bukan hanya service menyala.
- Worktree memiliki LSP user staged/untracked. Tidak dimasukkan ke commit/rilis.

## Scope Dan Non-Scope

Scope: payload offline, wizard, runtime privat, akun pertama, desktop WebView2,
opsi background/scheduler, HTTPS/LAN, backup, repair/update/uninstall aman.

Tidak termasuk fitur bisnis, perubahan FIFO/pricing/payment/shift, tenant/cabang,
registrasi publik, migrasi data Laragon, installer Linux, Docker, driver printer
universal, gateway struk baru, silent print atau remote QZ tablet. Tidak
mengaktifkan physical print atau menutup pending UAT lama.

## Acceptance Criteria

- [ ] Install offline pada Windows bersih tanpa Laragon berhasil sampai login.
- [ ] Pemilik pertama dibuat aman, tanpa akun/password default atau data demo.
- [ ] Desktop dan klien LAN memakai HTTPS yang dipercaya tanpa bypass TLS.
- [ ] Startup otomatis/manual, close-window dan reboot sesuai opsi.
- [ ] Backup manual/jadwal opsional dan restore disposable terbukti.
- [ ] Repair/update/uninstall mempertahankan DB, media, config dan APP_KEY.
- [ ] Tidak bergantung pada path/service/DB Laragon atau tool developer klien.

## Dampak

| Area | Batas perubahan yang diusulkan |
| --- | --- |
| Database | Database baru privat; migration existing, tanpa schema bisnis baru |
| Identity | CLI first-owner, katalog permission/role produksi, audit tanpa secret |
| Route/UI aplikasi | Login/module existing; tidak ada route setup/registrasi publik |
| Demo | Tidak disertakan; fixture QA hanya pada instalasi disposable |
| Backup | Path persisten configurable dengan fallback dan output existing |
| Ledger/soft delete/FIFO/payment | Tidak berubah |
| OS | Opsi service/ACL/trust/firewall milik instalasi, bukan deployment sekarang |

## Increment

| No | Status | Hasil |
| --- | --- | --- |
| 0 | Passed | Dokumen perencanaan, bukan implementasi |
| 1 | Planned | Payload offline terverifikasi dan bebas data toko |
| 2 | Planned | Setup database, owner pertama, login dan backup |
| 3 | Planned | WebView2, LAN/HTTPS, service dan scheduler opsional |
| 4 | Planned | Repair/update/uninstall dan pemulihan kegagalan |
| 5 | Planned | Satu gate paket akhir pada Windows bersih dan handoff |

## Handoff

Tahap sekarang hanya dokumentasi. Tidak ada executable installer dibuat,
dependency dipasang, cache/database produksi diubah, atau service baru
diregistrasikan. Old-docs dan file user tidak disentuh. Gate keputusan dan
verifikasi tersedia pada PLAN dan tasks.
