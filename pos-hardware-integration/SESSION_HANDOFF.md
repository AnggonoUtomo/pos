# Handoff Integrasi Hardware POS

**Diperbarui:** 21 November 2026.

## Kondisi Aktual

TASK-HW-013 APPROVED: user ingin QZ dipasang pada PC ini; scanner
dipilih mode keyboard langsung scan + tes kesiapan, tanpa enumerasi USB/HID.
PRD/PLAN/checklist/APPROVAL tersedia; exact33 path, validator approved
PASS, PLAN SHA2567CEA2517308C91611DC353E1CC2D938CC7952D1B6AF40297BB9DCF15487E1187.
User kemudian memasang QZ secara manual, sehingga agent tidak menjalankan
installer/download/upgrade atau mengubah trust/startup. Source autodiscovery
workspace, tes scanner keyboard dan helper setup Windows/panduan Linux selesai.
Native smoke read-only PASS:2.3.0+1,6 printer OS, signature valid/QZ Industries,
tanpa cetak; artifact qz-setup-08d4c7d4af43e493 privat, browser ditutup.
Typecheck/lint/build2975/guides34/unit PASS; QA browser akhir12 cek PASS pada
hardware-label-409605eb9fc1ba58, fingerprint QA identik, server/browser ditutup.
Startup shortcut QZ target valid diperiksa baca-saja; reboot tidak diuji.
Source commit d00cb82; REPORT013 tersedia, penutupan Git berjalan.
Tidak ada perubahan backend/database/.env/flag print.

User meminta download driver CT420D ke project, bukan install. Penelusuran
resmi belum mencocokkan model; katalog Cashcow menampilkan CT-428/CT-428S.
Pertanyaan foto label/model atau link pembelian sudah diajukan. Tunggu bukti
model, jangan menebak compatible driver atau download installer pihak ketiga.

TASK-HW-012 APPROVED dan COMPLETE; implementasi dipush8aa0c86, origin/main
diverifikasi. Workspace https://pos.test/platform/hardware, scanner ON/printing OFF.
QZ SDK2.3.0 saja terpasang, bukan aplikasi/driver OS. Gate79 test/774 assertion,
typecheck/scoped lint/Pint/build2975/guides34 dan browser12 cek PASS. Artefak
final hardware-label-1f955754a7cfea6d; semua server/browser QA sudah ditutup.
Backup pos-20261121-214732-3e2d347b.sql restore-disposable identik PASS; dua
cache privat hardware-label-deploy-46b06cb33d5a7ca8 hash-identik. Migration
Hardware batch37 Ran,4 permission baru,3 tabel kosong, fingerprint69 tabel
existing serta baris permission/migration lama identik, role/grant tidak berubah.
HTTPS login200/workspace guest302/catalog guest401/TLS valid/backup404.
Lihat [REPORT012](tasks/TASK-HW-012/REPORT.md) untuk bukti dan residual advisory
npm existing. Physical PHYSICAL_PENDING. Jangan ulang gate source identik atau
nyalakan print/pasang QZ/driver/sertifikat/service tanpa arahan.

Bagian di bawah mencatat baseline scanner sebelum TASK-HW-012; batas "belum
ada gateway/tabel" hanya berlaku pada baseline lama, bukan status kini.

Repo C:\laragon\www\pos telah diperiksa. TASK-HW-001 discovery/mockup disetujui
dan dipush pada5e3b4a51ca3da1689ee224ffc393f6971b564984. User mengoreksi bundle:
single company + multi-gudang, tidak menggunakan tenant atau cabang.

User kemudian menyetujui PLAN TASK-HW-002 version1 SHA256
665F6076563C5F7355B8FBE433894A89D0728ED7797C532F365732204861DFAA.
Implementasi Platform/Hardware scanner virtual selesai; commit/push96bddfe
berhasil pada 21 November 2026 sesuai izin user; origin/main telah diverifikasi.
Empat file LSP staged dan lima untracked user tetap dipertahankan.

## Yang Sudah Berjalan Dalam QA

- Halaman /platform/hardware/scanner dan resolve read-only; public Items
  SaleItemCatalog, auth/web/verified existing, inventory.view, rate limit.
