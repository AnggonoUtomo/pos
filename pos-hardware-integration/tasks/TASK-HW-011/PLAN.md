# PLAN: TASK-HW-011

**Status:** DRAFT, belum diizinkan untuk implementasi.
**Version:** 1.
**Tanggal:** 21 November 2026.
**Owner:** Pemilik project POS.

## Baseline read-only

HEAD dan origin/main sudah sama pada commit
`96bddfe7d05d8f6f5ce2ff30c74fd3f9622fe08d`. TASK-HW-002 selesai dan dipush
sesuai perintah user. Empat LSP staged dan lima LSP untracked milik user
dipertahankan; tidak ikut commit.

Pembacaan aktual: middleware scanner, route, UI/guide, test guard, harness,
dokumen module, arsitektur dan struktur canonical. APP_ENV lokal production;
POS_HARDWARE_ENABLED belum tercantum di .env. Config cache dan routes-v7.php
ada. Tidak membaca credential atau menjalankan bootstrap database produksi.

Saat ini middleware menolak production walaupun flag true. Route produksi yang
dicache sebelum module ditambahkan juga perlu dibangun ulang saat aktivasi.
PLAN TASK-HW-002 yang disetujui tetap snapshot, tidak diedit untuk scope ini.
Tetap single company, multi-gudang, tanpa tenant atau multi-cabang.

## Scope

Buka akses **diagnostik barcode read-only** pada production ketika feature flag
true. Login, verified, inventory.view, validasi, pembatasan request dan no-store
tetap berlaku. Default flag di source tetap false. Instalasi toko ini memakai
POS_HARDWARE_ENABLED=true setelah verifikasi dan persetujuan aktivasi.

Judul/panduan diperjelas menjadi uji scanner, dengan status perangkat fisik belum
diuji. Ketikan manual dan scanner mode keyboard/HID memakai input fokus + Enter
yang sama; tidak membuka USB/Bluetooth API atau listener barcode global.
Akses langsung lewat /platform/hardware/scanner; tanpa perubahan menu global.

Ini pengecualian akses production untuk halaman diagnostik, **bukan rollout
printer/gateway atau sertifikasi hardware** sebelum physical UAT. Tidak mengubah
POS, scan pembelian, cart, harga, stok/FIFO, payments, receipt, role/grant, data,
dependency, driver, service OS atau scheduler. Tidak ada migration/seeder toko.

## File allowlist

Existing source yang boleh diubah setelah approval:

1. `app/Modules/Platform/Hardware/Presentation/Middleware/EnsureVirtualHardwareEnabled.php`
2. `app/Modules/Platform/Hardware/README.md`
3. `resources/js/pages/platform/hardware/scanner.tsx`
4. `resources/js/pages/platform/hardware/scanner.guide.ts`
5. `tests/Feature/Modules/Platform/Hardware/HardwareDisabledRegressionTest.php`
6. `scripts/qa/hardware-scanner-browser.mjs`
7. `docs/modules/Platform/Hardware/README.md`
8. `docs/modules/Platform/Hardware/specification.md`
9. `docs/user-guide/UJI-SCANNER.md`

Tidak rename middleware pada pekerjaan ini untuk membatasi perubahan route;
nama class existing tidak menjadi dasar behavior, config flag tetap authority.
Test dan harness diperbarui mengikuti akses production, bukan dilonggarkan.

Dokumen task baru dan metadata companion:

10. `pos-hardware-integration/tasks/TASK-HW-011/README.md`
11. `pos-hardware-integration/tasks/TASK-HW-011/PLAN.md`
12. `pos-hardware-integration/tasks/TASK-HW-011/APPROVAL.md`
13. `pos-hardware-integration/tasks/TASK-HW-011/tasks.md`
14. `pos-hardware-integration/tasks/TASK-HW-011/REPORT.md`
15. `pos-hardware-integration/STATUS.md`
16. `pos-hardware-integration/NEXT_TASK.md`
17. `pos-hardware-integration/SESSION_HANDOFF.md`
18. `pos-hardware-integration/TASK_INDEX.md`

