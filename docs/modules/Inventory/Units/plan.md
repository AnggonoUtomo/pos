# Plan: Inventory/Units

## Increment 3

1. Selaraskan ownership satuan/barang melalui ADR-009 dan indeks module.
2. Dokumentasikan schema, demo, permission, dan acceptance sebelum coding.
3. Dry-run dan generate module melalui generator existing.
4. Buat failing test untuk persistence, seeder, dan audit.
5. Tambahkan schema/model, seeder access/demo, dan konfigurasi audit.
6. Jalankan focused test, full backend regression, Pint, route inspection,
   migration/status, smoke seed ulang MySQL, dan git diff --check.
7. Isi checklist berdasarkan hasil, perbarui work item, dan commit otomatis.

## QA Dan Batas

Test menggunakan SQLite in-memory. Migration tambahan diterapkan pada MySQL
lokal setelah focused test lulus; seed ulang tidak menimpa perubahan pengguna.
Tidak menjalankan reset/rollback database pengguna. QA browser, lint, build,
dan typecheck frontend SKIPPED selama belum ada UI/endpoint baru.

Konversi positif, ratio base unit 1, dan relasi barang diuji pada Increment 4.
Lookup contract sudah tersedia bersama consumer CreateItem. Work item global
ditutup setelah CRUD dan QA Increment 6 lulus.

## Increment 6: CRUD Dan Guard Referensi

ManageUnit mengorkestrasi update/delete/restore atomic dan guard ItemUnitUsage
publik termasuk data trashed (ADR-011). UnitLookup menyediakan pilihan aktif
untuk form barang, maksimum 1000. Halaman Inertia memiliki search/filter,
pagination, lifecycle, Sonner, permission UX, dan shortcut. QA kini mencakup
HTTP tests, lint/typecheck/build serta browser desktop/tablet; detail aktual
ada pada work item Inventory Foundation, bukan batas QA Increment 3 di atas.
