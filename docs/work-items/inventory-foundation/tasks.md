# Tasks: Inventory Foundation

## Sebelum Mulai

- [x] `AGENTS.md` dibaca.
- [x] `docs/README.md`, `docs/PROJECT.md`, `docs/ARCHITECTURE.md`,
  `docs/FOLDER-STRUCTURE.md`, `docs/MODULES.md`, `docs/WORKFLOW.md`, dan
  `docs/QUALITY.md` dirujuk sesuai scope.
- [x] Scope awal dibatasi ke perencanaan Inventory foundation.
- [x] Module target disebut ke user: lintas module Inventory (`Warehouses`,
  `Units`, `Items`).
- [x] Dampak database, route, permission, UI, seeder, audit, soft delete, stok,
  FIFO, payment, tax, diskon, dan pricing dicek sesuai scope.
- [x] QA automated ditentukan per increment.
- [x] Chrome DevTools QA direncanakan hanya untuk increment UI.
- [x] `Old-docs/` tidak disentuh.

## Increment 1: Work Item Dan Rencana Implementasi

- [x] Buat `README.md` work item.
  - Acceptance: mencatat kondisi awal, scope, non-scope, acceptance criteria,
    dependency, dampak, dan handoff.
  - Verification: review manual dokumen.
- [x] Buat `plan.md`.
  - Acceptance: berisi urutan increment, dependency graph, QA automated,
    risiko, dan open questions.
  - Verification: review manual dokumen.
- [x] Buat `tasks.md`.
  - Acceptance: checklist sebelum mulai, increment detail, dan hasil verifikasi
    tersedia sebelum coding.
  - Verification: `git diff --check`.

## Increment 2: Module Warehouses

- [x] Jalankan dry-run scaffold `Inventory/Warehouses`.
  - Acceptance: output membuat module di `app/Modules/Inventory/Warehouses`
    dengan `Routes/`, `Database/Migrations`, dan `Database/Seeders`.
  - Verification: `php artisan module:make Inventory Warehouses --dry-run`.
- [x] Buat module source `Inventory/Warehouses`.
  - Acceptance: provider, route, migration folder, seeder demo, permission key,
    dan test minimal tersedia.
  - Verification: focused test; `php artisan route:list --except-vendor`.
- [x] Tentukan data gudang awal.
  - Acceptance: gudang memiliki ULID, kode, nama, status aktif, dan soft delete
    sebagai master mutable.
  - Verification: migration/status check.

Increment 2 Passed pada 9 Oktober 2026. Audit model, permission awal, demo
gudang, dan migration kompatibilitas subject audit ULID juga selesai. Checklist
rinci ada di [tasks Warehouses](../../modules/Inventory/Warehouses/tasks.md).
Validasi integrasi dan permission HTTP data/store selesai pada Increment 5.

## Increment 3: Module Units

- [x] Jalankan dry-run scaffold `Inventory/Units`.
  - Acceptance: output module sesuai struktur canonical.
  - Verification: `php artisan module:make Inventory Units --dry-run`.
- [x] Buat module source `Inventory/Units`.
  - Acceptance: master satuan memiliki ULID, kode unik, nama, simbol, status aktif,
    soft delete, audit, dan demo seeder idempotent.
  - Verification: focused test; migration/status check.
- [x] Tetapkan ownership konversi berdasarkan keputusan user.
  - Acceptance: Units memiliki master satuan; Items memiliki base unit dan ratio
    positif per barang; ratio base unit 1. Schema Units tidak menyimpan ratio.
  - Verification: ADR-009; review indeks module dan rencana Increment 4.

Increment 3 Passed pada 9 Oktober 2026. Master satuan, audit, permission,
demo seeder, dan verifikasi MySQL selesai. Detail tersedia di
[tasks Units](../../modules/Inventory/Units/tasks.md). Konversi per barang
selesai pada Increment 4; endpoint data/store tersedia pada Increment 5, UI
CRUD lengkap menunggu Increment 6.

## Increment 4: Module Items

- [x] Jalankan dry-run scaffold `Inventory/Items`.
  - Acceptance: output module sesuai struktur canonical.
  - Verification: `php artisan module:make Inventory Items --dry-run`.
- [x] Buat module source `Inventory/Items`.
  - Acceptance: item memiliki ULID, kode/SKU, nama, base unit, status aktif,
    dan soft delete master.
  - Verification: focused test; migration/status check.
