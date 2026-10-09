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
- [ ] Verifikasi 4A dan commit otomatis.
  - Acceptance: focused/regression, Pint, route, migration/status lulus.
  - Verification: hasil command dan Git save point.

## Increment 4B: Demo Dan Finalisasi

- [ ] Tambahkan demo/permission seeder module.
  - Acceptance: tiga barang relasional; missing unit rollback; local/testing;
    menjaga existing data/soft delete; permission tidak memberi akses user.
  - Verification: feature test seeder; smoke MySQL seed ulang.
- [ ] Verifikasi seluruh Increment 4.
  - Acceptance: full backend regression, format, migration/status dan diff check lulus.
  - Verification: command project sesuai scope.
- [ ] Update checklist module, Units, indeks, dan work item global.
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

Browser/lint/build/typecheck SKIPPED: tidak ada UI/HTTP baru. Demo/access Items
belum diimplementasikan (scaffold), menunggu 4B. Tidak ada mutation stok/FIFO.
