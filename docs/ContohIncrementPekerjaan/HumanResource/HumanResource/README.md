# HumanResource

Source module mengikuti [`docs/ARCHITECTURE.md`](../../../ARCHITECTURE.md) dan
[`docs/FOLDER-STRUCTURE.md`](../../../FOLDER-STRUCTURE.md).

## Identitas

- Namespace: `HumanResource`
- Module: `HumanResource`
- Source: `app/Modules/HumanResource/HumanResource/`
- Frontend: `resources/js/pages/HumanResource/HumanResource/`
- Status: `Active`

## Tujuan

Module `HumanResource/HumanResource` menjadi source of truth SDM pesantren:
pegawai, guru, ustadz, staff, jabatan, status kepegawaian, dan penugasan kerja
minimum.

Module ini dibangun setelah `Organization/Organization` dan
`Academic/AcademicPeriod` karena data SDM membutuhkan konteks unit organisasi,
serta nantinya menjadi dependency untuk Academic, Dormitory, Communication, dan
Reporting.

## Boundary

- Memiliki:
  - identitas employee/pegawai;
  - nomor pegawai sebagai business identifier;
  - tipe/persona kerja seperti teacher, ustadz, musyrif, finance staff,
    administration staff, dan unit head;
  - status kepegawaian dasar;
  - jabatan/position dasar;
  - assignment pegawai ke unit organisasi;
  - lifecycle aktif/nonaktif pegawai.
- Tidak memiliki:
  - user account, login, role, permission, dan 2FA;
  - payroll, gaji, tunjangan, slip gaji, dan pajak;
  - jadwal mengajar detail, kelas, subject offering, dan presensi akademik;
  - placement asrama dan occupancy santri;
  - dokumen/file pegawai selain referensi future ke module Document;
  - approval workflow HR kompleks;
  - multi-yayasan/multi-tenant SaaS.

## Public Boundary

Public boundary dibuat hanya ketika ada consumer nyata. Candidate consumer awal:

- `Academic/Academic` membutuhkan teacher/ustadz lookup untuk teaching
  assignment;
- `StudentLife/Dormitory` membutuhkan musyrif/pegawai asrama;
- `Communication/Announcement` membutuhkan audience employee group;
- `Platform/Reporting` membutuhkan projection jumlah pegawai per unit/status.

Candidate public boundary:

- query employee lookup aktif;
- DTO ringkas employee identity;
- query teacher/ustadz lookup aktif untuk Academic;
- event perubahan lifecycle employee bila AuditTrail, Reporting, atau module
  consumer perlu merespons perubahan.

Employee lookup sudah menjadi source contract runtime karena
`Academic/KelasRombel` memakai `ActiveEmployeeReader` sebagai consumer nyata
untuk validasi wali kelas. Contract menyediakan opsi pegawai aktif dan lookup
pegawai aktif by-id tanpa mengekspos `Infrastructure/Models/EmployeeRecord`.

## Data dan Identifier

- Table awal:
  - `employees`
  - `employee_unit_assignments`
- Primary identifier: ULID.
- Business identifier:
  - `employee_no`, misalnya `EMP-2026-0012`.

## Permission dan Audit

- Permission awal:
  - `human_resource.view`
  - `human_resource.manage`
- Backend menjadi authority authorization.
- Frontend permission hanya UX; backend tetap authority authorization.
- Audit mutation minimum:
  - `human_resource.employee.created`
  - `human_resource.employee.updated`
  - `human_resource.employee.activated`
  - `human_resource.employee.deactivated`
  - `human_resource.employee.assigned_to_unit`

Metadata audit tidak boleh menyimpan secret, credential, token, atau payload
sensitif yang tidak relevan.

Audit runtime saat ini sudah mencatat create, update, activate, deactivate, dan
assignment employee melalui event `human-resource.activity.occurred`.

## Operasi

- Migration module berada di
  `app/Modules/HumanResource/HumanResource/Database/Migrations/`.
- Persistence minimum tersedia di
  `app/Modules/HumanResource/HumanResource/Infrastructure/Models/` untuk
  `EmployeeRecord` dan `EmployeeUnitAssignmentRecord`.
- `ServiceProvider.php` menjadi composition root dan tidak berisi business
  logic.
- Seeder/factory dibuat hanya ketika ada kebutuhan test atau demo nyata.
- Public contract `ActiveEmployeeReader` tersedia untuk lookup pegawai/guru
  aktif oleh module consumer nyata.
- Web route Inertia read page tersedia di `human-resource/employees` dengan
  named route `human-resource.employees.index`.
- Frontend read page tersedia di
  `resources/js/pages/HumanResource/HumanResource/pages/Index.tsx`, dengan
  komponen business-specific di folder `components`.
- Menu sidebar namespace `HumanResource` menampilkan `SDM Pesantren` untuk actor
  berizin `human_resource.view` atau `human_resource.manage`.
- API lifecycle minimum tersedia untuk activate/deactivate employee. Deactivate
  wajib menerima `left_on` dan ditolak bila employee masih memiliki assignment
  unit aktif.
- API assignment minimum tersedia untuk membuat penugasan employee ke unit.
  Assignment update/close belum masuk slice saat ini.
- Payroll tidak masuk release awal kecuali diputuskan melalui work item
  terpisah.

## Verifikasi Utama

```bash
php artisan module:make HumanResource HumanResource --dry-run --json --no-ansi
php artisan module:make HumanResource HumanResource --force --yes --no-ansi
php artisan test --filter=HumanResource
php artisan module:validate --no-ansi
php artisan starter:verify --no-ansi
npm run types:check
npm run lint:check
npm run build
git diff --check
```