- [x] Siapkan relasi satuan item.
  - Acceptance: item dapat memiliki satuan jual/beli awal yang mengarah ke unit
    dan ratio positif per barang; base unit ratio 1. Dua barang boleh memiliki
    ratio berbeda untuk unit yang sama.
  - Verification: feature test relasi.

Increment 4 Passed pada 9 Oktober 2026. Core 4A dan demo 4B mencakup konversi
exact, lookup public Units, barcode unik, soft delete, audit atomic, permission
seed, dan demo relasional. Detail tersedia di
[tasks Items](../../modules/Inventory/Items/tasks.md). Endpoint data/store
tersedia pada Increment 5; UI CRUD lengkap dan mutation stok/FIFO belum tersedia.

## Increment 5: Permission, Audit, Dan Seeder Demo

- [x] Tambahkan permission backend.
  - Acceptance: permission minimal `inventory.view` dan `inventory.manage`
    tersedia serta dipakai pada controller/action terkait.
  - Verification: permission focused test.
- [x] Tambahkan activity log untuk mutation master.
  - Acceptance: create/update/delete master penting tercatat melalui Spatie
    Activitylog.
  - Verification: activity log smoke test.
- [x] Tambahkan demo seeder relasional.
  - Acceptance: seeder membuat gudang, satuan, dan item demo yang dapat dipakai
    alur POS mock.
  - Verification: seed command module; focused test bila tersedia.

## Rincian Increment 5

### Sebelum Mulai

- [x] AGENTS, README, arsitektur, struktur canonical, API, Quality, dan dokumen
  ketiga module diperiksa; worktree user dipertahankan.
- [x] Target diinformasikan: Inventory/Warehouses, Units, Items.
- [x] Boundary HTTP internal, format response, permission, audit, dan batas CRUD
  didokumentasikan sebelum coding.

### 5A: Endpoint Gudang Dan Satuan

- [x] Tulis test HTTP permission dan validation terlebih dahulu.
  - Acceptance: guest/tanpa permission ditolak; view tidak dapat store; manage
    dapat store; super-system memakai Gate sesuai keputusan user ADR-010.
  - Verification: focused feature test RED sebelum implementasi.
- [x] Implementasikan read/create melalui Application dan adapter masing-masing.
  - Acceptance: pagination terbatas; soft-deleted tersembunyi; ULID; allowlist;
    request normalisasi; unique termasuk deleted; audit actor dalam transaction.
  - Verification: feature test response, validasi, actor/subject dan rollback.
- [x] Verifikasi dan commit 5A.
  - Acceptance: focused test/regresi, Pint, route dan diff check lulus.
  - Verification: Git save point setelah QA.

### 5B: Endpoint Barang

- [x] Hubungkan request/controller ke CreateItem dan list query.
  - Acceptance: can per action; ratio string; base unit/relasi tervalidasi;
    barcode unik; batas 100 satuan; exception tidak membocorkan SQL.
  - Verification: HTTP test permission, input invalid dan atomicity.
- [x] Verifikasi dan commit 5B.
  - Acceptance: focused test/regresi, Pint, route dan diff check lulus.
  - Verification: Git save point setelah QA.

### 5C: Integrasi Dan Finalisasi

- [x] Verifikasi demo/access ketiga module secara terintegrasi.
  - Acceptance: 2 gudang, 7 satuan, 3 barang/7 relasi; seed ulang menjaga edit
    dan soft delete; audit ULID terhubung; akses user tidak berubah.
  - Verification: integration test; smoke MySQL tanpa reset.
- [x] Jalankan gate akhir dan sinkronkan dokumentasi.
  - Acceptance: status berdasarkan realita; scope UI/transaksi tetap pending.
  - Verification: full test, Pint, route/status, diff, HTTP guest dan commit.

Increment 5 Passed pada 10 Oktober 2026. Read/create internal, permission
matrix, Gate super-system, audit, dan demo terintegrasi selesai. Belum ada UI
CRUD lengkap, operasional update/delete/restore, atau mutation stok/FIFO.

## Increment 6: UI Admin Inventory

### Sebelum Mulai

- [x] Target Inventory/Warehouses, Units, Items diinformasikan ke user.
- [x] Arsitektur, struktur, Quality, source dan pola UI existing diperiksa.
- [x] User menyetujui base unit immutable dan guard master satuan terpakai.
- [x] Scope desktop/tablet; theme, Old-docs, stok/FIFO dan grant akses tidak diubah.

### 6A: CRUD Backend Dan Integritas Master

