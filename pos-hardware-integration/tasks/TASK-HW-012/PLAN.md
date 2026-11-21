# PLAN: TASK-HW-012

**Status:** DRAFT; source/runtime belum diizinkan oleh dokumen ini.
**Version:** 1.
**Tanggal:** 21 November 2026.
**Vertical slice:** konfigurasi dan pengiriman label melalui QZ + tes scanner.

## Baseline read-only

Workspace C:\laragon\www\pos, main pada efbf811. TASK-HW-002 dan011 sudah
dipush; diagnostik scanner ON di production sesuai approval011. Empat LSP
staged dan lima LSP untracked user dipertahankan, bukan bagian task.

User mengonfirmasi hanya label CT420D + HC-4208DB dan memilih QZ Tray dengan
pemasangan manual. Ini bukan izin mengubah source tanpa exact PLAN/approval.
Root/companion AGENTS, dokumen aktif, profil hardware, arsitektur/folder, provider,
SaleItemCatalog/DTOs, permission seeder, shell, shared props, label browser dan
command backup existing telah diperiksa. Old-docs tidak dibuka.

Baseline belum punya QZ SDK, persistence hardware atau gateway nyata.
SaleItemCatalog hanya satuan jual; label semua satuan memerlukan public contract
baru milik Inventory/Items, bukan mengambil model/repository privat dari Hardware.
Barcode helper Items dapat diekstrak menjadi helper reusable tanpa perubahan
hasil encoder atau API lama. Seluruh alur bisnis POS tetap existing.

## Scope

Implementasikan satu paket sesuai [PRD](PRD.md): workspace Perangkat POS,
konfigurasi printer tersimpan, discovery QZ yang dipicu user, label preview dan
dispatch berizin/idempotent, riwayat dan reprint beralasan, tes input scanner.
QZ adalah konektor lokal browser, bukan daemon polling Laravel/SaaS baru.
Rancangan outbound gateway dalam blueprint generik bukan implementasi yang
dipaksakan; keputusan QZ driver-image dicatat dalam ADR canonical.

Driver-image melalui QZ dipakai karena manual TSPL/CPCL CT420D belum tersedia.
Jangan menebak raw command atau mengklaim driver Linux/Windows CT420D sudah
terverifikasi. Software cross-platform tidak membuktikan driver/model fisik.
Default localhost; tablet memerlukan print-server WSS yang disiapkan admin,
host allowlist dan trust sertifikat, tanpa membuka LAN/port otomatis.

Tidak ada printer struk, ESC/POS, raw TSPL/CPCL, cutter/drawer, auto-print checkout,
silent certificate setup, OS service/runner, instalasi QZ desktop/driver, Bluetooth
pairing atau deteksi merek scanner dari keyboard. Tidak ada tenant/multi-cabang.
Tidak mengubah harga, stok/FIFO, pembelian, penjualan, pembayaran atau receipt.

## File allowlist

Path relatif root; tidak ada wildcard source atau izin refactor module lain.

Existing source/documentation yang boleh diubah setelah approval:

1. `app/Modules/Platform/Hardware/ServiceProvider.php`
2. `app/Modules/Platform/Hardware/Routes/web.php`
3. `app/Modules/Platform/Hardware/Config/hardware.php`
4. `app/Modules/Platform/Hardware/README.md`
5. `app/Modules/Platform/Hardware/Database/Seeders/HardwareDemoSeeder.php`
6. `resources/js/pages/platform/hardware/types.ts`
7. `resources/js/pages/platform/hardware/scanner.tsx`
8. `resources/js/pages/platform/hardware/scanner.guide.ts`
9. `tests/Feature/Modules/Platform/Hardware/HardwareDisabledRegressionTest.php`
10. `app/Modules/Inventory/Items/ServiceProvider.php`
11. `resources/js/pages/inventory/items/lib/barcode.ts`
12. `scripts/qa/barcode-unit.mjs`
13. `app/Http/Middleware/HandleInertiaRequests.php`
14. `resources/js/types/index.ts`
15. `resources/js/components/app-sidebar.tsx`
16. `resources/js/components/command-palette.tsx`
17. `package.json`
18. `package-lock.json`
19. `docs/modules/Platform/Hardware/README.md`
20. `docs/modules/Platform/Hardware/specification.md`
21. `docs/user-guide/UJI-SCANNER.md`
22. `docs/user-guide/README.md`
23. `docs/MODULES.md`
24. `docs/DECISIONS.md`

Source/backend baru yang direncanakan:

