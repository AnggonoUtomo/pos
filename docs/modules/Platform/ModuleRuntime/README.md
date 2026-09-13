# Platform/ModuleRuntime

## Status

Pending Validation

## Tujuan

Capability ini mengatur discovery, bootstrap, dan scaffolding module. Saat ini
source terkait belum menjadi module fisik penuh di `app/Modules`, sehingga perlu
work item tersendiri bila akan dirapikan menjadi module platform.

## Boundary

- Memiliki: generator module, aturan scaffold route, database, seeder, provider,
  dan test module.
- Tidak memiliki: business capability POS, authorization bisnis, atau migration
  milik module lain.

## Public Boundary

- Command: `php artisan module:make {Domain} {Module}`.
- Contract: belum ada.
- DTOs: belum ada.
- Events: belum ada.

## Dependency

- Laravel console command.
- Filesystem project.
- Struktur canonical `app/Modules/{Domain}/{Module}`.

## Data Dan Seeder

Tidak memiliki tabel bisnis. Seeder tidak relevan kecuali nanti ada registry
module persisted.

## Verifikasi Utama

```powershell
php artisan test --filter=MakeModuleCommandTest
php artisan module:make Testing SampleModule --dry-run
```