- [x] Tulis test gagal sebelum implementasi endpoint update/delete/restore.
  - Acceptance: permission per aksi, ULID, validation, soft delete dan audit.
  - Verification: focused test RED kemudian GREEN.
- [x] Implementasikan mutation melalui Application dan port module.
  - Acceptance: transaction atomic; base unit immutable; referensi satuan aman;
    tidak ada model privat lintas module; uniqueness termasuk data terhapus.
  - Verification: focused test rollback, guard referensi dan lifecycle.
- [x] Verifikasi dan commit 6A.
  - Acceptance: test terkait, Pint, route inspection dan diff check lulus.
  - Verification: save point Git selektif.

### 6B: Halaman Gudang Dan Satuan

- [x] Tambahkan halaman Inertia dan query filter/pagination server.
  - Acceptance: cari kode/nama, status aktif/nonaktif dan tab data terhapus;
    state filter di URL; route data/store lama tetap kompatibel.
  - Verification: Inertia/HTTP focused test.
- [x] Implementasikan tabel, dialog tambah/edit, konfirmasi hapus/pulihkan.
  - Acceptance: Sonner, error per field, processing, empty state, permission UX,
    shortcut dropdown dan keyboard; navigasi sidebar/palette aktif.
  - Verification: lint/typecheck/build dan QA browser.
- [x] Verifikasi dan commit 6B.
  - Acceptance: source buildable dan operasi master berfungsi.
  - Verification: save point Git selektif.

### 6C: Halaman Barang Multi Satuan

- [x] Tambahkan pilihan satuan melalui public UnitLookup dan halaman barang.
  - Acceptance: list mencari SKU/nama/barcode; tampil base unit dan jumlah satuan.
  - Verification: HTTP/Inertia test lookup dan filter.
- [x] Implementasikan editor barang serta baris konversi/barcode jual/beli.
  - Acceptance: rasio string exact, base unit 1 terkunci; field errors dan Sonner;
    tambah/hapus/pulihkan satuan tambahan; permission dan shortcut konsisten.
  - Verification: test invalid ratio/barcode, build dan QA browser CRUD.
- [x] Verifikasi dan commit 6C.
  - Acceptance: CRUD barang tidak mengubah stok/FIFO/harga.
  - Verification: save point Git selektif.

### 6D: QA Dan Penutupan Work Item

- [ ] QA desktop/tablet, sidebar expanded/collapsed, shortcut dan palette.
  - Acceptance: tidak ada overlap/overflow halaman atau console error baru;
    permission readonly, validasi dan feedback operasi terverifikasi.
  - Verification: Chrome DevTools MCP atau BLOCKED dengan fallback browser.
- [ ] Jalankan regresi penuh, lint/typecheck/build, route/status dan diff check.
  - Acceptance: semua gate relevan lulus; hasil command dicatat aktual.
  - Verification: tabel hasil verifikasi dan checklist sesudah coding.
- [ ] Perbarui API, module docs/index, status work item; commit dan push.
  - Acceptance: dokumentasi sesuai source; artefak user tidak ikut staging.
  - Verification: staged diff dan status remote sesudah push.

### Ringkasan Acceptance

- [ ] Buat halaman list master gudang, satuan, dan item.
  - Acceptance: halaman memakai layout admin, shortcut operasi dropdown, dan
    permission UX.
  - Verification: `npm run lint`; `npm run build`.
- [ ] Tambahkan form CRUD master.
  - Acceptance: memakai Sonner toast success/error, loading/empty/error state,
    soft delete UI bila relevan, dan validasi user-friendly.
  - Verification: Chrome DevTools MCP desktop/tablet.
- [ ] Pastikan route palsu tidak dibuat.
  - Acceptance: navigasi hanya mengarah ke route nyata; menu yang belum siap
    tetap controlled.
  - Verification: `php artisan route:list --except-vendor`; browser QA.

## Sesudah Coding

- [ ] Scope selesai.
- [ ] Focused test lulus atau dinyatakan tidak relevan.
- [ ] Lint/build/typecheck lulus bila relevan.
- [ ] Route/migration check lulus bila relevan.
- [ ] Chrome DevTools QA PASS, atau BLOCKED/SKIPPED dengan alasan.
- [ ] Work item dan dokumen module diperbarui.
- [ ] Risiko terbuka dicatat.

## Hasil Verifikasi

