# Struktur Folder Canonical

Struktur ini menjadi acuan module baru dan refactor module lama. Folder opsional
hanya dibuat ketika memiliki isi dan concern nyata. Khusus `Routes/`,
`Database/Migrations/`, dan `Database/Seeders/`, module baru boleh dibuat sejak
awal karena itu bagian dari scaffold standar project.

## Backend Module

```text
app/Modules/{Domain}/{Module}/
|-- Application/
|   |-- Actions/
|   |-- Commands/             # opsional
|   |-- Contracts/
|   |-- DTOs/
|   |-- Events/               # opsional, hanya bila ada consumer
|   |-- Exceptions/           # opsional
|   |-- Listeners/            # opsional
|   |-- Queries/
|   `-- Services/             # opsional
|-- Domain/                   # hanya bila ada aturan bisnis murni
|   |-- Contracts/            # opsional
|   |-- Entities/
|   |-- Events/
|   |-- Exceptions/
|   |-- Services/
|   `-- ValueObjects/
|-- Infrastructure/
|   |-- Persistence/
|   |   |-- Models/
|   |   `-- Repositories/
|   |-- External/             # opsional
|   `-- Support/              # opsional
|-- Presentation/
|   |-- Console/Commands/
|   |-- Controllers/
|   |-- Middleware/
|   |-- Policies/
|   |-- Requests/
|   |-- Resources/
|   `-- Support/
|-- Database/
|   |-- Factories/
|   |-- Migrations/
|   `-- Seeders/
|-- Routes/
|   `-- web.php
|-- README.md
`-- ServiceProvider.php
```

## Artefak Root Module

| Artefak | Path project |
| --- | --- |
| Manifest module | Tidak digunakan saat ini |
| Runtime config module | Didaftarkan melalui `ServiceProvider.php` bila dibutuhkan |
| Permission identity | Seeder permission pada module terkait |
| Composition root | `app/Modules/{Domain}/{Module}/ServiceProvider.php` |
| Route module | `app/Modules/{Domain}/{Module}/Routes/web.php` |
| Migration module | `app/Modules/{Domain}/{Module}/Database/Migrations` |
| Demo seeder module | `app/Modules/{Domain}/{Module}/Database/Seeders/{Module}DemoSeeder.php` |

## Frontend Module

```text
resources/js/pages/{domain}/{module}/
|-- components/
|   |-- cards.tsx
|   |-- data-table.tsx
|   `-- ...
|-- hooks/                    # opsional, khusus fitur
|-- lib/                      # opsional, helper lokal fitur
|-- index.tsx
`-- types.ts                  # opsional
```

Aturan frontend:

- Gunakan lowercase kebab-case untuk path page, misalnya
  `resources/js/pages/platform/users`.
- Komponen fitur diletakkan dekat page.
- `resources/js/components/shared` hanya untuk komponen lintas fitur yang stabil.
- `resources/js/hooks/use-permission.ts` adalah hook permission global untuk UX.
- shadcn/ui dan `lucide-react` menjadi default.
- Sonner toast digunakan untuk feedback CRUD.

## Test Executable

```text
tests/Feature/Modules/{Domain}/{Module}/
tests/Unit/Modules/{Domain}/{Module}/
```

Frontend automated test belum menjadi baseline wajib sampai runner khusus
ditetapkan. Untuk perubahan UI/browser, lakukan QA via Chrome DevTools MCP
sesuai [QUALITY.md](QUALITY.md), lalu catat hasilnya pada work item.

## Dokumentasi Module

```text
docs/modules/{Domain}/{Module}/
|-- README.md
|-- specification.md
|-- plan.md
|-- tasks.md
|-- decisions/
`-- work-items/{nama-pekerjaan}/
    |-- README.md
    |-- plan.md
    `-- tasks.md
```

Pekerjaan lintas module memakai:

```text
docs/work-items/{nama-pekerjaan}/
|-- README.md
|-- plan.md
`-- tasks.md
```

## Generator Module

Gunakan generator project untuk module baru:

```powershell
php artisan module:make {Domain} {Module} --dry-run
php artisan module:make {Domain} {Module} --with-tests
```

Dry-run dijalankan bila struktur file belum pasti. Setelah generate, cek bahwa
route, migration, seeder, provider, dan test berada pada path canonical.

## Aturan Pembuatan

- Inventarisasi module dan generator sebelum mengubah struktur.
- Jangan menambahkan placeholder class atau abstraction tanpa behavior.
- Jangan membuat Domain bila capability masih CRUD sederhana tanpa rule murni.
- Jangan memindahkan migration module ke `database/migrations` global.
- Jangan memakai model Eloquent module lain sebagai dependency business mutation.
- Jika concern tidak cocok dengan struktur, buat ADR atau minta arahan sebelum
  coding.