25. `app/Modules/Inventory/Items/Application/Contracts/ItemLabelCatalog.php`
26. `app/Modules/Inventory/Items/Application/DTOs/ItemLabelData.php`
27. `app/Modules/Inventory/Items/Infrastructure/Persistence/Repositories/EloquentItemLabelCatalog.php`
28. `app/Modules/Platform/Hardware/Application/Contracts/HardwareRepository.php`
29. `app/Modules/Platform/Hardware/Application/Contracts/HardwareAvailability.php`
30. `app/Modules/Platform/Hardware/Application/DTOs/PrinterProfileData.php`
31. `app/Modules/Platform/Hardware/Application/DTOs/LabelJobData.php`
32. `app/Modules/Platform/Hardware/Application/Actions/SavePrinterProfile.php`
33. `app/Modules/Platform/Hardware/Application/Actions/ChangePrinterProfileStatus.php`
34. `app/Modules/Platform/Hardware/Application/Actions/CreateLabelJob.php`
35. `app/Modules/Platform/Hardware/Application/Actions/DispatchLabelJob.php`
36. `app/Modules/Platform/Hardware/Application/Actions/RecordLabelOutcome.php`
37. `app/Modules/Platform/Hardware/Application/Actions/RequestLabelReprint.php`
38. `app/Modules/Platform/Hardware/Application/Queries/HardwareWorkspace.php`
39. `app/Modules/Platform/Hardware/Domain/Services/LabelSpecification.php`
40. `app/Modules/Platform/Hardware/Domain/Services/PrintJobLifecycle.php`
41. `app/Modules/Platform/Hardware/Infrastructure/Persistence/Repositories/DatabaseHardwareRepository.php`
42. `app/Modules/Platform/Hardware/Infrastructure/Support/ConfiguredHardwareAvailability.php`
43. `app/Modules/Platform/Hardware/Database/Migrations/2026_11_21_000001_create_hardware_printing_tables.php`
44. `app/Modules/Platform/Hardware/Database/Seeders/HardwareAccessSeeder.php`
45. `app/Modules/Platform/Hardware/Presentation/Controllers/HardwareController.php`
46. `app/Modules/Platform/Hardware/Presentation/Controllers/LabelJobController.php`
47. `app/Modules/Platform/Hardware/Presentation/Requests/HardwareRequest.php`

Frontend baru, colocated kecuali helper barcode reusable:

48. `resources/js/lib/barcode.ts`
49. `resources/js/pages/platform/hardware/index.tsx`
50. `resources/js/pages/platform/hardware/index.guide.ts`
51. `resources/js/pages/platform/hardware/components/gateway-panel.tsx`
52. `resources/js/pages/platform/hardware/components/printer-profile-form.tsx`
53. `resources/js/pages/platform/hardware/components/label-composer.tsx`
54. `resources/js/pages/platform/hardware/components/print-job-table.tsx`
55. `resources/js/pages/platform/hardware/components/scanner-test-panel.tsx`
56. `resources/js/pages/platform/hardware/hooks/use-qz-gateway.ts`
57. `resources/js/pages/platform/hardware/hooks/use-label-print.ts`
58. `resources/js/pages/platform/hardware/gateway/qz-gateway.ts`
59. `resources/js/pages/platform/hardware/gateway/qz-tray.d.ts`
60. `resources/js/pages/platform/hardware/lib/label-renderer.ts`

Test/harness dan dokumentasi canonical baru:

61. `tests/Feature/Modules/Platform/Hardware/PrinterProfilesTest.php`
62. `tests/Feature/Modules/Platform/Hardware/LabelJobsTest.php`
63. `tests/Unit/Modules/Platform/Hardware/PrintJobLifecycleTest.php`
64. `tests/Unit/Modules/Platform/Hardware/LabelSpecificationTest.php`
65. `tests/Feature/Modules/Inventory/Items/ItemLabelCatalogTest.php`
66. `tests/Browser/Hardware/fake-qz-gateway.mjs`
67. `tests/Browser/Hardware/label-fixture.php`
68. `scripts/qa/hardware-label-browser.mjs`
69. `scripts/qa/hardware-label-unit.mjs`
70. `docs/user-guide/PRINTER-LABEL.md`
71. `docs/decisions/ADR-020-QZ-LABEL-GATEWAY.md`

Artefak task dan metadata companion:

