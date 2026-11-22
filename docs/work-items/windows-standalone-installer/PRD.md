# PRD: Installer POS Windows Standalone

## Status

Draft, 22 November 2026. Kebutuhan inti dikonfirmasi dalam percakapan; pilihan
teknis, persetujuan coding dan izin deployment pada PC aktual tetap terpisah.

## Masalah Dan Tujuan

Klien perlu memasang POS tanpa menyiapkan Laragon, PHP/MySQL, build frontend,
atau terminal sendiri. Satu paket offline harus menyiapkan runtime dan toko
baru, mempertahankan aplikasi bisnis serta data saat update/uninstall.

## Pengguna Dan Lifecycle

1. Admin Windows menjalankan satu `POS-Setup-<versi>-win-x64.exe`, menyelesaikan
   UAC dan lisensi. Preflight memeriksa OS, disk, port dan instalasi existing.
2. Wizard Bahasa Indonesia menampilkan opsi server otomatis, LAN, pemicu
   Scheduler dan QZ. Dampak service/firewall/trust tidak disembunyikan.
3. Database privat baru disiapkan. Pemilik membuat nama/email/password pertama
   dengan konfirmasi; tidak ada akun baku atau penyalinan dataset toko ini.
4. Kasir membuka shortcut WebView2 ke login existing. Pengaturan toko, gudang,
   barang/harga, kas/metode bayar dan shift memakai modul/panduan yang sudah ada.
5. Operator PC/tablet menggunakan URL LAN setelah trust sertifikat yang sah.
6. Admin memperbarui/perbaiki/hapus aplikasi tanpa kehilangan data toko.

"Sekali klik" berarti satu paket terpandu, bukan tanpa UAC, input akun,
persetujuan lisensi, atau onboarding sertifikat klien. Offline berarti tidak
perlu internet saat install/pakai lokal; akses LAN tetap membutuhkan jaringan.

## Requirement

| ID | Kebutuhan |
| --- | --- |
| INS-01 | Runtime, vendor PHP produksi dan asset frontend sudah dibangun dalam paket |
| INS-02 | Klien tidak membutuhkan Node/npm, Composer, Git, .NET SDK atau dev server |
| INS-03 | Windows x64; edisi/versi OS hanya diklaim setelah didukung runtime dan diuji |
| INS-04 | MySQL privat loopback, credential unik, bukan root kosong/user web global admin |
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
tablet jarak jauh. Tidak menutup UAT operator/printer atau backup off-device
hanya karena installer berhasil.

## Acceptance Dan Keputusan Terbuka

- [ ] Windows bersih/offline menghasilkan login yang bekerja tanpa data developer.
- [ ] Setup race/retry/failure tidak membuat takeover atau menghilangkan data.
- [ ] Background, reboot, TLS/LAN, backup/restore dan uninstall terbukti.
- [ ] Dukungan OS, lisensi bundling dan code-signing ditetapkan sebelum distribusi.

Detail pilihan teknis, allowlist dan gate terdapat di [PLAN](plan.md).
