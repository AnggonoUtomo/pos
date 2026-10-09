# Plan: Inventory/Warehouses

## Urutan

1. Dokumentasikan fondasi dan dry-run generator.
2. Generate module lalu tambahkan test persistence, permission, audit, dan demo.
3. Implementasikan migration/model dan kedua seeder module.
4. Jalankan focused test, formatter, route list, migration/status, dan review diff.
5. Perbarui checklist sesuai hasil nyata dan commit Increment 2.

Tahap ini mengikuti [work item lintas module](../../../work-items/inventory-foundation/plan.md).
UI, endpoint CRUD, dan integrasi transaksi menunggu increment terkait.

## QA

Test menggunakan SQLite in-memory sesuai phpunit.xml. Migration baru juga
diterapkan pada database lokal terkonfigurasi setelah test lulus. Seeder demo
diuji dua kali serta setelah nama/status/soft delete berubah. Tidak menjalankan
migrate:fresh atau rollback pada database pengguna.

Chrome DevTools, lint, dan build frontend tidak relevan selama belum ada UI
atau route baru. Smoke route existing tetap diperiksa.