72. `pos-hardware-integration/tasks/TASK-HW-012/README.md`
73. `pos-hardware-integration/tasks/TASK-HW-012/PRD.md`
74. `pos-hardware-integration/tasks/TASK-HW-012/PLAN.md`
75. `pos-hardware-integration/tasks/TASK-HW-012/APPROVAL.md`
76. `pos-hardware-integration/tasks/TASK-HW-012/tasks.md`
77. `pos-hardware-integration/tasks/TASK-HW-012/REPORT.md`
78. `pos-hardware-integration/STATUS.md`
79. `pos-hardware-integration/NEXT_TASK.md`
80. `pos-hardware-integration/SESSION_HANDOFF.md`
81. `pos-hardware-integration/TASK_INDEX.md`

Source baru dibuat hanya bila diperlukan untuk consumer yang disebutkan; tidak
membuat folder/konten kosong demi daftar. Path lain memerlukan revisi approval.
Tidak mengubah root AGENTS atau task/approval historis001/002/011.

Runtime yang boleh berubah **hanya bila approval mencakup aktivasi aplikasi**:

82. `bootstrap/cache/config.php`
83. `bootstrap/cache/routes-v7.php`

Tidak ada perubahan .env pada task ini. Output node_modules SDK/lock serta
public/build/.vite-temp diperbolehkan sebagai hasil install/build normal.
QA SQLite/cache/storage/screenshot/report hanya root nonce ignored
storage/framework/testing/hardware-label-{nonce}. Backup konfigurasi/cache
hanya storage/app/private/hardware-label-deploy-{nonce}; backup MySQL lewat
command pos:backup --verify existing ke backups/. Tidak mencetak secret.

## Contract and regressions

Semua route baru di prefix/nama platform.hardware, web/auth/verified,
HasMiddleware can per action, CSRF untuk POST/PATCH/DELETE dan rate limit
aksi kirim. Endpoint scanner existing tidak diubah behavior/contract-nya.

| Endpoint usulan | Tanggung jawab dan guard |
| --- | --- |
| GET /platform/hardware | Workspace; hardware.view |
| GET /platform/hardware/catalog | Katalog label; hardware.print + inventory.view |
| POST/PATCH /platform/hardware/printers dan /{printer} | Simpan profil; hardware.manage |
| PATCH /platform/hardware/printers/{printer}/status | Aktif/nonaktif; hardware.manage |
| DELETE/POST /platform/hardware/printers/{printer} dan /restore | Soft delete/restore; hardware.manage |
| POST /platform/hardware/labels | Snapshot job/test label; hardware.print, print flag ON |
| POST /platform/hardware/labels/{job}/dispatch | Claim sekali, aktor/token/version; hardware.print |
| POST /platform/hardware/labels/{job}/outcome | Catat hasil attempt; hardware.print |
| POST /platform/hardware/labels/{job}/reprint | Job baru+alasan+referensi; hardware.reprint + print |
| GET /platform/hardware/labels | Riwayat; own atau semua jika manage |

Nama route canonical diturunkan index/catalog/printers.store/update/status/destroy/
restore/labels.store/dispatch/outcome/reprint/index. Tidak menambah route di POS.
Root hardware.enabled tetap authority workspace; printing_enabled terpisah,
default false dan dipastikan ulang pada Application untuk create/dispatch.
Ack/recovery yang hanya mencatat outcome attempt lama tetap dapat diterima saat
print flag baru OFF agar status tidak menggantung; tidak boleh mengirim ulang.

Tiga tabel module: hardware_printers (ULID, version, config, soft delete),
hardware_label_jobs (ULID, creator, idempotency, snapshot/settings immutable,
status, original_job_id), hardware_print_attempts (ULID, job/claim hash/version,
status dan timestamp). Jobs/attempts tidak soft delete; tidak cascade-delete
histori ketika profil dihapus. Hapus/nonaktif profil ditolak ketika ada queued/
sending yang belum diselesaikan; gunakan status/outcome berizin terlebih dulu.

Application memegang transaksi/orchestration, Domain lifecycle/ukuran murni,
Infrastructure persistence/audit, controller tidak mutation DB langsung.
Activitylog mencatat config, claim/outcome/reprint dengan metadata whitelist,
tanpa token attempt, raw gambar, credential QZ atau payload respons bebas.
UI tidak mengirim raw command, harga/nama/barcode palsu untuk snapshot.

Contract label baru dipublikasikan Inventory/Items: pencarian bounded dan lookup
barang+satuan aktif, barcode nullable ditolak saat label dibuat. ItemLabelData
hanya identitas label. Tidak menambah field harga/stok, tidak mengubah query
penjualan atau mengambil model privat dari Hardware. Label semua satuan bukan
perubahan aturan scan POS/diagnostik satuan jual existing.

