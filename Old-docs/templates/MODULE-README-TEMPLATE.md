# {Category}/{Module}

## Identitas

- Category:
- Module:
- Source: `app/Modules/{Category}/{Module}/`
- Frontend:
- Status:

## Tujuan

Jelaskan tujuan module dan masalah produk yang diselesaikan.

## Boundary

Memiliki:

- ...

Tidak memiliki:

- ...

## Public Boundary

Public boundary hanya dibuat ketika ada consumer nyata.

Candidate consumer:

- ...

Candidate public boundary:

- ...

## Data dan Identifier

- Table awal:
  - ...
- Primary identifier: ULID.
- Business identifier:
  - ...
- Delete strategy:
  - Soft delete default untuk CRUD master/operasional mutable, atau alasan pengecualian dicatat.

## Permission dan Audit

- Permission:
  - ...
- Backend permission guard:
  - ...
- Frontend permission guard:
  - `resources/js/hooks/use-permission.ts` untuk UX guard bila UI module membutuhkan kondisi permission.
- UI feedback:
  - Sonner toast untuk feedback create/update/delete/restore bila UI module memiliki CRUD.
- Audit mutation:
  - ...

Metadata audit tidak boleh menyimpan secret, credential, token, atau payload sensitif yang tidak relevan.

## Operasi

- Route module berada di `app/Modules/{Category}/{Module}/Routes/`.
- Migration module berada di `app/Modules/{Category}/{Module}/Database/Migrations/`.
- Demo seeder module berada di `app/Modules/{Category}/{Module}/Database/Seeders/{Module}DemoSeeder.php`.
- `ServiceProvider.php` menjadi composition root dan tidak berisi business logic.
- Demo seeder diisi ketika ada kebutuhan test manual, demo, atau relasi module yang nyata. Jika belum relevan, alasan skip dicatat pada work-item.

## Verifikasi Utama

```bash
php artisan module:make {Category} {Module} --dry-run
php artisan test --filter={Module}
npm run build
git diff --check
```
