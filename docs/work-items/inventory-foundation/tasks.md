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
Increment 5 tetap pending untuk validasi integrasi dan permission HTTP nyata.

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
selesai pada Increment 4; belum ada endpoint HTTP/UI satuan.

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
[tasks Items](../../modules/Inventory/Items/tasks.md). Endpoint HTTP/UI dan
mutation stok/FIFO belum tersedia; Increment 5-6 tetap pending.

## Increment 5: Permission, Audit, Dan Seeder Demo

- [ ] Tambahkan permission backend.
  - Acceptance: permission minimal `inventory.view` dan `inventory.manage`
    tersedia serta dipakai pada controller/action terkait.
  - Verification: permission focused test.
- [ ] Tambahkan activity log untuk mutation master.
  - Acceptance: create/update/delete master penting tercatat melalui Spatie
    Activitylog.
  - Verification: activity log smoke test.
- [ ] Tambahkan demo seeder relasional.
  - Acceptance: seeder membuat gudang, satuan, dan item demo yang dapat dipakai
    alur POS mock.
  - Verification: seed command module; focused test bila tersedia.

## Increment 6: UI Admin Inventory

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
