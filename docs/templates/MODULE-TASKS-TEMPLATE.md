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
  - Acceptance: file awal module dibuat tanpa folder kosong placeholder.
  - Verification: `php artisan module:make {Category} {Module} --with-routes --with-tests`.

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

Jangan menambahkan pekerjaan baru ke checklist ini tanpa persetujuan user.
