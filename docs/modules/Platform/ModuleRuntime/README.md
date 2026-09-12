# Platform/ModuleRuntime

## Identitas

- Category: `Platform`
- Module: `ModuleRuntime`
- Source: `app/Console/Commands`, `app/Providers/AppServiceProvider.php`, `bootstrap/app.php`
- Frontend: tidak ada
- Status: Sebagian selesai; validasi route/migration module nyata menunggu modul domain pertama.

## Tujuan

Module runtime menyediakan fondasi teknis untuk membuat dan memuat module POS
secara konsisten dengan struktur `app/Modules/{Category}/{Module}`.

Fokus awalnya adalah generator module minimal, namespace `App\\Modules\\`,
auto-register `ServiceProvider.php` module, dan guardrail agar agent/developer
tidak membuat layer kosong atau abstraction spekulatif.

## Boundary

Memiliki:

- command `php artisan module:make`;
- struktur source module `app/Modules/{Category}/{Module}`;
- konvensi `ServiceProvider.php` sebagai composition root module;
- auto-register provider dari `app/Modules/*/*/ServiceProvider.php`;
- opsi scaffold route dan test module;
- guardrail overwrite via `--force`;
- dry-run via `--dry-run`.

Tidak memiliki:

- business module Catalog, Inventory, Sales, Purchasing, Finance, atau Reporting;
- domain rule POS;
- permission domain;
- migration domain;
- repository, port, event, adapter, atau Domain folder tanpa kebutuhan nyata;
- UI module.

## Public Boundary

Public boundary runtime saat ini berupa command Artisan:

```bash
php artisan module:make {Category} {Module}
```

Opsi yang tersedia:

- `--with-routes`
- `--with-tests`
- `--dry-run`
- `--force`

Module lain tidak memanggil `MakeModuleCommand` sebagai service domain. Command
ini hanya tooling development.

## Data dan Identifier

- Tidak memiliki tabel.
- Tidak memiliki primary identifier domain.
- Tidak membuat migration module secara otomatis.

## Permission dan Audit

- Tidak memiliki permission runtime aplikasi.
- Tidak mencatat activity log karena command ini tooling developer, bukan aksi user aplikasi.

## Operasi

- Namespace `App\\Modules\\` mengikuti autoload Laravel `App\\ => app/`.
- Command didaftarkan melalui `bootstrap/app.php`.
- Provider module diregister otomatis oleh `AppServiceProvider`.
- `ServiceProvider.php` hasil generator memuat route dan migration hanya bila file/folder tersedia.
- `--dry-run` harus dipakai lebih dulu jika struktur target belum pasti.

## Verifikasi Utama

```bash
composer dump-autoload
php -l app\Console\Commands\MakeModuleCommand.php
php -l app\Providers\AppServiceProvider.php
php artisan test --filter=MakeModuleCommandTest
php artisan module:make Platform Identity --with-routes --with-tests --dry-run
php artisan test
git diff --check
```
