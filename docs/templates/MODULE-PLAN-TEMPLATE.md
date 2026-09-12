# Implementation Plan: {Category}/{Module}

## Scope

Jelaskan scope module dan batas berhenti awal.

## Increment 1: Dokumentasi Module

- Perubahan:
  - `docs/modules/{Category}/{Module}/README.md`
  - `docs/modules/{Category}/{Module}/specification.md`
  - `docs/modules/{Category}/{Module}/plan.md`
  - `docs/modules/{Category}/{Module}/tasks.md`
- Dependency:
  - ...
- Acceptance:
  - scope, non-scope, acceptance criteria, dependency, risiko, dan verifikasi tertulis.
- Verifikasi:
  - `git diff --check`

## Increment 2: Skeleton Module

- Perubahan:
  - generate `app/Modules/{Category}/{Module}/` dengan generator.
- Dependency:
  - Increment 1 direview.
- Acceptance:
  - target path benar;
  - tidak ada layer kosong placeholder;
  - `ServiceProvider.php`, `Routes/web.php`, `Database/Migrations/.gitkeep`, dan `Database/Seeders/{Module}DemoSeeder.php` tersedia.
- Verifikasi:
  - `php artisan module:make {Category} {Module} --dry-run`
  - `php artisan module:make {Category} {Module} --with-tests`
  - `php artisan test --filter=MakeModuleCommandTest`
  - `git diff --check`

## Increment 3: Data Foundation

- Perubahan:
  - migration dan persistence minimum sesuai scope.
- Dependency:
  - Increment 2.
- Acceptance:
  - ULID;
  - constraint penting;
  - tidak ada direct mutation lintas module.
- Verifikasi:
  - focused migration/model tests.

## Increment 4: Backend Behavior Minimum

- Perubahan:
  - action/query/controller/request sesuai use case.
- Dependency:
  - Increment 3.
- Acceptance:
  - actor berizin berhasil;
  - actor tanpa izin ditolak;
  - validation failure jelas.
- Verifikasi:
  - focused feature tests.

## Increment 5: UI/Presentation Bila Dibutuhkan

- Perubahan:
  - route Inertia/page/frontend components.
- Dependency:
  - Increment 4.
- Acceptance:
  - page canonical;
  - build lulus;
  - QA browser dicatat jika UI berubah.
- Verifikasi:
  - `npm run build`
  - Chrome DevTools MCP QA atau status `SKIPPED/BLOCKED`.

## Increment 6: Demo Seeder Bila Relevan

- Perubahan:
  - isi `Database/Seeders/{Module}DemoSeeder.php` dengan data demo yang relevan.
- Dependency:
  - Increment data/persistence yang dibutuhkan tersedia.
- Acceptance:
  - relasi demo jelas;
  - seeder tidak bypass invariant bisnis;
  - jika belum relevan, alasan skip dicatat.
- Verifikasi:
  - `php artisan db:seed --class=...` pada database aman/test atau focused test seeder.

## Batas Berhenti

- ...

## Rollback

- Revert commit per increment.
- Rollback migration bila sudah dijalankan lokal.
- Jangan menghapus dependency module lain sebagai bagian rollback tanpa persetujuan.
