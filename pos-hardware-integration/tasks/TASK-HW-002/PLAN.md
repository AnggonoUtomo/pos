# PLAN: TASK-HW-002

**Status:** DRAFT.
**Owner:** Pemilik project POS (user).
**Vertical Slice:** VS-HW-02 bagian virtual scanner; VUAT-01/02/03/09 relevan.
**Date:** 2026-11-20.
**Version:** 1.

Judul bagian mengikuti validator bundle; isi menggunakan Bahasa Indonesia.
Ini usulan implementasi, bukan izin menjalankan langkahnya.

## Baseline read-only

Root `C:\laragon\www\pos`, branch main, HEAD dan origin/main sama:
`5e3b4a51ca3da1689ee224ffc393f6971b564984`. Commit/push companion TASK-HW-001
berhasil atas instruksi eksplisit user. Empat file LSP masih staged dan lima
LSP untracked milik user; tidak ikut commit atau diubah.

Baseline checkout/RBAC/Items/scan/receipt pada
[TASK-HW-001/BASELINE.md](../TASK-HW-001/BASELINE.md) tetap berlaku.
PLAN TASK-HW-001 yang disetujui tidak diubah. Stack/identifier/organisasi tetap
Laravel12/Inertia React, ULID, single company + multi-gudang, tanpa tenant/cabang.

Pembacaan tambahan untuk usulan ini:

- `app/Providers/AppServiceProvider.php`: provider module ditemukan otomatis.
- `app/Console/Commands/MakeModuleCommand.php`: scaffold default empat file,
  termasuk migration .gitkeep dan no-op demo seeder. Tidak perlu edit generator.
- `app/Modules/Inventory/Items/Application/Contracts/SaleItemCatalog.php` dan
  DTOs SaleScanData/SaleItemData/SaleUnitData: sudah cukup untuk lookup identitas.
- Adapter Items existing mendahulukan barcode yang direservasi sebelum fallback
  SKU, menyeleksi barang/satuan jual aktif; bukan lookup seluruh jenis satuan.
- `resources/js/app.tsx` dan PageGuide resolver: discovery page/guide otomatis.
- Pencarian source tidak menemukan Platform/Hardware, platform.hardware,
  pos_hardware atau route /platform/hardware. Ini collision check statis, bukan
  inspeksi route cache produksi.

## Scope

Implementasikan hanya halaman **Uji barcode satuan jual (virtual)** pada module
baru Platform/Hardware: input fokus/Enter, resolve barang/satuan aktif melalui
public contract Items, hasil sukses/tidak ditemukan/error dan riwayat uji di
memori tab. Hitungan pemindaian adalah hitungan uji, bukan kuantitas transaksi.

Default `pos_hardware.enabled = false`. Bahkan saat flag true, fitur ini hanya
boleh berjalan pada environment local/testing; production selalu menolak.
Flag/invariant diperiksa middleware sendiri, bukan Gate yang dapat dibypass
super-system. Tidak mengubah .env produksi atau global shared props.

Halaman baru diakses langsung lewat URL development yang didokumentasikan,
tidak melalui menu/palette global. Header, theme, Sonner, shortcut dropdown,
BarcodeInput dan PageGuide menggunakan komponen existing tanpa modifikasi.

Tidak ada data model/tabel baru, persistent job/history, migrasi bisnis, role/
permission baru, grant, scan-to-cart, QRIS, harga/stok, printer/gateway service,
TSPL/CPCL builder, receipt/cutter/drawer atau auto-print. Tidak membuat port/
adapter kosong untuk fitur cetak yang belum punya consumer.

## File allowlist

**Existing application/source files to modify: NONE.** Semua path relatif root
POS. Batas berikut untuk implementasi setelah disetujui, bukan file yang dibuat
saat perencanaan. Tidak ada wildcard atau izin rename/refactor file existing.

File module baru:

1. `app/Modules/Platform/Hardware/ServiceProvider.php`
2. `app/Modules/Platform/Hardware/Routes/web.php`
3. `app/Modules/Platform/Hardware/Config/hardware.php`
4. `app/Modules/Platform/Hardware/Database/Migrations/.gitkeep`
5. `app/Modules/Platform/Hardware/Database/Seeders/HardwareDemoSeeder.php`
6. `app/Modules/Platform/Hardware/Presentation/Middleware/EnsureVirtualHardwareEnabled.php`
7. `app/Modules/Platform/Hardware/Presentation/Controllers/ScannerController.php`
8. `app/Modules/Platform/Hardware/Presentation/Requests/ResolveScanRequest.php`
9. `app/Modules/Platform/Hardware/Application/Queries/ResolveScannedItem.php`
10. `app/Modules/Platform/Hardware/README.md`

Frontend baru, colocated dan tanpa perubahan komponen shared:

11. `resources/js/pages/platform/hardware/scanner.tsx`
12. `resources/js/pages/platform/hardware/scanner.guide.ts`
13. `resources/js/pages/platform/hardware/components/scan-results.tsx`
14. `resources/js/pages/platform/hardware/hooks/use-scanner-diagnostics.ts`
15. `resources/js/pages/platform/hardware/types.ts`

Test/fixture/harness baru:

16. `tests/Feature/Modules/Platform/Hardware/ScannerWorkspaceTest.php`
17. `tests/Feature/Modules/Platform/Hardware/HardwareDisabledRegressionTest.php`
18. `tests/Unit/Modules/Platform/Hardware/ResolveScannedItemTest.php`
19. `tests/Browser/Hardware/fake-barcode-input.mjs`
20. `tests/Browser/Hardware/scanner-fixture.php`
21. `scripts/qa/hardware-scanner-browser.mjs`

Dokumen module/operator baru setelah approval implementasi:

22. `docs/modules/Platform/Hardware/README.md`
23. `docs/modules/Platform/Hardware/specification.md`
24. `docs/user-guide/UJI-SCANNER.md`

Artefak task ini yang boleh dibuat/diperbarui untuk plan/report:

25. `pos-hardware-integration/tasks/TASK-HW-002/README.md`
26. `pos-hardware-integration/tasks/TASK-HW-002/PLAN.md`
27. `pos-hardware-integration/tasks/TASK-HW-002/APPROVAL.md`
28. `pos-hardware-integration/tasks/TASK-HW-002/tasks.md`
29. `pos-hardware-integration/tasks/TASK-HW-002/REPORT.md`

Metadata companion existing yang boleh diperbarui **setelah implementasi dan
report terverifikasi**, agar tidak lagi menyatakan repo belum disediakan:

30. `pos-hardware-integration/STATUS.md`
31. `pos-hardware-integration/NEXT_TASK.md`
32. `pos-hardware-integration/SESSION_HANDOFF.md`
33. `pos-hardware-integration/TASK_INDEX.md`

Perubahan empat file tersebut hanya status/tautan/batas scope aktual. Tidak
mengubah blueprint atau approval TASK-HW-001 dan tidak memberi izin coding
task berikutnya. TASK-HW-002 tetap scanner virtual, bukan physical USB UAT.

Dokumen canonical module tetap di docs/modules; companion tidak dipindahkan ke
docs. Root AGENTS/docs existing tidak diubah. Domain, repository, migration SQL
dan event baru tidak diperlukan untuk query read-only ini.

Generated QA artifacts hanya boleh di direktori acak ignored
`storage/framework/testing/hardware-scanner-{nonce}`: SQLite, cache/view/session
QA, screenshot, report JSON/JUnit. Nonce adalah pengecualian runtime-artifact
saja, bukan wildcard source allowlist. public/build dan node_modules/.vite-temp
boleh berubah sebagai output build normal, bukan source yang di-commit.

## Contract and regressions

Usulan route module baru, nama belum ditemukan di source existing:

| Route | Nama | Guard dan hasil |
| --- | --- | --- |
| GET /platform/hardware/scanner | platform.hardware.scanner.index | web, feature/env, auth, verified, can:inventory.view; Inertia platform/hardware/scanner |
| GET /platform/hardware/scanner/resolve | platform.hardware.scanner.resolve | Guard sama; JSON hasil lookup identitas saja |

Feature/env gate menolak404 ketika OFF atau production, termasuk super-system
dan route-cache replay dari testing. Ketika ON/local: guest mengikuti auth
web, user tanpa inventory.view mendapat403. Tidak mengubah permission existing.
Middleware can per action memakai HasMiddleware. Rate limit memanfaatkan
middleware throttle bawaan pada resolve, tanpa limiter/alias global baru.

Input resolve hanya `code` berupa string ASCII printable1..64 karakter;
trim mengikuti semantik existing, nol awal tidak dibuang. Tenant/branch,
harga atau raw device command bukan input. Response200 berisi identitas
item_id/sku/name dan unit_id/unit_name/barcode yang ditemukan; tidak memuat
harga, HPP, stok, kontak atau field internal. Unknown/nonaktif/terhapus memakai
422 validation error sesuai pola Laravel, Cache-Control private/no-store.
SKU fallback dan barcode reservasi mengikuti public contract, tidak ditulis ulang.

Application mengimpor public contract/DTOs Items saja; tidak mengimpor adapter,
model, repository atau controller privat. Tidak memakai endpoint POS yang
memerlukan gudang/pelanggan, karena uji ini bukan quote/penjualan. Keterbatasan
satuan jual dijelaskan di UI/guide, bukan diam-diam mengubah lookup pembelian.

Input bukan listener global. Antrean terurut maksimal20 request, hasil sementara
maksimal20 entri; scan sengaja sama dua kali menghasilkan dua hasil. Tidak
mereset konteks POS, tidak retry otomatis hasil scan timeout, abaikan respons
stale setelah clear/unmount. Alt+S fokus input, Alt+R konfirmasi hapus hasil,
F1 PageGuide existing; shortcut tidak mengambil fokus dari dialog lain.

Tabel hasil uji sementara bukan daftar master/riwayat persisten sehingga bukan
target pagination global. Tidak ada data transaksi yang dipotong. Mutasi CRUD
dan business audit tidak ada; pembacaan tidak membuat activity log palsu.

Regresi flag OFF: tidak ada menu/hotkey/request hardware pada halaman existing,
tidak ada listener/IO/job/polling, direct endpoint ditolak. Login, scan POS/
pembelian, quote, checkout, FIFO, payments, receipt dan recovery tetap existing.

## Implementation steps

1. Pastikan APPROVAL task ini APPROVED dan fingerprint cocok; validator
   --require-approved lulus. Periksa tidak ada collision baru atau drift source.
2. Dengan bootstrap testing/cache terisolasi, jalankan module generator dry-run
   Platform Hardware lalu generator default tanpa --force/--with-tests. Empat
   outputnya sudah tercakup allowlist. Tambah test terfokus flag/env/permission
   dan query terlebih dahulu. Tidak mengubah generator atau provider global.
3. Isi provider/config/routes/middleware/query/controller/request; reuse public
   lookup Items. Demo seeder scaffold menjadi no-op yang menjelaskan alasan:
   module tidak memiliki master/transaksi tersimpan dan tidak boleh mengubah
   katalog toko. Fixture seeding hanya isolated QA melalui seeder owner existing.
4. Selesaikan halaman input/hasil, queue/cancel/error, shortcut, PageGuide dan
   dokumentasi operator dengan shadcn/Lucide/theme existing.
5. Gunakan fake scanner hanya di harness browser untuk mengetik kode+Enter,
   deliberate duplicate, disconnect/timeout injection. Selesaikan focused test,
   satu gate regresi akhir dan browser alur berubah; jangan ulang gate yang
   sudah hijau pada source identik.
