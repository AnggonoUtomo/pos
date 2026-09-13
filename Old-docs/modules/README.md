# Dokumentasi Module

Folder ini menyimpan dokumentasi module per bounded context.

Struktur canonical:

```text
docs/modules/{Category}/{Module}/
  README.md
  specification.md
  plan.md
  tasks.md
```

## Peran Dokumen

- `README.md`: ringkasan module, boundary, public boundary, kepemilikan data, permission, audit, operasi, dan verifikasi utama.
- `specification.md`: kontrak perilaku module, input/output, failure, data, authorization, dependency, acceptance criteria, dan risiko.
- `plan.md`: urutan increment implementasi, dependency antar increment, batas berhenti, dan rollback.
- `tasks.md`: checklist pekerjaan yang diisi sebelum dan sesudah coding.

## Aturan

- Dokumentasi module dibuat sebelum coding module dimulai.
- Public boundary hanya dibuat jika ada consumer nyata.
- Domain, port, event, repository, adapter, dan migration class tidak dibuat hanya untuk melengkapi diagram.
- Module baru menyimpan route dan database miliknya di `Routes/`, `Database/Migrations/`, dan `Database/Seeders/`.
- Demo seeder module diisi bila ada master/operational data relevan untuk demo, test manual, atau relasi lintas module. Jika tidak relevan, alasan skip dicatat pada work-item.
- Work-item aktif harus merujuk dokumen module bila pekerjaan menyentuh module tersebut.
- Setiap bukti verifikasi harus mencatat command nyata dan hasilnya.
