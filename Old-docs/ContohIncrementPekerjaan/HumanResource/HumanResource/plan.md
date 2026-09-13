# Implementation Plan: HumanResource/HumanResource

## Scope

Pekerjaan ini memulai module `HumanResource/HumanResource` sebagai foundation
SDM pesantren: employee identity, tipe kerja, status lifecycle, assignment unit,
permission, audit, dan candidate lookup contract.

Increment awal berhenti pada dokumentasi, skeleton module, data foundation,
backend behavior minimum, lifecycle, audit, contract candidate, dan UI/Inertia
read page. UI mutation penuh menunggu increment eksplisit berikutnya.

## Increment 1: Dokumentasi module

- Perubahan:
  - `docs/modules/HumanResource/HumanResource/README.md`
  - `docs/modules/HumanResource/HumanResource/specification.md`
  - `docs/modules/HumanResource/HumanResource/plan.md`
  - `docs/modules/HumanResource/HumanResource/tasks.md`
- Dependency: Organization dan AcademicPeriod foundation selesai, roadmap Task
  6 siap dimulai.
- Acceptance: scope, non-scope, acceptance criteria, dependency, risiko, dan
  verifikasi tertulis.
- Verifikasi: `git diff --check`.

## Increment 2: Skeleton module

- Perubahan:
  - generate `app/Modules/HumanResource/HumanResource/` dengan generator;
  - review `module.json`, `module.php`, `permissions.php`,
    `ServiceProvider.php`, `README.md`, dan `Routes/*`.
- Dependency: Increment 1 direview.
- Acceptance:
  - target path `app/Modules/HumanResource/HumanResource`;
  - tidak ada folder kosong placeholder;
  - manifest valid.
- Verifikasi:
  - `php artisan module:make HumanResource HumanResource --dry-run --json --no-ansi`
  - `php artisan module:make HumanResource HumanResource --force --yes --no-ansi`
  - `php artisan module:validate --no-ansi`
  - `git diff --check`

## Increment 3: Data foundation

- Perubahan:
  - migration `employees`;
  - migration `employee_unit_assignments` bila assignment unit masuk slice;
  - model/persistence minimum;
  - permission identity awal.
- Dependency: Increment 2.
- Acceptance:
  - table memakai ULID;
  - `employees.employee_no` unik;
  - employee memiliki type/status minimum;
  - assignment terhubung ke unit organisasi tanpa direct dependency ke model
    privat Organization;
  - permission `human_resource.view` dan `human_resource.manage` tersedia.
- Verifikasi:
  - focused migration/model tests;
  - `php artisan module:validate --no-ansi`.

## Increment 4: Backend read/list dan create/update minimum

- Perubahan:
  - query/action minimum;
  - controller/request/resource atau API response;
  - route backend minimum;
  - authorization backend.
- Dependency: Increment 3.
- Acceptance:
  - actor berizin dapat membaca/membuat/memperbarui employee;
  - actor tanpa izin ditolak;
  - duplicate `employee_no` ditolak;
  - unit assignment invalid ditolak;
  - response tidak mengekspos field sensitif.
- Verifikasi:
  - focused feature tests HumanResource;
  - `php artisan module:validate --no-ansi`;
  - `php artisan starter:verify --no-ansi`.

## Increment 5: Employee lifecycle

- Perubahan:
  - action activate employee;
  - action deactivate employee;
  - rule lifecycle dasar sesuai keputusan scope.
- Dependency: Increment 4.
- Acceptance:
  - employee dapat diaktifkan/dinonaktifkan;
  - inactive employee tidak muncul pada lookup aktif;
  - failure dipetakan ke validation error yang jelas.
- Verifikasi:
  - focused lifecycle tests HumanResource.

## Increment 6: Audit mutation

- Perubahan:
  - audit create/update employee;
  - audit activate/deactivate employee;
  - audit assignment unit bila assignment dibuat.
- Dependency: Increment 4-5 dan bridge audit existing.
- Acceptance:
  - mutation employee menghasilkan audit entry/event;
  - metadata audit aman dan tidak memuat payload sensitif.
- Verifikasi:
  - focused audit tests HumanResource.

## Increment 7: Candidate employee lookup contract

- Perubahan:
  - dokumentasikan candidate lookup contract employee/teacher;
  - implementasi hanya bila consumer nyata disetujui.
- Dependency: Increment 5-6.
- Acceptance:
  - contract tidak mengekspos model Infrastructure;
  - DTO cukup ringkas untuk consumer Academic, Dormitory, Communication, atau
    Reporting;
  - tidak ada direct dependency lintas module.
- Verifikasi:
  - focused contract/query tests bila diimplementasikan;
  - `php artisan module:validate --no-ansi`.

## Increment 8: UI/Inertia read page

- Perubahan:
  - route web Inertia HumanResource;
  - controller presentation untuk props daftar employee;
  - frontend canonical di
    `resources/js/pages/HumanResource/HumanResource/`;
  - menu sidebar namespace HumanResource.
- Dependency: Increment 4-7.
- Acceptance:
  - page resolve dari canonical frontend module;
  - `Index.tsx` hanya menjadi komposer layout;
  - komponen business-specific berada di folder `components`;
  - backend tetap authority untuk permission.
- Verifikasi:
  - focused presentation tests HumanResource;
  - focused sidebar/Ziggy tests;
  - `npm run types:check`;
  - `npm run lint:check`;
  - `npm run build`.

## Batas Berhenti

Pekerjaan berhenti ketika backend foundation dan UI read page HumanResource
valid serta lulus focused tests. UI mutation penuh, import/export, payroll,
document employee, dan consumer integration menunggu persetujuan/increment
terpisah.

## Rollback

- Revert commit per increment.
- Untuk migration yang sudah dijalankan lokal, gunakan rollback migration
  module sesuai mekanisme Laravel sebelum menghapus source.
- Jangan mengubah atau menghapus data/module `Organization/Organization` dan
  `Academic/AcademicPeriod` sebagai bagian dari rollback HumanResource.