6C pada 10 Oktober 2026: RED 2 test index barang dan 1 test barcode duplikat
edit; GREEN regresi CRUD/pages/ItemsHttp/UnitLookup 36 test/341 assertion,
kemudian UnitLookupTest 3 test/12 assertion termasuk limit pilihan 1000 PASS.
InventoryPagesTest 6 test/126 assertion PASS; scoped ESLint, Pint dan build PASS.
QA barang PASS: CRUD/restore, barcode '0'/pencarian, rasio exact 24.000001,
flag jual/beli, base terkunci dan rasio invalid; screenshot form/list desktop
dan tablet. QA gabungan terakhir PASS dengan console kosong, 18 screenshot,
dark default/clean, sidebar expanded/collapsed, palette dan viewer readonly.
Artifact: storage/framework/testing/inventory-browser-6bd12c92d51efb51.
Global typecheck tetap BLOCKED oleh error existing yang sama; Inventory tidak
menambah error TypeScript. CSS/theme, stok/FIFO dan MySQL user tidak diubah.

6B pada 10 Oktober 2026: InventoryPagesTest RED 4 test (GET index belum ada),
GREEN 4 test/82 assertion. Regresi CRUD/master/pages 28 test/364 assertion PASS.
Scoped ESLint dan build PASS. Typecheck global BLOCKED oleh 10 error baseline
auth.user nullable dan CSS welcome; izin perbaikan terpisah ditanyakan ke user.
Chrome DevTools MCP BLOCKED (tool tidak tersedia), browser plugin BLOCKED
(tidak ada browser terhubung); fallback Playwright lokal PASS. Delapan screenshot
1440x1000, 1024x768, 768x1024 untuk gudang/satuan, expanded/collapsed; CRUD,
restore, search/empty, shortcut, palette, readonly dan guard referensi PASS.
Console error/warning kosong. Artifact lokal di
storage/framework/testing/inventory-browser-8babd1120fdc0f33 (git ignored).
QA memperbaiki state dialog error melalui preserveState pada mutation Inertia.
Browser memakai SQLite disposable/credential runtime, tidak memutasi MySQL user.

6A pada 10 Oktober 2026: RED 9 test karena endpoint belum tersedia; GREEN
InventoryCrudTest 11 test/90 assertion. Regresi Inventory awal 100 test/579
assertion PASS sebelum tambahan 2 test terakhir; focused test terakhir mencakup
rollback persistence/audit dan ID invalid. Pint PASS, 15 route Inventory, diff
check PASS. Belum ada perubahan schema/UI, sehingga QA browser 6A SKIPPED.
Chrome DevTools MCP tidak tersedia pada daftar tool; fallback browser plugin
juga tidak menemukan browser terhubung. QA UI akan mencoba fallback lokal.

5A diverifikasi pada 10 Oktober 2026: InventoryMasterHttpTest 13 test/188
assertion PASS; full suite 113 test/489 assertion PASS; Pint source/test terkait
PASS; route:list -v menunjukkan 4 route Inventory dengan can per action.
Tidak ada migration atau perubahan UI; QA frontend/browser SKIPPED.

Save point 5A: `c44aeb8`. Bypass super-system disetujui user dan disimpan pada
`4f45005` dengan ADR-010. 5B diverifikasi pada 10 Oktober 2026: ItemsHttpTest
16 test/108 assertion PASS; full suite 129 test/597 assertion PASS. Validasi
base ratio, unit tidak tersedia, precision, duplicate SKU/barcode, ULID case,
barcode '0', rollback/audit, serta super-system tanpa bypass invariant lulus.

Save point 5B: `3cbdee6`. Verifikasi akhir Increment 5 pada 10 Oktober 2026:

| Command | Hasil | Bukti |
| --- | --- | --- |
| Filter InventoryMasterHttpTest, ItemsHttpTest, InventoryDemoIntegrationTest, SuperSystemGateTest | PASS | 33 test/329 assertion |
| `php artisan test` | PASS | 131 test/624 assertion |
| `php vendor/bin/pint --test` source/test Inventory dan Gate Identity terkait | PASS | Format PHP sesuai |
| `php artisan route:list --except-vendor` dan `--path=inventory -v` | PASS | 28 route total; 6 route Inventory dengan auth/verified/can per action |
| `php artisan migrate:status` | PASS | Semua 9 migration Ran; tidak menambah schema atau reset database |
| Seed access/demo ketiga module dan repeat smoke MySQL via tinker | PASS | 2 gudang, 7 satuan, 3 barang, 7 relasi, 19 audit; data/audit dan akses user tidak berubah saat seed ulang; subject ULID terhubung |
| HTTP guest GET data pada `http://pos.test` | PASS | Warehouses/Units/Items masing-masing 401 |
| HTTP POST Items tanpa CSRF pada `http://pos.test` | PASS | 419; middleware web tetap melindungi mutation |
| Review layer/import, input allowlist, error mapping, pagination | PASS | Domain tetap murni; Application tanpa Infrastructure; runtime Items tidak mengambil model privat Units |
| `git diff --check` | PASS | Tidak ada whitespace error; staging hanya scope pekerjaan |

