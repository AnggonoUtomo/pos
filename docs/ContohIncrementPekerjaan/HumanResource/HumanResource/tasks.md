# Tasks: HumanResource/HumanResource

## Sebelum Mulai

- [x] Scope dan non-scope awal ditentukan.
- [x] Dependency dan keputusan terbuka diketahui.
- [x] Focused test atau cara verifikasi awal ditentukan.
- [x] `AGENTS.md`, `docs/README.md`, `docs/ARCHITECTURE.md`,
  `docs/FOLDER-STRUCTURE.md`, `docs/MODULES.md`, dan roadmap module dibaca.

## Increment 1: Dokumentasi module

- [x] Buat README module.
  - Acceptance: boundary, public boundary, data, permission, audit, operasi,
    dan verifikasi utama tertulis.
  - Verification: `git diff --check`.
- [x] Buat specification module.
  - Acceptance: scope, non-scope, contract, data, authorization, audit,
    dependency, acceptance criteria, dan risiko terbuka tertulis.
  - Verification: `git diff --check`.
- [x] Buat implementation plan module.
  - Acceptance: increment skeleton, data foundation, backend behavior,
    lifecycle, audit, candidate contract, dan UI read page tersusun berurutan.
  - Verification: `git diff --check`.

## Increment 2: Skeleton module

- [x] Dry-run generator.
  - Acceptance: target `app/Modules/HumanResource/HumanResource`, diagnostics
    kosong, dan tidak ada folder optional placeholder.
  - Verification:
    `php artisan module:make HumanResource HumanResource --dry-run --json --no-ansi`.
- [x] Generate skeleton module.
  - Acceptance: file awal module dibuat tanpa folder kosong placeholder.
  - Verification:
    `php artisan module:make HumanResource HumanResource --force --yes --no-ansi`.
- [x] Validasi module registry.
  - Acceptance: semua module valid.
  - Verification: `php artisan module:validate --no-ansi`.

## Increment 3: Data foundation

- [x] Tambahkan migration `employees`.
  - Acceptance: ULID primary key, unique `employee_no`, type/status, joined/left
    date, dan timestamp tersedia.
  - Verification: focused migration/model tests.
- [x] Tambahkan migration `employee_unit_assignments` bila assignment masuk
  slice.
  - Acceptance: ULID primary key, foreign key employee, reference
    organization_unit, role, date range, primary marker, dan timestamp tersedia.
  - Verification: focused migration/model tests.
- [x] Tambahkan persistence minimum.
  - Acceptance: model/repository hanya dibuat bila dipakai oleh use case.
  - Verification: focused unit tests.
- [x] Tambahkan permission identity awal.
  - Acceptance: `human_resource.view` dan `human_resource.manage` valid.
  - Verification: focused permission identity test.

## Increment 4: Backend read/list dan create/update minimum

- [x] Tambahkan read/list employee.
  - Acceptance: actor dengan `human_resource.view` dapat membaca daftar
    employee.
  - Verification: focused feature test.
- [x] Tambahkan create/update employee.
  - Acceptance: actor dengan `human_resource.manage` dapat membuat dan mengubah
    employee; duplicate `employee_no` ditolak.
  - Verification: focused feature test.
- [x] Tambahkan authorization failure coverage.
  - Acceptance: actor tanpa permission mendapat response forbidden.
  - Verification: focused feature test.

## Increment 5: Employee lifecycle

- [x] Tambahkan activate employee.
  - Acceptance: actor dengan `human_resource.manage` dapat mengaktifkan kembali
    employee inactive sesuai rule yang disetujui.
  - Verification: focused lifecycle feature test.
- [x] Tambahkan deactivate employee.
  - Acceptance: actor dengan `human_resource.manage` dapat menonaktifkan
    employee secara aman.
  - Verification: focused lifecycle feature test.
- [x] Jaga lookup aktif.
  - Acceptance: employee inactive tidak muncul pada candidate lookup aktif.
  - Verification: focused lifecycle/query test.

## Increment 6: Audit mutation

- [x] Tambahkan audit create/update employee.
  - Acceptance: mutation menghasilkan audit entry/event yang aman.
  - Verification: focused audit test.
- [x] Tambahkan audit lifecycle dan assignment employee.
  - Acceptance: activate/deactivate/assignment menghasilkan audit entry/event
    yang aman.
  - Verification: focused audit test.

## Increment 7: Candidate employee lookup contract

- [x] Dokumentasikan query contract employee lookup.
  - Acceptance: contract menjelaskan input/output/failure tanpa mengekspos model
    Infrastructure.
  - Verification: `git diff --check`.
- [ ] Implementasikan contract hanya jika consumer nyata disetujui.
  - Acceptance: consumer memakai DTO/query public boundary, bukan Eloquent model
    HumanResource.
  - Verification: focused contract/query tests.

  Catatan: belum diimplementasikan pada Increment 7 karena belum ada consumer
  runtime yang disetujui. Ini menjaga contract tidak menjadi placeholder.

## Increment 8: UI/Inertia read page

- [x] Tambahkan route web Inertia HumanResource.
  - Acceptance: actor dengan `human_resource.view` dapat membuka halaman
    employee; actor tanpa permission ditolak backend.
  - Verification: focused presentation test.
- [x] Tambahkan frontend module canonical.
  - Acceptance: page berada di
    `resources/js/pages/HumanResource/HumanResource/pages/Index.tsx`, komponen
    business-specific berada di
    `resources/js/pages/HumanResource/HumanResource/components/`, dan
    `Index.tsx` tetap minimal.
  - Verification: `npm run types:check`, `npm run lint:check`,
    dan `npm run build`.
- [x] Tambahkan menu sidebar namespace HumanResource.
  - Acceptance: menu employee/SDM muncul untuk actor berizin
    `human_resource.view` atau `human_resource.manage`.
  - Verification: focused sidebar/Ziggy tests.

Jangan menambahkan pekerjaan baru ke checklist ini tanpa persetujuan user.
