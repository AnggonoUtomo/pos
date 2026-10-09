# Plan: Inventory/Items

## Increment 4A: Core Barang Dan Konversi

1. Dokumentasikan invariant, precision, boundary, dan QA sebelum coding.
2. Dry-run dan generate Items.
3. Tulis failing unit test ConversionRatio; implementasikan dengan BigDecimal.
4. Tulis test public UnitLookup; implementasikan contract, DTO, dan adapter Units.
5. Tulis test CreateItem; implementasikan schema/model, DTOs, action, repository,
   binding provider, dan audit.
6. Verifikasi focused test, regresi, Pint, route, migration/status; commit.

## Increment 4B: Demo Dan Finalisasi

1. Buat test seeder idempotent dan penolakan produksi/missing unit.
2. Implementasikan demo serta permission seeder Items.
3. Jalankan focused test, regresi, Pint, smoke seed ulang MySQL.
4. Isi checklist/hasil command, sinkronkan docs Items/Units dan work item global.
5. Commit otomatis; belum push karena Inventory Foundation belum selesai.

## Batas QA

SQLite in-memory untuk tests; MySQL lokal untuk migration dan demo relasional.
Tidak reset/rollback database pengguna. QA browser dan frontend SKIPPED:
belum ada perubahan UI/HTTP. Stock/FIFO/payment/tax/discount/pricing tidak dimutasi.
