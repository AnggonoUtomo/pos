# WI-0004: Baseline Identitas dan Akses

Status: Draf

## Tujuan

Menyiapkan auth internal, Spatie Permission, public registration off, role/permission dasar, dan akses gudang user.

## Rujukan

- `docs/adr/ADR-0005-auth-permission-and-activity-log.md`
- `docs/SPEC.md`
- `docs/QA-AUTOMATION.md`

## Modul Target

- Category: Platform
- Module: Identity
- Namespace/path: `app/Modules/Platform/Identity`
- Jenis pekerjaan: auth internal, role, permission, dan audit access

## Scope

Masuk scope:

- Install/config Spatie Permission jika belum tersedia.
- Disable public registration.
- Seed super admin.
- Role dan permission baseline.
- Struktur akses gudang user jika warehouse module siap atau placeholder contract.

Di luar scope:

- UI access matrix lengkap.
- SSO atau external auth.

## Checklist Sebelum Coding

- [ ] Starter kit auth teridentifikasi.
- [ ] Route register diketahui.
- [ ] Permission naming disetujui.

## Rencana Increment

| Increment | Fokus | Checklist Sebelum | Checklist Sesudah | QA automated | Status |
| --- | --- | --- | --- | --- | --- |
| INC-01 | Disable public registration | - [ ] Route register dan page register ditemukan | - [ ] Register publik tidak bisa diakses | `php artisan test --filter=RegistrationTest` | Draf |
| INC-02 | Spatie Permission baseline | - [ ] Nama role/permission disepakati | - [ ] Role/permission seed dan middleware berjalan | `php artisan test --filter=Permission` | Draf |
| INC-03 | Activity log identity | - [ ] Event perubahan akses ditentukan | - [ ] Perubahan role/permission tercatat | test activity log identity terkait | Draf |

## Kriteria Penerimaan

- [ ] Public registration tidak bisa diakses.
- [ ] Flow admin-created user tersedia atau direncanakan jelas.
- [ ] Role/permission dasar berjalan.
- [ ] Permission middleware/policy baseline tersedia.
- [ ] Activity log mencatat perubahan role/permission penting.

## Verifikasi

- [ ] `php artisan test --filter=Auth`
- [ ] `php artisan test --filter=Permission`
- [ ] Pemeriksaan manual route register nonaktif
- [ ] `npm run build` jika UI berubah