Runtime deployment, tidak di-commit:

19. `.env`: tambah/ubah hanya POS_HARDWARE_ENABLED, tanpa mengubah APP_ENV,
    debug, APP_KEY, database, cookie atau setting lainnya.
20. `bootstrap/cache/config.php`: output php artisan config:cache.
21. `bootstrap/cache/routes-v7.php`: output php artisan route:cache.

Output build public/build dan temporary Vite cache diperbolehkan sebagai
generated artifact. QA memakai direktori nonce ignored
storage/framework/testing/hardware-scanner-{nonce}, seperti harness existing.
Backup konfigurasi/cache sebelum deployment hanya ke direktori privat ignored
storage/app/private/hardware-activation-{nonce}, tidak dibuka melalui web,
tidak dicetak isinya, tidak di-commit. Direktori runtime nonce bukan wildcard
allowlist source. Jangan menyentuh Old-docs, file user, root AGENTS atau
PLAN/APPROVAL TASK-HW-002.

## Contract and regressions

| Bagian | Saat ini | Setelah disetujui |
| --- | --- | --- |
| Default source | OFF | Tetap OFF |
| Production, flag OFF | 404 | Tetap 404 termasuk super-system |
| Production, flag ON | 404 | Auth/verified/permission, lalu halaman/lookup |
| Local/testing ON | Halaman/lookup | Tetap tersedia |
| Resolve | GET, code saja, enam field identitas | Tidak berubah |
| Database/ledger | Tidak dimutasi lookup | Tetap tidak dimutasi |
| POS/pembelian | Scan existing | Tidak berubah |

Barcode/SKU/satuan jual mengikuti SaleItemCatalog existing. Tidak memperluas
lookup menjadi satuan purchase-only. Permission tetap inventory.view tanpa
grant otomatis. OFF diperiksa di middleware runtime termasuk route compiled.
Tidak mengubah rate limit, serial queue, timeout, cancel atau no-auto-retry.

## Implementation steps

1. Catat persetujuan eksplisit atas PLAN ini dan fingerprint, lalu jalankan
   check_plan.py --require-approved. Tidak memakai izin Git sebagai approval.
2. Tambah/ubah test terfokus terlebih dahulu: production ON berizin berhasil,
   guest/tanpa permission ditolak; OFF tetap tertutup setelah route compile.
3. Ubah guard seminimal mungkin dan perbarui label/panduan/batas UAT. Harness
   boleh menjalankan server QA dengan APP_ENV=production setelah fixture setup
   testing; guard setup SQLite tidak dilonggarkan.
4. Jalankan gate terdampak dan browser scanner production-like disposable satu
   kali, bandingkan fingerprint bisnis. Reuse hasil existing yang tidak berubah.
5. Sebelum mengaktifkan instalasi toko, backup konfigurasi/cache secara privat,
   catat hanya status flag lama. Ubah hanya flag, rebuild config dan route cache,
   periksa flag cached serta route/middleware tanpa mencetak credential.
6. Smoke HTTPS guest: endpoint tetap membutuhkan login, bukan terbuka publik.
   Jangan membuat akun/transaksi uji toko. Bila aktivasi gagal, lakukan rollback
   dan laporkan; jangan menyebut ON sebelum diverifikasi.
7. Lengkapi REPORT/checklist, metadata dan save point terpisah. Commit/push task
   baru mengikuti persetujuan pemilik; file user tetap tidak ikut.

## Test/UAT

Execution yang diminta setelah approval: test SQLite disposable, fixture seeder
existing hanya disposable, build dan server PHP localhost sementara yang ditutup
pada finally. Bukan migrasi/seeding database toko atau service OS.

- Hardware feature/unit tests: default OFF, production ON dan OFF, route compile,
  auth/permission/no-store/validation/no-mutation. Tidak menjalankan test terhadap
  config cache/database toko; gunakan isolasi cache/path PHPUnit existing.