QZ adapter hanya connect WSS, printer discovery dan pixel image print, dengan
payload raster dari snapshot. Tidak mengekspor arbitrary QZ API/signing proxy.
Host dari allowlist deployment, default loopback; tidak downgrade TLS. SDK lazy,
tanpa koneksi pada login/dashboard/checkout. Guard OFF di backend dan navigasi
tetap enforced untuk super-system. Konfigurasi dipisah dari klaim readiness.

## Implementation steps

1. Validasi fingerprint/APPROVAL --require-approved. Install hanya SDK npm
   qz-tray2.3.0 yang disetujui, pin versi, --ignore-scripts; tidak desktop QZ,
   driver, certificate atau package lain. Tinjau lock diff dan audit dependency.
2. Tambah focused tests sebelum mutation implementation: profile permission/
   soft delete/version, job snapshot/idempotency/claim/outcome/reprint, flag OFF
   dan public catalog. Implementasikan migration module/ports/bindings/actions.
3. Lengkapi workspace, discovery/pemilihan profil, preview raster, explicit print,
   riwayat/TablePagination, tes input/scan-back, Sonner/shortcut/PageGuide.
   Helper barcode diekstrak dengan re-export API Items lama; browser label
   existing tidak diganti atau dihubungkan otomatis ke QZ.
4. Buat FakeQzGateway/FakePrintTransport test-only, fixture berizin terisolasi
   dan browser desktop/tablet representatif. Jalankan gate akhir terfokus sekali,
   bukan berulang setiap increment. Manual QZ/driver dan physical print pending.
5. Jika pemilik menyetujui aktivasi aplikasi dalam approval ini: pos:backup
   --verify harus lulus dahulu, snapshot config/cache privat, periksa existing
   table fingerprint dan migration status. Jalankan migrate --force hanya path
   migration Hardware baru, seeder HardwareAccessSeeder idempotent tanpa grants
   atau demo toko. Rebuild config/route cache dan smoke HTTPS tanpa login asli.
   Print flag tetap OFF; scanner ON tidak dimatikan. Bila backup/DB gagal,
   tunda deployment dan laporkan, jangan melonggarkan gate.
6. Catat REPORT, validators, source allowlist, software/physical terpisah,
   metadata dan checklist berdasarkan hasil nyata. Commit save point setelah
   increment lulus sesuai root workflow, tanpa LSP. Push hanya setelah seluruh
   paket selesai dan izin Git eksplisit; bukan install/service/pilot tersirat.

## Test/UAT

Approval execution yang diminta: dependency JS tersebut saja, migrate/seed
disposable, test, build, server PHP localhost sementara dengan windowsHide dan
cleanup finally. Fixture menolak sebelum bootstrap bila DB/cache/storage bukan
SQLite/root nonce QA. Tidak memakai cached MySQL production untuk PHPUnit.

- Hardware/Items label focused feature dan Domain unit tests: auth, izin, flags,
  validasi ukuran/copies/host, SQL-like literal search, item/satuan nonaktif/
  terhapus, harga palsu/raw command/field asing, CSRF dan throttling.
- Idempotency: same key/same request satu job, key beda payload konflik, claim
  bersamaan/tab kedua ditolak, token salah/kedaluwarsa ditolak, outcome replay
  idempotent, non-owner/role tanpa izin gagal, ack lost tidak auto-resend.
- Profile conflict/version/delete/restore dan audit whitelist/rollback failure.
- Regresi scan POS/checkout/replay, label browser dan barcode encoding existing;
  test data toko tidak direset. Gate subset ditentukan dari source berubah.
- Renderer: Code128 library existing, exact text/nol awal/quiet zone, batas
  integer-dot/barcode terlalu panjang, text overflow, dimensi/copies dan PNG
  payload image-only. Tidak menguji firmware TSPL yang tidak diimplementasikan.
- Browser MCP jika tersedia; fallback Chrome/Playwright existing dengan gateway
  fake melalui boundary dependency, tidak membuka realQZ/USB/BT/socket printer.
  Uji gateway tidak ada/izin ditolak/list kosong/multi-printer, ukuran/preview,
  submit/double click/2tab/uncertain/reprint, permission, F1/Escape, pagination,
  Light/Dark desktop1366x768 dan tablet1024x768/768x1024 representatif.
- Fingerprint existing master/transaksi/FIFO/payments/grants identik, kecuali
  tabel hardware dan activitylog tindakan yang sengaja dibuat. Tidak menulis
  ulang user/grant atau memakai demo pada toko.