6. Catat REPORT dan check-report, source allowlist, no-mutation evidence serta
   software/physical verdict terpisah. Flag toko tetap OFF dan production
   tidak diaktifkan. Perbarui empat metadata companion terdaftar berdasarkan
   hasil aktual, tanpa memulai task berikutnya.

## Test/UAT

Command berikut hanya dijalankan **setelah approval execution task ini**:

```text
python pos-hardware-integration/scripts/check_plan.py pos-hardware-integration/tasks/TASK-HW-002/PLAN.md --require-approved
php artisan module:make Platform Hardware --dry-run
php artisan module:make Platform Hardware
php artisan test --compact tests/Feature/Modules/Platform/Hardware tests/Unit/Modules/Platform/Hardware
php artisan test --compact tests/Feature/Modules/Sales/POS/PosCartTest.php tests/Feature/Modules/Sales/POS/PosCheckoutTest.php tests/Feature/Modules/Sales/POS/InvoiceHistoryTest.php tests/Feature/Modules/Inventory/Items/ItemsHttpTest.php
node scripts/qa/barcode-unit.mjs
node scripts/qa/page-guides.mjs
npx tsc --noEmit
npx eslint resources/js/pages/platform/hardware
npm run build
node scripts/qa/hardware-scanner-browser.mjs
python pos-hardware-integration/scripts/check_report.py pos-hardware-integration/tasks/TASK-HW-002/REPORT.md
git diff --check
```

Route inspection `php artisan route:list --path=platform/hardware` juga memakai
bootstrap testing. Tidak menjalankan Artisan dengan cache/.env database produksi.
Gunakan PHP/Node/Python/Chrome/Playwright existing, tanpa download atau install.
Tidak memakai lint --fix atau formatter global.

Persetujuan execution yang diminta mencakup QA SQLite disposable dan migration/
fixture seeder existing **hanya pada database disposable yang diverifikasi**;
tidak mencakup migrate/seed database toko. Browser harness boleh menjalankan
PHP test server sementara di127.0.0.1 port bebas, windowsHide=true, ditutup
bersama browser pada finally. Bukan service/runner OS. Tidak ada perubahan
Apache/Laragon/.env/config cache produksi atau koneksi printer.

Sebelum bootstrap, fixture harus memverifikasi APP_ENV=testing, DB_CONNECTION=
sqlite, DB_DATABASE dan semua cache/storage paths di root QA nonce tersebut;
tolak jika path/database salah. APP_KEY/password QA acak, tidak dicetak.
Fingerprint business rows/master/grants sebelum/sesudah lookup dan scan harus
identik; session/cache QA boleh berubah sesuai auth. Jangan mengekspor data toko.

Browser: desktop1366x768 dan tablet1024x768/768x1024. Uji focus/Enter, nol awal,
intentional duplicate, unknown, permission denied, queue full, timeout,
clear/unmount stale response, F1/Sheet, Light/Dark representatif, overflow dan
console/network. Tidak mengulang seluruh tema. DevTools MCP dahulu; fallback
instalasi browser existing jika tool tidak tersedia. Tidak menguji pos.test
dengan akun/transaksi asli.

## Virtual testing and physical gate

FakeBarcodeInput berada di tests/Browser/Hardware, tidak di namespace production
atau build application. Emit karakter+Enter, bukan langsung memanggil setter
hasil supaya benar-benar memeriksa alur input. Fixture mencakup kamera CCTV
UNIT/BOX, MCB UNIT, kode dengan nol awal, unknown serta satuan purchase-only
yang ditolak secara jujur oleh lookup satuan jual.

VUAT01/02: input sintetik, repeated scan dan lookup; VUAT03: layout/touch
emulasi, bukan Android Bluetooth; VUAT09: flag OFF tanpa side effect pada POS.
VUAT08 diterjemahkan menjadi permission/resource isolation milik instalasi,
bukan tenant/branch. Tidak ada gateway token/job pada slice scanner ini.

