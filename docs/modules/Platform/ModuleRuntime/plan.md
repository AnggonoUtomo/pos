# Plan: Platform/ModuleRuntime

## Scope Module

Menjaga konsistensi scaffold module POS.

## Increment

| No | Status | Nama | Perubahan | Acceptance | Verifikasi |
| --- | --- | --- | --- | --- | --- |
| 1 | Passed | Generator dasar | Command `module:make` membuat module di `app/Modules` | Dry-run menampilkan path benar | `php artisan test --filter=MakeModuleCommandTest` |
| 2 | Passed | Route/database/seeder scaffold | Module baru memiliki `Routes/`, `Database/Migrations`, dan `Database/Seeders` | Output dry-run memuat file tersebut | `php artisan module:make Testing SampleModule --dry-run` |
| 3 | Planned | Validasi runtime/module | Tentukan apakah perlu module fisik atau validator tambahan | Keputusan tercatat dan source selaras | Work item baru |

## QA Automated

- Focused backend: `php artisan test --filter=MakeModuleCommandTest`.
- Dry-run manual: `php artisan module:make Testing SampleModule --dry-run`.
- Full gate bila generator diubah: `php artisan test` dan `git diff --check`.
