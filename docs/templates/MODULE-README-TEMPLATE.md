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

## Permission dan Audit

- Permission:
  - ...
- Audit mutation:
  - ...

Metadata audit tidak boleh menyimpan secret, credential, token, atau payload sensitif yang tidak relevan.

## Operasi

- Migration module berada di `app/Modules/{Category}/{Module}/Database/Migrations/`.
- `ServiceProvider.php` menjadi composition root dan tidak berisi business logic.
- Seeder/factory dibuat hanya ketika ada kebutuhan test atau demo nyata.

## Verifikasi Utama

```bash
php artisan module:make {Category} {Module} --dry-run
php artisan test --filter={Module}
npm run build
git diff --check
```