- Regresi terfokus POS scan/cart/checkout, plus barcode-unit; bukan full-suite
  berulang. Typecheck, scoped lint, build dan page-guides untuk UI berubah.
- Browser production-like disposable: Enter, nol awal, duplicate, permission,
  queue/error/cancel dan desktop/tablet representatif melalui harness existing.
  Fake input tetap hanya test, bukan asset production. MCP dicoba sesuai Quality,
  fallback Playwright existing bila akses artefak tool tetap terbatas.
- route:list, inspeksi flag effective dan smoke HTTPS guest setelah aktivasi;
  tidak membocorkan config lain atau memerlukan transaksi toko.
- Validator PLAN/REPORT, git diff --check dan exact allowlist review.

## Virtual testing and physical gate

Software acceptance hanya dapat VIRTUAL_PASSED untuk scanner diagnostics dan
akses production-like yang benar-benar diuji. Physical acceptance tetap
PHYSICAL_PENDING. Production diagnostic dapat ON atas persetujuan scope ini,
tetapi tidak berarti HC-4208DB USB/dongle/Bluetooth atau Android sudah verified.

Saat hardware tersedia: cocokkan suffix Enter dan layout keyboard, scan barcode
bernol awal dan satuan berbeda, pastikan hasil dikenali sebelum transaksi nyata.
UAT scanner di POS/pembelian memerlukan perangkat dan pengawasan operator;
simulasi tidak menggantikan bukti fisik. CT420D TSPL/CPCL, driver, media, gateway,
label scan-back, cutter/drawer/receipt bukan scope, tidak diberi status siap.

## Rollback

Sebelum deployment, backup konfigurasi/cache privat di lokasi ignored di atas;
pertahankan izin file dan jangan mengekspos salinannya lewat HTTP/Git/log.
Tidak ada perubahan schema/data yang memerlukan DB restore.

Jika aktivasi gagal: kembalikan flag ke keadaan sebelumnya, rebuild config/route
cache atau pulihkan snapshot privat yang cocok dengan source, verifikasi 404
untuk scanner dan login POS tetap bekerja. Tidak memakai optimize:clear global,
reset worktree atau membatalkan perubahan user. Revert source hanya commit task
sendiri bila diminta. Status ON tidak dilaporkan jika rollback dilakukan.

## Risk & mitigation

| Risiko | Mitigasi |
| --- | --- |
| ON disangka printer sudah siap | Batas scanner-only, PHYSICAL_PENDING dan tanpa driver/gateway |
| Cache lama tetap menolak route/flag | Rebuild dua cache setelah backup, periksa effective config dan route |
| Flag OFF dilewati super-system | Runtime guard non-Gate dan compiled-route test |
| UI menyebut production selalu ditolak | Update UI, guide dan dokumen operator bersama |
| QA production-like menyentuh data toko | Setup hanya SQLite terisolasi, server env terpisah, fingerprint data |
| Salinan cache membawa credential | Backup privat ignored, tidak dicetak/di-commit/dibuka web |
| File user masuk commit | Stage/commit exact task paths, pertahankan LSP index |

STOP jika perlu mutation data, source/path di luar allowlist, permission baru,
dependency, driver, printer IO atau service. Minta persetujuan scope baru.

## Owner approval

Permintaan terbaru user mengizinkan commit/push TASK-HW-002 dan meminta fitur
diaktifkan. Rencana aktivasi ini baru disusun sesudahnya; APPROVAL.md masih
PENDING sampai user menyetujui exact scope ini. Jangan menandainya APPROVED
berdasarkan persetujuan PLAN lama atau keinginan umum fitur ON.

Persetujuan yang diminta mencakup source allowlist, QA disposable, backup
konfigurasi privat serta aktivasi flag dan rebuild cache pada instalasi toko.
Ini izin akses diagnostik production sebelum UAT fisik, bukan konfirmasi
hardware/printer siap produksi. Setelah disetujui, validasi fingerprint dan
--require-approved wajib lulus sebelum implementasi/deployment.