FakeGateway, FakePrintTransport, TSPL/CPCL snapshots, label preview generator
dan scan-back tidak applicable pada slice input scanner; dikerjakan dalam
PLAN label/gateway tersendiri. Tidak membuat stub/adapters tanpa consumer.
Profil CT420D tetap label TSPL/CPCL, bukan ESC/POS. Manual firmware/protokol
belum cukup untuk menulis driver dan bukan blocker uji scanner virtual.

REPORT kelak boleh menyebut VIRTUAL_PASSED hanya untuk **slice scanner** jika
semua acceptance terkait lulus, bukan seluruh hardware integration. Physical
tetap PHYSICAL_PENDING: HC-4208DB USB/dongle/Bluetooth HID, suffix Enter nyata,
latency dan Android asli, CT420D driver/media/print quality/scan-back belum diuji.
HARDWARE_VERIFIED dan pilot memerlukan UAT fisik serta approval rollout terpisah.

## Rollback

Belum ada perubahan runtime saat PLAN ditulis. Implementasi kelak tidak punya
DDL atau data module, jadi tidak memerlukan DB down/reset. Flag OFF menutup
endpoint; environment production tetap tertutup terlepas flag. Jangan hanya
bergantung pada route cache kondisional untuk pengamanan runtime.

Jika deployment parsial tidak lolos typecheck/build/test, jangan aktifkan atau
deploy. Revert hanya commit task sendiri setelah persetujuan, tidak reset user
worktree. Lepas file module/frontend baru secara terkontrol bila diperlukan,
rebuild asset/route cache hanya pada deployment yang diizinkan. Tidak menghapus
provider/module/tabel existing. Fixture nonce dibersihkan aman sesudah path
absolut diverifikasi di bawah storage/framework/testing; bukti minimal boleh
dipertahankan ignored tanpa credential.

## Risk & mitigation

| Risiko | Mitigasi |
| --- | --- |
| Satuan purchase-only disangka barcode rusak | UI jelas uji satuan jual; tidak ubah contract Items |
| Gate super-system melewati flag | Middleware feature/env bukan policy; test super-system OFF/production |
| Route cached ON pindah environment | Runtime middleware tetap404 di production/OFF |
| Uji scanner membuat transaksi | Query identitas saja; fingerprint data/grant/ledger identik |
| Bootstrap QA menyentuh config/data toko | Cache/path/SQLite guard sebelum bootstrap; gagal tertutup |
| Browser mengetik saat dialog pembayaran | Halaman terpisah, input fokus saja; no global listener atau POS imports |
| Request tertunda menimpa hasil baru | Serial queue, abort/generation, cap20 dan tidak auto-retry |
| Dummy print dimaknai cetak nyata | Tidak ada printer/gateway pada scope; verdict scanner-only |
| File LSP ikut Git | Exact path staging/commit, index staged user dipertahankan |

STOP bila butuh source existing, dependency baru, field/tabel/grant baru,
live DB, OS service, printer IO atau path di luar allowlist. Revisi PLAN dan
minta approval sebelum melanjutkan. Tidak menutup gap existing di luar scope.

## Owner approval

[APPROVAL.md](APPROVAL.md) masih PENDING. User diminta menyetujui satu paket:
module baru scanner read-only local/testing dengan flag OFF, exact allowlist,
reuse inventory.view tanpa grant, serta execution QA disposable/temp server
yang dibatasi di atas. Tidak meminta produksi, device install atau gateway.

Persetujuan TASK-HW-001 dan perintah "commit dan push dulu baru lanjutkan"
ditafsirkan untuk save point terdahulu dan penyusunan PLAN ini, bukan approval
source task baru. Commit/push implementasi dan deployment tetap memerlukan
izin sesuai aturan companion; jangan stage/commit/push task ini sekarang.

Sebelum satu baris source ditulis: approval eksplisit dicatat, fingerprint PLAN
diperiksa, jalankan check_plan.py --require-approved. Bila scope direvisi,
approval lama tidak dipakai untuk revision baru.
