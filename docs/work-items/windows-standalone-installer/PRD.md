# PRD: Installer POS Windows Standalone

## Status

Kebutuhan dan PLAN diterima untuk mulai Increment 1, 22 November 2026.
Distribusi aplikasi tertutup dipilih user. Revisi 23 November 2026: MySQL
dipasang manual oleh admin, bukan bagian bundel; pengajuan Oracle dihentikan.
Persetujuan build bukan izin deployment pada PC aktual.

## Masalah Dan Tujuan

Klien perlu memasang POS tanpa Laragon, menyiapkan PHP atau build frontend.
Admin memasang MySQL manual lebih dahulu. Sesudah prasyarat itu terpenuhi,
paket offline menyiapkan runtime aplikasi dan toko baru, mempertahankan
aplikasi bisnis serta data saat update/uninstall.

## Pengguna Dan Lifecycle

1. Admin memasang MySQL serta mysql/mysqldump manual, menyediakan database baru
   kosong dan akun khusus. Admin menjalankan `POS-Setup-<versi>-win-x64.exe`, menyelesaikan
   UAC dan lisensi. Preflight memeriksa OS, disk, port dan instalasi existing.
2. Wizard Bahasa Indonesia menampilkan opsi server otomatis, LAN, pemicu
   Scheduler dan QZ. Dampak service/firewall/trust tidak disembunyikan.
3. Koneksi, database kosong, hak akses dan alat backup diverifikasi tanpa
   mengelola service/datadir MySQL. Pemilik membuat nama/email/password pertama
   dengan konfirmasi; tidak ada akun baku atau penyalinan dataset toko ini.
4. Kasir membuka shortcut WebView2 ke login existing. Pengaturan toko, gudang,
   barang/harga, kas/metode bayar dan shift memakai modul/panduan yang sudah ada.
5. Operator PC/tablet menggunakan URL LAN setelah trust sertifikat yang sah.
6. Admin memperbarui/perbaiki/hapus aplikasi tanpa kehilangan data toko.

"Sekali klik" berarti satu paket terpandu, bukan tanpa UAC, input akun,
persetujuan lisensi, atau onboarding sertifikat klien. Offline berarti tidak
perlu internet saat install/pakai lokal setelah prasyarat MySQL manual tersedia;
akses LAN tetap membutuhkan jaringan.

## Requirement

| ID | Kebutuhan |
| --- | --- |
| INS-01 | Runtime aplikasi, vendor PHP produksi dan asset frontend dibangun dalam paket; MySQL/mysql/mysqldump eksternal manual |
| INS-02 | Klien tidak membutuhkan Node/npm, Composer, Git, .NET SDK atau dev server |
| INS-03 | Windows x64; edisi/versi OS hanya diklaim setelah didukung runtime dan diuji |
| INS-04 | Validasi MySQL manual loopback, database khusus kosong dan akun terbatas; tanpa memasang/mengendalikan service/datadir MySQL |
| INS-05 | Owner/admin pertama dibuat sekali; rerun tidak mereset password/APP_KEY |
| INS-06 | Tidak ada setup HTTP publik, akun default, master/demo/transaksi latihan |
| INS-07 | Server dapat otomatis menyala dan tetap hidup saat desktop ditutup |
| INS-08 | Pemicu Scheduler opsional/noninteraktif; aturan backup tetap di Laravel |
| INS-09 | HTTPS/cookie secure/debug mati; document root public, private tidak terbuka |
| INS-10 | LAN hanya web pada jaringan privat yang dipilih, bukan database atau manajemen |
| INS-11 | DB/media/key/config/backup persisten saat update/repair/uninstall |
| INS-12 | Tidak mengambil alih service/DB/PATH global/hosts/port milik produk lain |
| INS-13 | QZ komponen opsional; driver printer model-spesifik dan UAT terpisah |
| INS-14 | Manifest versi/hash/provenance/lisensi dan checksum rilis dapat diperiksa |
| INS-15 | Status build/install/LAN/toko siap/hardware diuji dibedakan |

## Di Luar Scope

Fitur bisnis, tenant/cabang, migrasi data Laragon, purge data, auto-update
internet, driver universal, gateway struk baru, silent signing dan printer
tablet jarak jauh, bundel/installer otomatis MySQL dan pengajuan Oracle.
Tidak menutup UAT operator/printer atau backup off-device
hanya karena installer berhasil.

## Acceptance Dan Keputusan Terbuka

- [ ] Windows bersih dengan MySQL manual siap menghasilkan login offline tanpa
  Laragon atau data developer; tidak diklaim tanpa prasyarat eksternal.
- [ ] Setup race/retry/failure tidak membuat takeover atau menghilangkan data.
- [ ] Background, reboot, TLS/LAN, backup/restore dan uninstall terbukti.
- [ ] Dukungan OS, lisensi bundling dan code-signing ditetapkan sebelum distribusi.

Detail pilihan teknis, allowlist dan gate terdapat di [PLAN](plan.md).
