# Tasks: Fondasi Barang Dan Multi Satuan

## Sebelum Mulai

- [x] AGENTS.md, README, arsitektur, struktur folder, ADR-009, dan work item dibaca.
- [x] Target module diinformasikan: Items; Units disentuh untuk lookup public nyata.
- [x] Scope, precision enam desimal, base unit terkecil, dan QA didokumentasikan.
- [x] Dampak schema/route/permission/audit/demo/soft delete dan transaksi diperiksa.
- [x] Dry-run generator sesuai struktur canonical.

## Increment 4A: Core

- [x] Implementasikan ConversionRatio bebas Laravel.
  - Acceptance: ratio minimal 1, base ratio 1, qty pecahan nonnegatif,
    hasil exact scale enam; invalid, overflow, rounding ditolak.
  - Verification: unit test Domain.
- [x] Tambahkan public UnitLookup, UnitData, adapter, dan binding Units.
  - Acceptance: active unit saja; DTO; lock untuk mutation dalam transaction.
  - Verification: feature test lookup; consumer/import review.
- [x] Tambahkan schema, model, action CreateItem, DTOs, port/repository, binding.
  - Acceptance: ULID; base row otomatis; flags jual/beli; barcode unik; ratio
    dus per barang; referensi unit valid; soft delete; tanpa model Units privat.
  - Verification: feature test create, invalid input, schema, soft delete.
- [x] Pasang audit dan rollback atomic.
  - Acceptance: create/update/delete/restore tercatat; gagal persistence tidak
    meninggalkan barang/relasi/audit.
  - Verification: test audit dan duplicate barcode rollback.
- [x] Verifikasi 4A dan commit otomatis.
  - Acceptance: focused/regression, Pint, route, migration/status lulus.
  - Verification: hasil command dan Git save point.

## Increment 4B: Demo Dan Finalisasi

- [x] Tambahkan demo/permission seeder module.
  - Acceptance: tiga barang relasional; missing unit rollback; local/testing;
    menjaga existing data/soft delete; permission tidak memberi akses user.
  - Verification: feature test seeder; smoke MySQL seed ulang.
- [x] Verifikasi seluruh Increment 4.
  - Acceptance: full backend regression, format, migration/status dan diff check lulus.
  - Verification: command project sesuai scope.
- [x] Update checklist module, Units, indeks, dan work item global.
  - Acceptance: status berdasarkan realita; UI/transaksi belum diklaim selesai.
  - Verification: review docs dan commit otomatis.

## Hasil Verifikasi

Verifikasi 4A pada 9 Oktober 2026:

| Command | Hasil | Bukti |
| --- | --- | --- |
| Dry-run dan generator Items dengan --with-tests | PASS | Struktur canonical module sesuai baseline |
| `php artisan test --filter="Items|UnitLookup"` | PASS | 31 test/75 assertion |
| `php artisan test` | PASS | 89 test/259 assertion |
| `php vendor/bin/pint --test` pada source/test Items dan Units terkait | PASS | Format PHP sesuai |
| `php artisan migrate --no-interaction` dan `migrate:status` | PASS | Semua 9 migration Ran pada MySQL lokal; tanpa reset/rollback |
| `php artisan route:list --except-vendor` | PASS | 22 route existing; tanpa endpoint barang |

Core 4A disimpan pada commit `cbb8acd` setelah verifikasi lulus.

Verifikasi akhir 4B pada 9 Oktober 2026:

| Command | Hasil | Bukti |
| --- | --- | --- |
| `php artisan test --filter="Items|UnitLookup"` | PASS | 40 test/111 assertion |
| `php artisan test` | PASS | 98 test/295 assertion termasuk auth, Identity, generator, Inventory, dashboard, POS |
| `php vendor/bin/pint --test` pada source/test Items dan Units terkait | PASS | Format PHP sesuai |
| Seeder Units prasyarat, ItemsAccessSeeder, ItemsDemoSeeder via `db:seed --class=... --no-interaction` | PASS | MySQL lokal: 3 barang, 7 relasi, 10 audit barang/relasi, 2 permission inventory |
| Smoke `tinker --execute` pada MySQL lokal | PASS | Seed ulang menjaga data/audit; semua subject ULID terhubung; 0.125 KG = 125 G |
| Smoke rollback barcode duplikat pada MySQL lokal | PASS | Constraint menolak barcode; jumlah barang/relasi/audit tetap sama |
| `git diff --check` | PASS | Tidak ada whitespace error; staging hanya file terkait |

Browser/lint/build/typecheck SKIPPED: tidak ada UI/HTTP baru. Migration/status
dan route tetap sesuai hasil 4A; 4B tidak menambah schema atau endpoint.
Review import memastikan runtime Items hanya memakai contract Units; Domain
bebas Laravel dan Application tidak mengimpor Infrastructure. Tidak memasang
dependency baru, mengubah theme, menyentuh Old-docs, atau memutasi stok/FIFO.

Risiko terbuka: perubahan/deaktivasi master yang sudah direferensikan perlu
guard pada CRUD; snapshot dan no-negative-stock/FIFO diuji pada transaksi nyata.
Permission HTTP dan QA desktop/tablet tetap pending pada increment berikutnya.
Work item global belum selesai; push belum dilakukan.