- Feature middleware: OFF selalu404 termasuk super-system dan compiled routes.
  TASK-HW-011 membuka production ketika ON, tetap auth/verified/inventory.view.
- Input fokus/Enter, nol awal, repeated scans, serial queue maksimal20,
  hasil sementara20, timeout/cancel/stale, shortcut, Sonner dan PageGuide.
- Tanpa persistent data/migration SQL; HardwareDemoSeeder no-op. Fixture
  CCTV/kelistrikan hanya SQLite QA, tidak data toko.
- Regresi69 test/680 assertion PASS, focused checks sesudah koreksi test/
  harness PASS; typecheck/lint/build2959, guides33 PASS.
- Browser final12 cek PASS: storage/framework/testing/
  hardware-scanner-87953e352ba32224/report.json; fingerprint71 tabel identik.
- MCP guest smoke PASS/console bersih; ekspor artefak MCP ditolak workspace
  roots tool. Authenticated browser dan screenshot melalui Playwright existing.
- Browser test dan server PHP sementara sudah ditutup, tanpa service OS baru.

## Batas Yang Tetap Berlaku

Software VIRTUAL_PASSED hanya scanner; physical PHYSICAL_PENDING. HC-4208DB
USB/dongle/Bluetooth, Android nyata dan CT420D label TSPL/CPCL/media/scan-back
belum diuji. Tidak ada receipt ESC/POS/cutter/drawer/LAN CT420D yang diklaim.
Fake gateway/print transport/command builder belum dibuat pada slice scanner.
TASK-HW-011 kemudian disetujui eksplisit dan diagnostik production diaktifkan;
flag .env true serta config/route cache diperbarui setelah backup privat.
Tidak ada pilot printer atau perangkat dipasang. HTTPS login200/scanner guest302/
resolve guest401, TLS valid. Source default flag tetap false.

PLAN tidak diubah sesudah approval; status aktual di APPROVAL/tasks/REPORT.
Source modul scanner yang baru dibuat diubah sesuai PLAN TASK-HW-011; source
modul POS lain, dependency, role/grant, FIFO/payment/invoice/receipt tidak diubah.
Detail baseline: [REPORT](tasks/TASK-HW-002/REPORT.md).
Aktivasi: [REPORT TASK-HW-011](tasks/TASK-HW-011/REPORT.md).

## Lanjut Dari Sini

TASK-HW-011 COMPLETE: gate39 test/449 assertion, guard final4/26, build2959,
guides33, browser12 production-like dan fingerprint71 tabel identik PASS.
Implementasi dipush826056d; origin/main diverifikasi, LSP user tetap terpisah.
Artefak final hardware-scanner-9d2ca02114c52165; server/browser sudah ditutup.
MCP tidak tersedia pada kelanjutan; Playwright existing dipakai sebagai fallback.
Backup privat3 file hash-identik pada hardware-activation-a9813b349f6c408aaaab9108d7db41da,
di storage/app/private, ignored dan URL404. Jangan cetak/commit isi snapshot.
User kemudian meminta gateway/konfigurasi autodetect, mengonfirmasi CT420D
label + HC-4208DB tanpa struk, serta memilih QZ Tray dipasang manual.
[TASK-HW-012](tasks/TASK-HW-012/README.md) memuat PRD/PLAN/checklist;
APPROVAL sekarang APPROVED. Jalur driver-image, bukan menebak TSPL/CPCL.
PLAN83 exact path, npm SDK qz-tray2.3.0 saja, fake QA, deployment aplikasi
telah dilakukan setelah backup verified; print fisik tetap OFF. Source/SDK,
schema/seeder Hardware dan cache berubah sesuai allowlist, bukan .env/OS.
Langkah berikutnya menunggu hardware/UAT dan arahan pemilik.
Jangan menganggap approval
TASK-HW-002 memberi izin task lain, install, service, live migration/seed,
produksi, root AGENTS atau refactor source existing. Baca exact allowlist sebelum
melanjutkan. Jangan membaca/mengubah Old-docs. Jangan mengulang QA source yang
sudah lulus tanpa perubahan atau temuan baru.
