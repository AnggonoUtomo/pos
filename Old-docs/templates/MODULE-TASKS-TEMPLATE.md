# Tasks: {Category}/{Module}

## Sebelum Mulai

- [ ] Scope dan non-scope awal ditentukan.
- [ ] Dependency dan keputusan terbuka diketahui.
- [ ] Focused test atau cara verifikasi awal ditentukan.
- [ ] `AGENTS.md`, `docs/SPEC.md`, `docs/ARCHITECTURE.md`, `docs/MODULE-COMMUNICATION.md`, dan work-item aktif dibaca.

## Increment 1: Dokumentasi Module

- [ ] Buat README module.
  - Acceptance: boundary, public boundary, data, permission, audit, operasi, dan verifikasi utama tertulis.
  - Verification: `git diff --check`.
- [ ] Buat specification module.
  - Acceptance: scope, non-scope, contract, data, authorization, audit, dependency, acceptance criteria, dan risiko terbuka tertulis.
  - Verification: `git diff --check`.
- [ ] Buat implementation plan module.
  - Acceptance: increment tersusun berurutan.
  - Verification: `git diff --check`.

## Increment 2: Skeleton Module

- [ ] Dry-run generator.
  - Acceptance: target file benar dan tidak ada file ditulis.
  - Verification: `php artisan module:make {Category} {Module} --dry-run`.
- [ ] Generate skeleton module.
  - Acceptance: `ServiceProvider.php`, `Routes/web.php`, `Database/Migrations/.gitkeep`, `Database/Seeders/{Module}DemoSeeder.php`, dan test scaffold dibuat tanpa layer kosong placeholder.
  - Verification: `php artisan module:make {Category} {Module} --with-tests`.

## Increment 3: Data Foundation

- [ ] Tambahkan migration/persistence minimum.
  - Acceptance: ULID dan constraint utama tersedia.
  - Verification: focused migration/model tests.

## Increment 4: Backend Behavior Minimum

- [ ] Tambahkan use case/query/action minimum.
  - Acceptance: behavior utama berjalan dan authorization enforced.
  - Verification: focused feature tests.

## Increment 5: UI/Presentation Bila Dibutuhkan

- [ ] Tambahkan route/page/component.
  - Acceptance: page canonical, build lulus, dan QA browser dicatat.
  - Verification: `npm run build` dan Chrome DevTools MCP QA bila tersedia.

## Increment 6: Demo Seeder Bila Relevan

- [ ] Isi atau putuskan skip demo seeder.
  - Acceptance: data demo relevan dengan relasi module dan tidak bypass invariant bisnis, atau alasan skip dicatat.
  - Verification: `php artisan db:seed --class=...` pada database aman/test atau focused test seeder.

Jangan menambahkan pekerjaan baru ke checklist ini tanpa persetujuan user.
