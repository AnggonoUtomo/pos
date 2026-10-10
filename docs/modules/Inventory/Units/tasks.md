# Tasks: Fondasi Satuan

## Sebelum Mulai

- [x] AGENTS.md dan dokumen aktif dibaca sesuai scope.
- [x] Target module diinformasikan: Inventory/Units, Increment 3.
- [x] Keputusan user: Units/Items terpisah, konversi per barang pada Items.
- [x] ADR-009 dan indeks module diselaraskan serta di-commit.
- [x] Schema, permission, demo, audit, risiko integrasi, dan QA direncanakan.
- [x] Dry-run generator sesuai struktur canonical.

## Increment 3: Fondasi Backend Satuan

- [x] Generate module dengan provider, route scaffold, migration, dan seeder.
  - Acceptance: loader existing menemukan module tanpa registrasi manual.
  - Verification: focused test; route:list.
- [x] Tambahkan schema dan model satuan.
  - Acceptance: ULID, unique code termasuk data terhapus, simbol, default aktif,
    soft delete; tidak ada ratio atau base unit global.
  - Verification: feature test database; migrate:status.
- [x] Tambahkan demo dan access seeder module.
  - Acceptance: tujuh satuan demo; idempotent; menjaga perubahan user dan
    soft-deleted row; demo ditolak di luar local/testing; tidak memberi akses user.
  - Verification: feature test seeder; smoke seed ulang MySQL.
- [x] Pasang activity log sejak awal.
  - Acceptance: create/update/delete/restore tercatat; actor/subject terhubung;
    update tanpa perubahan dan seed ulang tidak menambah log.
  - Verification: feature test audit; smoke MySQL.

## Sesudah Coding

- [x] Focused test dan regression backend lulus.
- [x] Pint, route/migration check, dan git diff --check lulus.
- [x] Dokumen dan checklist global diperbarui berdasarkan realita.
- [x] Commit otomatis dibuat setelah verifikasi.

## Hasil Verifikasi

Verifikasi 9 Oktober 2026:

| Command | Hasil | Bukti |
| --- | --- | --- |
| `php artisan module:make Inventory Units --dry-run --with-tests` | PASS | Struktur canonical sesuai generator |
| `php artisan module:make Inventory Units --with-tests` | PASS | Provider, Routes, Database, dan test module tersedia |
| `php artisan test --filter=Units` | PASS | 9 test, 34 assertion |
| `php artisan test` | PASS | 58 test, 184 assertion termasuk Warehouses, Identity, auth, generator, dashboard, POS |
| `php vendor/bin/pint --test app/Modules/Inventory/Units tests/Feature/Modules/Inventory/Units` | PASS | Format PHP sesuai |
| `php artisan migrate --no-interaction` | PASS | Tabel units dibuat pada MySQL lokal |
| `php artisan migrate:status` | PASS | Semua 8 migration Ran |
| Seeder access/demo via `db:seed --class=... --no-interaction` | PASS | Tujuh satuan demo, permission inventory tersedia |
| Smoke MySQL via `tinker --execute` | PASS | 7 satuan dan 7 audit satuan; semua subject ULID terhubung; seed ulang tidak mengubah data/audit; 2 permission inventory tanpa duplikat |
| `php artisan route:list --except-vendor` | PASS | 22 route existing, belum ada endpoint HTTP satuan |
| `git diff --check` | PASS | Tidak ada whitespace error |

Frontend lint/build/typecheck dan Chrome DevTools QA SKIPPED: increment ini
belum mengubah UI atau endpoint HTTP. Tidak menjalankan rollback/reset database
pengguna. UI tetap pending. Integrasi lookup Items diverifikasi pada Increment 4.

## Consumer Items: Increment 4

- [x] Contract UnitLookup dan DTO UnitData memiliki consumer CreateItem nyata.
- [x] Adapter/binding menyaring unit hilang, nonaktif, dan terhapus.
- [x] Lookup mutation memakai lock dalam transaction bersama.
- [x] Dua feature test lookup lulus (8 assertion); regresi 4A lulus.

Perubahan ini tidak menambahkan endpoint atau aturan mutation master Units.

## Increment 5: Integrasi HTTP

- [x] CreateUnit/ListUnits memakai port, adapter, dan DTOs module.
- [x] Controller HasMiddleware memakai can:inventory.view/manage per action.
- [x] Validasi/normalisasi, pagination, soft delete, audit actor, dan konflik
  unique/rollback terverifikasi melalui InventoryMasterHttpTest.
- [x] Demo/access seeder diuji bersama Warehouses dan Items; akses user tetap.

Gate akhir 10 Oktober 2026: 131 test/624 assertion PASS; route/status, Pint,
dan smoke MySQL PASS. UI CRUD lengkap dan guard mutation master yang sudah
direferensikan tetap scope Increment 6. Detail pada work item Inventory Foundation.
