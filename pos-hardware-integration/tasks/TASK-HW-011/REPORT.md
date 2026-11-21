# REPORT: TASK-HW-011

**Tanggal:** 21 November 2026.
**Scope:** Akses production diagnostik scanner, bukan printer/gateway.

## Scope delivered

Halaman /platform/hardware/scanner dan lookup identitas barcode satuan jual
dapat digunakan pada production ketika flag aktif. Instalasi https://pos.test
sudah memakai POS_HARDWARE_ENABLED=true, effective config cache terverifikasi.
Login, verified, inventory.view, throttle resolve dan no-store tetap berlaku.
Source default flag tetap false. Tidak menambahkan navigasi atau mengubah POS.

PLAN version1 disetujui eksplisit dengan jawaban user "Setuju, aktifkan
diagnostik scanner sesuai PLAN". SHA256
10DD7FB3D65ACEB12A122C7CB7B4BE472FC0FB56E57104F384DAFF5860654D41
tetap identik; validator --require-approved lulus sebelum implementasi.

## File changes

Perubahan source mengikuti exact allowlist PLAN: satu guard middleware,
judul/badge/panduan scanner, test production ON/OFF/permission, harness browser
yang menjalankan server production-like setelah setup fixture testing,
dokumen module/operator dan metadata companion. Tidak rename class middleware
atau mengubah route/controller/query/contract existing.

Runtime: tambah hanya POS_HARDWARE_ENABLED pada .env, rebuild
bootstrap/cache/config.php dan routes-v7.php. Parse Dotenv sebelum/sesudah
memastikan satu-satunya key berubah adalah POS_HARDWARE_ENABLED. Tidak mencetak
konfigurasi lain. .env/cache/build/backup/QA tidak masuk commit.

## Tests

- `python .../check_plan.py .../TASK-HW-011/PLAN.md --require-approved`: PASS.
- RED HardwareDisabledRegressionTest: dua test gagal karena production404;
  setelah implementasi GREEN 4 test/27 assertion.
- `php vendor/phpunit/phpunit/phpunit tests/Feature/Modules/Platform/Hardware
  tests/Unit/Modules/Platform/Hardware
  tests/Feature/Modules/Sales/POS/PosCartTest.php
  tests/Feature/Modules/Sales/POS/PosCheckoutTest.php --colors=never`:
  PASS39 test/449 assertion, cache PHPUnit terisolasi dan SQLite memory.
- Test OFF kemudian dibuat eksplisit, tidak mengasumsikan flag .env toko OFF.
  Subset HardwareDisabledRegressionTest setelah aktivasi: PASS4 test/26 assertion.
  Ini subset pengulangan terfokus, bukan penambahan terhadap jumlah gate39.
- `npx --no-install tsc --noEmit`: PASS.
- `npx --no-install eslint resources/js/pages/platform/hardware`: PASS.
- Scoped Pint --test untuk middleware dan regression test: PASS.
- `node scripts/qa/barcode-unit.mjs`: PASS.
- `node scripts/qa/page-guides.mjs`: PASS33 halaman/panduan.
- `npm run build`: PASS2959 module,30.23 detik. Build yang hasil prosesnya
  hilang akibat interupsi tidak diklaim lulus; satu build pengganti dicatat ini.
- `node scripts/qa/hardware-scanner-browser.mjs`: PASS12 cek, errors kosong,
  expectedHttpErrors3, serverStopped=true. Chrome/Playwright existing, tanpa install.
  Setup SQLite/cache/path tetap testing terisolasi; hanya server HTTP memakai
  APP_ENV=production. Tidak melonggarkan fixture guard.

Artefak browser ignored:
storage/framework/testing/hardware-scanner-9d2ca02114c52165/report.json,
before.json/after.json, desktop.png, tablet-landscape.png,
tablet-portrait-dark.png. Screenshot desktop Light dan portrait Dark diperiksa
visual; tabel portrait scroll lokal, tidak memaksa overflow seluruh workspace.
Fingerprint71 tabel bisnis/master/grant/audit identik; sesi/cache QA dikecualikan.

Chrome DevTools MCP: BLOCKED pada kelanjutan ini karena namespace tidak tersedia
di daftar tool. Fallback Playwright/Chrome existing benar-benar dijalankan,
bukan mengklaim hasilnya sebagai MCP. Browser/server sementara sudah ditutup.

Aktivasi lokal:

