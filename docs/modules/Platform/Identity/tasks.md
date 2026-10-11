# Tasks: Platform/Identity

## Baseline Saat Ini

- [x] Module source berada di `app/Modules/Platform/Identity`.
- [x] Route module berada di `Routes/web.php`.
- [x] Seeder module berada di `Database/Seeders`.
- [x] Shared auth memakai `superSystem`.
- [x] Hook permission frontend tersedia.
- [x] Focused identity test tersedia.
- [x] UI role/permission/akses pengguna existing tersedia.
- [x] CRUD/sync memakai HasMiddleware, Application authorization dan audit atomic.
- [x] Permission catalog dan demo role seeder tanpa reset akun tersedia.
- [x] Guard reserved/last-super/escalation dan browser desktop/tablet terverifikasi.
- [x] Self-delete profil melindungi last-super, audit atomic dan lifecycle sesi.
- [x] Tambah/edit/status akun internal, per-operation permission dan audit atomic.
- [x] Active last-super, version/remember guard, inactive dan sesi lama ditolak.

## Checklist Module Berikutnya

- [ ] Saat menambah permission baru, update seeder dan test.
- [ ] Saat menambah UI CRUD/sync, gunakan Sonner toast.
- [ ] Saat menambah controller, gunakan `HasMiddleware` dengan `can:*` per
  action atau policy eksplisit.
- [ ] Saat menambah mutation akses, catat activity log.
- [ ] Saat menambah public boundary, dokumentasikan contract/DTOs/event.

## Hasil Verifikasi Terakhir Yang Diketahui

| Command | Hasil | Catatan |
| --- | --- | --- |
| `php artisan test --filter=Identity` | PASS | Focused identity tests |
| `php artisan test --filter=PermissionBaselineTest` | PASS | Permission baseline |
| `php artisan test --filter=AccessManagementTest` dan helper akses existing | PASS | 19 test/128 assertion |
| `php artisan test --compact` | PASS | 205 test/1213 assertion |
| AccountDeletionTest + profil/akses existing | PASS | 33 test/208 assertion |
| `npm run build` | PASS | UI akses ter-build |
| `npx eslint resources/js` | PASS | Source aktif; tanpa arsip/artefak user |
| `npx tsc --noEmit` | PASS | Typecheck global |
| Browser DevTools smoke | PASS | Tool tersedia kembali; guest redirect/login, asset 200 dan console bersih; bukan workflow authenticated |
| Browser Playwright Identity | PASS | Database disposable; CRUD akun/akses, status/auth lifecycle dan self-delete, desktop/tablet, readonly/delegated/users-only, console bersih |
| UserManagementTest/UserStatusTest | PASS | Credential allowlist, grant/takeover guard, schema defaults, session/remember/race, audit rollback; termasuk full suite |

Hasil detail dan batas QA tercatat pada
[work item UI akses](work-items/access-management/tasks.md).
Pengaman profil terbaru: [work item guard](work-items/account-deletion-guard/tasks.md).
Manajemen akun: [work item pengguna](work-items/user-management/tasks.md).
