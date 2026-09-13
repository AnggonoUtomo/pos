# Specification: Platform/ModuleRuntime

## Status

Generator module minimal sudah tersedia dan terverifikasi. Loader provider module
dasar sudah tersedia melalui scan `app/Modules/*/*/ServiceProvider.php`.
Validasi route/migration module nyata dilakukan saat modul domain pertama dibuat.

## Tujuan dan Scope

Scope awal:

- command `module:make`;
- validasi nama category dan module;
- dry-run tanpa menulis file;
- scaffold `ServiceProvider.php`;
- scaffold `Routes/web.php`;
- scaffold `Database/Migrations/.gitkeep`;
- scaffold `Database/Seeders/{Module}DemoSeeder.php`;
- scaffold test bila `--with-tests`;
- overwrite guard bila module sudah ada;
- namespace `App\\Modules\\` melalui autoload Laravel `App\\ => app/`;
- auto-register provider module.

## Arsitektur

- Hexagon: tidak berlaku sebagai business module.
- Tooling source:
  - `app/Console/Commands/MakeModuleCommand.php`.
- Runtime registration:
  - `bootstrap/app.php`;
  - `app/Providers/AppServiceProvider.php`.
- Generated composition root:
  - `app/Modules/{Category}/{Module}/ServiceProvider.php`.

Generator sengaja tidak membuat `Domain`, repository, port, event, adapter, atau
migration class tanpa kebutuhan nyata.

## Di Luar Scope

- Module metadata kompleks.
- Manifest `module.json`.
- Generator migration/domain/application/infrastructure otomatis.
- Generator UI otomatis.
- Validasi arsitektur penuh.
- Composer package discovery per module.
- Multi app atau package publishing.

## Contract

### Input

Argument:

- `category`: wajib, huruf pertama alphabetic, boleh huruf/angka/spasi/underscore/dash.
- `module`: wajib, huruf pertama alphabetic, boleh huruf/angka/spasi/underscore/dash.

Option:

- `--with-routes`: compatibility flag; `Routes/web.php` tetap dibuat default.
- `--with-tests`: membuat scaffold test feature dan unit placeholder.
- `--dry-run`: menampilkan rencana file tanpa menulis.
- `--force`: mengizinkan overwrite file generated.

### Output

File minimum:

- `app/Modules/{Category}/{Module}/ServiceProvider.php`.
- `app/Modules/{Category}/{Module}/Routes/web.php`.
- `app/Modules/{Category}/{Module}/Database/Migrations/.gitkeep`.
- `app/Modules/{Category}/{Module}/Database/Seeders/{Module}DemoSeeder.php`.

File opsional:

- `tests/Feature/Modules/{Category}/{Module}/{Module}ScaffoldTest.php`.
- `tests/Unit/Modules/{Category}/{Module}/.gitkeep`.

### Failure

- Nama category/module tidak valid: exit code failure.
- Module sudah ada tanpa `--force`: exit code failure.
- File existing tanpa `--force`: file dilewati dan diberi warning.

## Data

Tidak ada table.

## Authorization dan Audit

Tidak ada authorization aplikasi. Command ini berjalan di console developer.

## UI

Tidak ada UI.

## Dependency

- Laravel Console Command.
- Laravel File facade.
- Composer PSR-4 autoload.

## Acceptance Criteria

- [x] Command `module:make` tersedia di Artisan.
- [x] `--dry-run` tidak menulis file.
- [x] Generator membuat `ServiceProvider.php`.
- [x] Generator membuat `Routes/web.php`.
- [x] Generator membuat `Database/Migrations/.gitkeep`.
- [x] Generator membuat `Database/Seeders/{Module}DemoSeeder.php`.
- [x] Generator membuat test scaffold jika `--with-tests`.
- [x] Generator menolak overwrite module existing tanpa `--force`.
- [x] Generated service provider tidak membuat Domain placeholder.
- [x] Full test suite lulus setelah generator ditambahkan.
- [ ] Validasi route/migration module nyata dilakukan saat module domain pertama dibuat.

## Risiko Terbuka

- Auto-register provider via filesystem scan cukup untuk fase awal, tetapi bisa perlu registry/cache bila jumlah module besar.
- Generator belum memiliki output JSON; bila CI membutuhkan machine-readable output, tambahkan pada increment terpisah.
