# WI-0003: Scaffold Folder dan Loader Modul

Status: Sedang Dikerjakan

## Tujuan

Menyiapkan fondasi folder modul dan mekanisme loading route/migration/provider tanpa membuat fitur domain besar.

## Rujukan

- `docs/ARCHITECTURE.md`
- `docs/adr/ADR-0001-modular-monolith-ddd-lite-hexagonal.md`
- `docs/QA-AUTOMATION.md`
- `docs/modules/Platform/ModuleRuntime/README.md`
- `docs/modules/Platform/ModuleRuntime/specification.md`
- `docs/modules/Platform/ModuleRuntime/plan.md`
- `docs/modules/Platform/ModuleRuntime/tasks.md`

## Modul Target

- Category: Platform
- Module: ModuleRuntime
- Namespace/path: `app/Modules/{Category}/{Module}`, loader module, provider module
- Jenis pekerjaan: scaffold arsitektur module runtime

## Scope

Masuk scope:

- Struktur `app/Modules/{Category}/{Module}`.
- Module service provider atau loader sesuai pola Laravel yang disepakati.
- Loading route modul.
- Loading migration modul.
- `ServiceProvider.php` sebagai composition root module.
- Dokumentasi cara membuat modul baru.

Di luar scope:

- Implementasi bisnis Catalog, Sales, Purchasing, dan Inventory.
- Schema transaksi.

## Checklist Sebelum Coding

- [ ] WI-0001 dan WI-0002 selesai.
- [ ] Pola autoload composer dipahami.
- [ ] Risiko route/migration discovery dicatat.

## Rencana Increment

| Increment | Fokus | Checklist Sebelum | Checklist Sesudah | QA automated | Status |
| --- | --- | --- | --- | --- | --- |
| INC-01 | Struktur folder dan autoload module | - [x] Struktur namespace disetujui | - [x] Folder canonical tersedia<br>- [x] Autoload berjalan | `composer dump-autoload` | Selesai |
| INC-02 | Loader route dan migration module | - [x] Pola Laravel provider dibaca | - [x] Loader provider dasar tersedia<br>- [ ] Route/migration module nyata tervalidasi | `php artisan route:list`, `php artisan migrate:status` | Sebagian Selesai |
| INC-03 | Dokumentasi convention module | - [x] Struktur final dicek | - [x] Panduan membuat module tersedia | `git diff --check` | Selesai |
| INC-04 | Generator modul minimal | - [x] Guardrail generator disepakati<br>- [x] Tidak membuat Domain/port/event spekulatif | - [x] `module:make` tersedia<br>- [x] `--dry-run`, `--with-routes`, `--with-tests`, dan overwrite guard berjalan | `php artisan test tests/Feature/Console/MakeModuleCommandTest.php` | Selesai |

## Kriteria Penerimaan

- [x] Folder modul awal tersedia.
- [ ] Route modul nyata dapat diload saat modul domain pertama dibuat.
- [ ] Migration modul nyata dapat diload saat modul domain pertama dibuat.
- [x] Autoload namespace bekerja.
- [x] Binding contract-adapter, route, migration, policy/listener diarahkan melalui ServiceProvider module.
- [x] ServiceProvider tidak berisi business logic.
- [x] Dokumentasi module convention tersedia.

## Verifikasi

- [x] `composer dump-autoload`
- [x] `php artisan route:list`
- [x] `php artisan migrate:status`
- [x] `php artisan test`

## Catatan Implementasi

- `php artisan module:make {Category} {Module}` ditambahkan sebagai generator minimal.
- Generator membuat `ServiceProvider.php` dan hanya membuat route/test scaffold jika flag diminta.
- Aplikasi melakukan auto-register provider module dari `app/Modules/*/*/ServiceProvider.php`.
- Generator tidak membuat folder `Domain`, repository, port, event, adapter, atau migration secara otomatis.

## Bukti

```text
Command: composer dump-autoload
Hasil: PASS
Catatan: optimized autoload generated.

Command: php -l app\Console\Commands\MakeModuleCommand.php
Hasil: PASS
Catatan: No syntax errors detected.

Command: php artisan test tests/Feature/Console/MakeModuleCommandTest.php
Hasil: PASS
Catatan: 3 tests, 13 assertions.

Command: php artisan test
Hasil: PASS
Catatan: 29 tests, 76 assertions.

Command: php artisan module:make Platform Identity --with-routes --with-tests --dry-run
Hasil: PASS
Catatan: Menampilkan rencana file tanpa membuat file.

Command: php artisan route:list --except-vendor
Hasil: PASS
Catatan: 23 route baseline tampil.

Command: php artisan migrate:status
Hasil: PASS
Catatan: Migration baseline aplikasi tampil.
```