- npx tsc --noEmit, scoped ESLint/Pint, build, barcode-unit, page-guides,
  npm audit, route:list/migration status sesuai scope, check_plan/check_report,
  git diff --check dan allowlist. Reuse hasil source identik.

## Virtual testing and physical gate

VUAT05/06/07/09 relevan: label preview/raster, fake transport/claim/outcome,
offline/ack hilang dan flag OFF; VUAT01/02/03 scanner/touch sintetis. VUAT08
diterjemahkan menjadi akses aktor/job/profil satu instalasi, bukan tenant/cabang.
VUAT04 raw TSPL/CPCL tidak applicable pada driver-image; tidak ditandai lulus.
Tidak memakai real print emulator/service untuk menyiasati physical gate.

QZ desktop, driver, sertifikatLAN/signing, instalasi OS dan realprint tidak
dijalankan oleh agent dalam approval virtual ini. CT420D hardware tetap
PHYSICAL_PENDING sampai driver, media/gap/ukuran, cetak dan scan-back terbukti.
HC-4208DB USB/dongle/BT dan Android tetap pending; ketikan bukan proof hardware.

Flag pengiriman print OFF setelah deployment. Pilot/tes fisik pertama memerlukan
arah pemilik dan perangkat tersedia. Jangan mengaktifkan otomatis karena semua
mock hijau. Tidak menyebut hasil submitted sebagai tercetak atau siap produksi.

## Rollback

Sebelum deployment stateful, wajib backup MySQL yang restore-disposable-nya
terverifikasi serta snapshot config/cache privat. Backup bukan file publik,
tidak di-commit/ditampilkan isinya. Jika migrasi additive gagal, jangan seed,
aktifkan menu atau lanjut deploy. Tinjau actual migration state, jangan migrate
fresh/reset/down tabel existing.

Matikan hanya print flag atau kembali ke commit sendiri dengan persetujuan;
diagnostik scanner existing tetap dapat dipakai. Pending/sending tidak dikirim
ulang otomatis. Riwayat hardware/audit dipertahankan, bukan dihapus untuk reset.
Config/route cache diregenerasi atau dipulihkan bersama source yang sesuai.
Tidak menghapus QZ/driver/setting OS user, mengubah cert/firewall, atau reset
worktree. Dependency dibatalkan hanya perubahan SDK/lock task sendiri, bukan
melakukan npm audit fix/global update tanpa arahan.

## Risk & mitigation

| Risiko | Mitigasi |
| --- | --- |
| QZ connected dianggap printer siap | Pisahkan gateway/printer terdaftar/hasil fisik |
| Driver gambar CT420D belum terbukti | Tidak raw-guess; batas driver dan UAT tertulis |
| Scanner keyboard ditebak mereknya | Tes input dan exact scan-back, bukan klaim device ID |
| Tablet localhost menuju tablet sendiri | Dokumen PC print-server WSS + host allowlist/admin trust |
| Java/QZ/cert warning mengganggu | Manual admin, dialog jujur, tidak auto-install/bypass |
| Dua tab/double click mencetak ganda | Idempotency+single claim+attempt token dan no auto-retry |
| Reload saat print/ack lost | Hasil uncertain, reprint baru beralasan setelah cek fisik |
| Arbitrary command/signing endpoint | Image-only, snapshot server, tidak ada generic signer |
| Global navigation bocor saat OFF | Shared availability tanpa query DB; backend guard dan UI tests |
| Migration/seed merusak toko | Backup verified, exact path additive, permission only/no grants |
| Frontend refactor mengubah barcode | Re-export helper, existing encoder/print tests diregresi |
| Task terlalu terfragmentasi | Satu paket utuh, tiga increment internal tanpa approval kecil |

## Owner approval

[APPROVAL.md](APPROVAL.md) PENDING. Keputusan user "Label CT420D + scanner dulu"
dan "Ya, gunakan QZ Tray" mengonfirmasi produk/dependency pilihan, bukan approval
exact source, schema atau deployment PLAN yang baru disusun ini.

Persetujuan yang diminta: PRD/layout, exact allowlist, satu npm SDK, implementasi
utuh, QA virtual/disposable dan, bila disetujui, aktivasi aplikasi melalui backup
verified + migration Hardware + permission seeder tanpa grants + cache rebuild.
Print fisik tetap OFF; tidak ada instalasi OS/QZ/driver/cert atau pilot hardware.
Izin commit/push dicatat terpisah secara eksplisit, bukan diasumsikan dari Git
task011. Sesudah approval, --require-approved dan fingerprint wajib diperiksa
sebelum coding; bila scope berubah, minta persetujuan revisi.