QA Chrome DevTools dan lint/build/typecheck frontend SKIPPED: hanya endpoint
data/store, tidak ada UI baru; fallback HTTP feature test dan smoke host lokal
lulus. Tidak menyentuh theme, SampleUI, Old-docs, dependency, credential seed,
akun/role assignment, atau stok/FIFO/payment. Assignment role super-system
tetap milik Identity; bypass baru tidak memberikan role tersebut kepada user.
MustVerifyEmail mengikuti starter kit existing; tidak mengubah flow auth.
Risiko terbuka: CRUD lengkap/guard master referensial dan QA desktop/tablet
menunggu Increment 6; snapshot/no-negative-stock/FIFO menunggu transaksi nyata.
Checklist sesudah coding mengacu keseluruhan work item, belum selesai
karena Increment 6 pending. Push belum dilakukan.

| Command | Hasil | Catatan |
| --- | --- | --- |
| `git push origin main` | PASS | Commit UI/theme sebelumnya sudah dipush sebelum work item Inventory dibuat |
| `git diff --check` | PASS | Tidak ada whitespace error pada work item Inventory foundation |
| `php artisan test --filter=Warehouses` | PASS | Increment 2: 11 test/38 assertion |
| `php artisan test` | PASS | Increment 2: 49 test/150 assertion, termasuk regresi Identity |
| `php vendor/bin/pint --test` dengan path perubahan | PASS | PHP perubahan sesuai format |
| `php artisan route:list --except-vendor` | PASS | 22 route existing; belum ada endpoint HTTP gudang |
| `php artisan migrate --no-interaction` dan `migrate:status` | PASS | 7 migration Ran pada MySQL lokal |
| Seeder access/demo gudang dan smoke seed ulang MySQL | PASS | 2 gudang, 2 permission inventory, 2 audit; data dan audit tidak berubah saat seed ulang |
| QA browser/frontend | SKIPPED | Increment 2 backend saja; tidak ada UI baru |
| `php artisan test --filter=Units` | PASS | Increment 3: 9 test/34 assertion |
| `php artisan test` | PASS | Setelah Increment 3: 58 test/184 assertion |
| `php vendor/bin/pint --test` dengan path Units | PASS | Format PHP sesuai |
| `php artisan migrate --no-interaction` dan `migrate:status` | PASS | Setelah Increment 3: semua 8 migration Ran di MySQL lokal |
| Seeder access/demo Units dan smoke seed ulang MySQL | PASS | 7 satuan, 7 audit satuan, subject ULID terhubung; seed ulang tidak mengubah data/audit |
| `php artisan route:list --except-vendor` | PASS | Setelah Increment 3: tetap 22 route existing |
| QA browser/frontend | SKIPPED | Increment 3 backend saja; tidak ada UI baru |
| `php artisan test --filter="Items|UnitLookup"` | PASS | Increment 4: 40 test/111 assertion |
| `php artisan test` | PASS | Setelah Increment 4: 98 test/295 assertion |
| Pint source/test Items dan lookup Units terkait | PASS | Format PHP sesuai |
| `php artisan migrate --no-interaction` dan `migrate:status` | PASS | Semua 9 migration Ran pada MySQL lokal |
| Seeder access/demo Items dan smoke seed ulang MySQL | PASS | 3 barang, 7 relasi, 10 audit; subject ULID terhubung; seed ulang tidak mengubah data/audit |
| Smoke rollback barcode duplikat MySQL | PASS | Barang, relasi, audit rollback bersama; jumlah record tidak berubah |
| `php artisan route:list --except-vendor` | PASS | Tetap 22 route existing; belum ada endpoint barang |
| QA browser/frontend | SKIPPED | Increment 4 backend saja; tidak ada UI baru |

Scope keseluruhan belum selesai. Konflik ownership telah diselesaikan melalui
keputusan user: Units/Items terpisah sesuai ADR-009.

Jangan menambahkan pekerjaan baru ke checklist ini tanpa persetujuan user.
