# Implementation Plan: Platform/ModuleRuntime

## Scope

Pekerjaan ini menyiapkan fondasi teknis module runtime dan generator module
minimal untuk POS. Scope berhenti pada scaffold konsisten, auto-register
provider dasar, test generator, dan dokumentasi module runtime.

## Increment 1: Dokumentasi Module Runtime

- Perubahan:
  - `docs/modules/Platform/ModuleRuntime/README.md`
  - `docs/modules/Platform/ModuleRuntime/specification.md`
  - `docs/modules/Platform/ModuleRuntime/plan.md`
  - `docs/modules/Platform/ModuleRuntime/tasks.md`
- Dependency:
  - baseline arsitektur dan WI-0003 tersedia.
- Acceptance:
  - boundary, non-scope, contract, acceptance, risiko, dan verifikasi tertulis.
- Verifikasi:
  - `git diff --check`.

## Increment 2: Generator Module Minimal

- Perubahan:
  - `app/Console/Commands/MakeModuleCommand.php`
  - `bootstrap/app.php`
  - `composer.json`
  - `tests/Feature/Console/MakeModuleCommandTest.php`
- Dependency:
  - keputusan struktur `app/Modules/{Category}/{Module}`.
- Acceptance:
  - command tersedia;
  - dry-run tidak menulis file;
  - scaffold minimal sesuai guardrail route/database/demo seeder module;
  - overwrite ditolak tanpa `--force`.
- Verifikasi:
  - `composer dump-autoload`
  - `php artisan test --filter=MakeModuleCommandTest`
  - `php artisan module:make Platform Identity --with-tests --dry-run`

## Increment 3: Provider Auto-Register Dasar

- Perubahan:
  - `app/Providers/AppServiceProvider.php`
- Dependency:
  - namespace `App\\Modules\\` tersedia melalui autoload Laravel `App\\ => app/`.
- Acceptance:
  - provider di `app/Modules/*/*/ServiceProvider.php` diregister bila class ada;
  - tidak gagal jika folder `Modules` belum ada;
  - provider tetap composition root module.
- Verifikasi:
  - `php -l app\Providers\AppServiceProvider.php`
  - `php artisan test`

## Increment 4: Validasi Dengan Module Domain Pertama

- Perubahan:
  - belum dilakukan.
- Dependency:
  - module domain pertama disetujui.
- Acceptance:
  - route module nyata terload;
  - migration module nyata terlihat;
  - tidak ada folder placeholder tidak dipakai.
- Verifikasi:
  - `php artisan route:list --except-vendor`
  - `php artisan migrate:status`
  - focused tests module terkait.

## Batas Berhenti

Pekerjaan berhenti ketika generator minimal, auto-register provider dasar, dan
dokumentasi runtime tersedia. Generator lanjutan seperti JSON output, UI
scaffold, atau manifest module menunggu work-item eksplisit.

## Rollback

- Revert commit generator.
- Jalankan `composer dump-autoload` setelah menghapus namespace autoload bila diperlukan.
- Hapus module hasil generator hanya jika module tersebut memang dibuat oleh increment yang sama.
