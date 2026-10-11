# Tasks: Fondasi Gudang

## Sebelum Mulai

- [x] AGENTS.md, README, arsitektur, struktur folder, workflow, dan quality dibaca.
- [x] Target module diinformasikan: Inventory/Warehouses.
- [x] Dry-run generator sesuai struktur canonical.
- [x] Scope Increment 2, schema, permission, demo, audit, dan QA didokumentasikan.

## Increment 2: Fondasi Backend Gudang

- [x] Generate module dengan provider, Routes, migration, seeder, dan test.
  - Acceptance: loader existing menemukan module tanpa registrasi manual.
  - Verification: focused test dan route:list.
- [x] Tambahkan schema dan model gudang.
  - Acceptance: ULID, unique code, default aktif, soft delete; kode terhapus tetap unik.
  - Verification: feature test database dan migrate:status.
- [x] Tambahkan permission dan demo seeder module.
  - Acceptance: idempotent; tidak mengubah role/user maupun existing gudang;
    demo ditolak di luar local/testing.
  - Verification: feature test seeder dan smoke seeder lokal.
- [x] Pasang audit perubahan master sejak awal.
  - Acceptance: create/update/delete/restore dicatat; update tanpa perubahan tidak dicatat.
  - Verification: feature test activity dan actor.
- [x] Siapkan kompatibilitas subject audit dengan ULID.
  - Acceptance: migration global memperlebar subject_id tanpa menghapus log lama;
    relasi subject numerik dan ULID tetap bekerja.
  - Verification: focused test; inspeksi schema dan seed pada MySQL lokal.

## Sesudah Coding

- [x] Focused test dan regression test terkait lulus.
- [x] Pint, route inspection, migration/status, dan git diff --check lulus.
- [x] Dokumen dan checklist work item diperbarui.
- [x] Commit otomatis dibuat setelah verifikasi.

## Hasil Verifikasi

Verifikasi 9 Oktober 2026:

| Command | Hasil | Bukti |
| --- | --- | --- |
| `php artisan module:make Inventory Warehouses --dry-run --with-tests` | PASS | Path app/Modules dan database/route module sesuai generator |
| `php artisan test --filter=Warehouses` | PASS | 11 test, 38 assertion |
| `php artisan test` | PASS | 49 test, 150 assertion termasuk Identity, auth, generator, dashboard, POS |
| `php vendor/bin/pint --test` dengan path perubahan | PASS | Semua PHP yang diubah sesuai format |
| `php artisan route:list --except-vendor` | PASS | 22 route existing, tanpa endpoint gudang palsu |
| `php artisan migrate --no-interaction` | PASS | Schema gudang dan kompatibilitas subject audit diterapkan pada MySQL lokal |
| `php artisan migrate:status` | PASS | Semua 7 migration Ran |
| Seeder access dan demo via `db:seed --class=... --no-interaction` | PASS | 2 gudang, 2 permission inventory, 2 audit |
| Smoke MySQL via `tinker --execute` | PASS | Seed ulang mempertahankan data dan jumlah audit; kedua subject ULID terhubung |
| `git diff --check` | PASS | Tidak ada whitespace error |

QA browser, lint, build, dan typecheck frontend SKIPPED: belum ada perubahan
UI atau endpoint HTTP baru. Migration upgrade mempertahankan subject numerik
existing dan rollback menjaga referensi ULID; diverifikasi pada database test
disposable. Rollback database pengguna tidak dijalankan.

Dua assertion Identity disesuaikan untuk subject_id string dan dilengkapi
verifikasi relasi user/role. Perubahan ini diperlukan oleh schema audit baru.

## Increment 5: Integrasi HTTP

- [x] CreateWarehouse/ListWarehouses memakai port, adapter, dan DTOs module.
- [x] Controller HasMiddleware memakai can:inventory.view/manage per action.
- [x] Validasi/normalisasi, pagination, soft delete, audit actor, dan konflik
  unique/rollback terverifikasi melalui InventoryMasterHttpTest.
- [x] Demo/access seeder diuji bersama Units dan Items; akses user tetap.

Gate akhir 10 Oktober 2026: 131 test/624 assertion PASS; route/status, Pint,
dan smoke MySQL PASS. UI CRUD lengkap dan guard stok/transaksi pada mutation
master tetap pending pada akhir Increment 5. Detail pada work item Inventory Foundation.

## Increment 6: CRUD Admin

- [x] Endpoint index/update/destroy/restore melalui Application, permission backend,
  validation, soft delete dan audit atomic.
- [x] List/filter/pagination, dialog CRUD, Sonner, shortcut dan permission UX.
- [x] QA desktop/tablet expanded/collapsed, lifecycle, search/empty dan palette.

HTTP/permission/rollback: InventoryCrudTest dan InventoryPagesTest PASS.
Gate lint/typecheck/build dan hasil fallback browser tercatat pada
[tasks Inventory Foundation](../../../work-items/inventory-foundation/tasks.md).
Integrasi stok/POS dan guard referensi transaksi tetap di luar scope.
