# WI-0004: Baseline Identitas dan Akses

Status: Draf

## Tujuan

Menyiapkan auth internal, Spatie Permission, public registration off, role/permission dasar, dan akses gudang user.

## Rujukan

- `docs/adr/ADR-0005-auth-permission-and-activity-log.md`
- `docs/SPEC.md`

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

## Kriteria Penerimaan

- [ ] Public registration tidak bisa diakses.
- [ ] Admin-created user fRendah tersedia atau direncanakan jelas.
- [ ] Role/permission dasar berjalan.
- [ ] Permission middleware/policy baseline tersedia.
- [ ] Activity log mencatat perubahan role/permission penting.

## Verifikasi

- [ ] `php artisan test --filter=Auth`
- [ ] `php artisan test --filter=Permission`
- [ ] Pemeriksaan manual route register nonaktif
- [ ] `npm run build` jika UI berubah
