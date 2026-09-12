# Tasks: Platform/ModuleRuntime

## Sebelum Mulai

- [x] Scope dan non-scope awal ditentukan.
- [x] Dependency dan keputusan terbuka diketahui.
- [x] Focused test generator ditentukan.
- [x] `AGENTS.md`, `docs/SPEC.md`, `docs/ARCHITECTURE.md`, `docs/QA-AUTOMATION.md`, dan WI-0003 dibaca.

## Increment 1: Dokumentasi Module Runtime

- [x] Buat README module.
  - Acceptance: boundary, public boundary, data, permission, audit, operasi, dan verifikasi utama tertulis.
  - Verification: `git diff --check`.
- [x] Buat specification module.
  - Acceptance: scope, non-scope, contract, data, authorization, audit, dependency, acceptance criteria, dan risiko terbuka tertulis.
  - Verification: `git diff --check`.
- [x] Buat implementation plan module.
  - Acceptance: increment generator, provider registration, dan validasi module domain pertama tersusun berurutan.
  - Verification: `git diff --check`.

## Increment 2: Generator Module Minimal

- [x] Tulis failing test command generator.
  - Acceptance: test gagal karena `module:make` belum tersedia.
  - Verification: `php artisan test tests/Feature/Console/MakeModuleCommandTest.php`.
- [x] Implementasikan command `module:make`.
  - Acceptance: dry-run, scaffold minimal route/database/demo seeder, test flag, dan overwrite guard berjalan.
  - Verification: `php artisan test --filter=MakeModuleCommandTest`.
- [x] Pastikan namespace `App\\Modules\\` mengikuti autoload Laravel.
  - Acceptance: class generated module autoload-able melalui `App\\ => app/`.
  - Verification: `composer dump-autoload`.

## Increment 3: Provider Auto-Register Dasar

- [x] Tambahkan auto-register provider module.
  - Acceptance: scan `app/Modules/*/*/ServiceProvider.php` dan register class yang ada.
  - Verification: `php -l app\Providers\AppServiceProvider.php`, `php artisan test`.

## Increment 4: Validasi Dengan Module Domain Pertama

- [ ] Validasi route module nyata.
  - Acceptance: route module domain pertama tampil di `route:list`.
  - Verification: `php artisan route:list --except-vendor`.
- [ ] Validasi migration module nyata.
  - Acceptance: migration module domain pertama terlihat dan aman.
  - Verification: `php artisan migrate:status`.

Jangan menambahkan pekerjaan baru ke checklist ini tanpa persetujuan user.
