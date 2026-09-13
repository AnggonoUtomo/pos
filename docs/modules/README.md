# Dokumentasi Module

Folder ini menyimpan dokumentasi per module aktif atau module yang mulai
direncanakan secara serius.

## Lokasi

Setiap module yang baru dibuat atau mulai memperoleh pekerjaan signifikan
memakai folder:

```text
docs/modules/{Domain}/{Module}/
|-- README.md
|-- specification.md
|-- plan.md
|-- tasks.md
|-- decisions/
`-- work-items/
```

Nama Domain dan Module mengikuti source code, misalnya `Platform/Identity`.
Folder work item memakai `kebab-case`, misalnya
`work-items/role-permission-ui/`.

## Isi Minimum

- `README.md`: ownership, status, dependency, route, permission, dan link penting.
- `specification.md`: rule, contract, data, UI, dan QA khusus module.
- `plan.md`: increment pengembangan module.
- `tasks.md`: checklist module yang terus diperbarui.
- `decisions/`: ADR khusus module bila ada.
- `work-items/`: pekerjaan signifikan di dalam module.

## Aturan

- Jangan membuat folder kosong untuk module yang belum akan dikerjakan.
- Module baru di source dibuat dengan `php artisan module:make {Domain} {Module}`.
- Route, migration, dan seeder module berada di dalam module source.
- Demo seeder wajib dipertimbangkan dan harus memakai relasi yang relevan.
- Struktur source module wajib mengikuti
  [`../ARCHITECTURE.md`](../ARCHITECTURE.md) dan
  [`../FOLDER-STRUCTURE.md`](../FOLDER-STRUCTURE.md).