- Tiga snapshot konfigurasi/cache privat berhasil dan hash sama dengan sumber.
- `php artisan config:cache`: PASS; `php artisan route:cache`: PASS.
- Inspeksi Laravel cached config: environment production, hardware_enabled=true,
  configurationIsCached/routesAreCached=true, dua named route ditemukan.
- `php artisan route:list --path=platform/hardware -vv`: PASS2 GET/HEAD route,
  web/guard/auth/verified/can:inventory.view, resolve throttle120/menit.
- HTTPS HEAD tanpa skip TLS: login200, scanner302 ke https://pos.test/login,
  resolve dengan Accept JSON401, URL salinan privat404. ssl_verify_result=0.
- Pint dan Hardware guard subset setelah aktivasi PASS. Source aplikasi tidak
  berubah lagi sesudah gate browser/build; tidak mengulang seluruh regresi.

## Invariants and regressions

Tidak ada mutation ledger/master/grant/transaksi toko, migration/seeder toko,
atau perubahan checkout, harga, stok/FIFO, pembayaran, invoice dan receipt.
Barcode/SKU/satuan jual tetap public SaleItemCatalog, termasuk penolakan satuan
purchase-only. Tidak ada tabel, permission, OS runner, driver atau dependency baru.
Single company + multi-gudang dipertahankan, tanpa tenant/cabang.

Feature OFF tetap ditolak termasuk super-system dan compiled-route replay;
production ON tetap membutuhkan session/permission. Serial queue, timeout,
cancel/stale response dan no-auto-retry ikut lulus browser.
Empat LSP staged serta lima LSP untracked user tidak diubah/diikutkan Git.

## Hardware evidence

Input karakter+Enter disimulasikan FakeBarcodeInput pada Chrome, bukan USB,
Bluetooth atau driver scanner nyata. Halaman menerima input mode keyboard/HID
jika perangkat mengirim teks+Enter; kompatibilitas HC-4208DB belum dibuktikan.
Tidak ada command TSPL/CPCL, gateway, print transport atau perangkat dipasang.

## Virtual and physical acceptance

Software acceptance: VIRTUAL_PASSED
Physical acceptance: PHYSICAL_PENDING

Production diagnostic: ON, APPROVED untuk halaman read-only pada instalasi ini.
Printer/gateway rollout: NOT_IMPLEMENTED / NOT_APPROVED.
HC-4208DB USB/dongle/Bluetooth dan CT420D driver/media/print/scan-back tetap
menunggu perangkat dan UAT fisik. Ini bukan pernyataan seluruh hardware siap.

## Rollback verification

Snapshot privat:
storage/app/private/hardware-activation-a9813b349f6c408aaaab9108d7db41da/.
environment.snapshot, config.snapshot dan routes.snapshot hash-identik dengan
sumber sebelum aktivasi. Folder ignored; URL salinan privat diuji404.
Isi snapshot tidak dicetak/di-commit; mengandung konfigurasi sensitif dan harus
tetap privat dengan izin akses filesystem instalasi.

Sebelumnya flag tidak ada. Rollback: hapus penambahan flag atau set false,
rebuild config/route cache dan verifikasi scanner404/login tetap berfungsi;
snapshot lama hanya dipulihkan bersama source yang cocok. Tidak perlu DB restore.
Kemampuan OFF sudah diuji pada regression test compiled route. Rollback runtime
production tidak dieksekusi karena aktivasi sukses; tidak diklaim sebagai uji
restore produksi. Tidak mengubah ACL/OS atau menghapus snapshot otomatis.

## Remaining risks

- UAT perangkat nyata tetap pending, termasuk suffix Enter, layout keyboard,
  USB/dongle/Bluetooth dan cetak/scan-back label CT420D.
- Akses langsung URL saja sesuai PLAN; tidak ada menu baru.
- Smoke toko hanya guest/TLS/config/route; tidak login memakai akun asli atau
  membuat akun/transaksi uji toko. Akses berizin diperiksa pada QA disposable.
- Backup ini konfigurasi privat, bukan bukti backup/restore database atau
  pengesahan pending operasional MVP sebelumnya.

## Final status

COMPLETE untuk scope TASK-HW-011: diagnostik scanner aktif di production dan
guard/QA/deployment smoke lulus. PLAN lama tetap snapshot; hardware fisik tetap
pending. Commit/push TASK-HW-00296bddfe sudah diverifikasi pada origin/main
sebelum perubahan ini. Save point task aktivasi dibuat sesudah review akhir.
