# Plan: Platform/Identity

## Scope Module

Menyediakan fondasi identity dan access control untuk seluruh aplikasi POS.

## Increment

| No | Status | Nama | Perubahan | Acceptance | Verifikasi |
| --- | --- | --- | --- | --- | --- |
| 1 | Passed | Baseline package | Spatie Permission dan Activitylog tersedia | Package dapat digunakan aplikasi | `composer show spatie/laravel-permission`; `composer show spatie/laravel-activitylog` |
| 2 | Passed | Shared auth permission | Inertia shared auth menyediakan user, roles, permissions, superSystem | Frontend bisa membaca permission untuk UX | `php artisan test --filter=Identity` |
| 3 | Passed | Hook permission frontend | `use-permission.ts` menyediakan can, canAny, hasRole, isSuperSystem | UI dapat membuat guard UX konsisten | `npm run build` |
| 4 | Passed | Demo seeder identity | Seeder demo identity tersedia | Seeder dapat dijalankan | `php artisan db:seed --class="App\\Modules\\Platform\\Identity\\Database\\Seeders\\IdentityDemoSeeder"` |
| 5 | Passed | UI manajemen akses | Page role/permission/user access; katalog read-only dan guard privilege | Admin mengelola akses existing tanpa CRUD akun/key bebas | [Work item UI](work-items/access-management/tasks.md): full test, lint/typecheck/build, browser fallback |
| 6 | Passed | Pengaman profil self-delete | Last-super, audit/delete atomic dan logout setelah sukses | Tidak dapat menghapus super-system terakhir; failure mempertahankan sesi | [Work item guard](work-items/account-deletion-guard/tasks.md): HTTP/rollback, browser dan frontend gate |
| 7 | Passed | Manajemen pengguna | Tambah/edit/status akun internal dan session version | Anti-escalation, last-super aktif, inactive/old-session ditolak | [Work item pengguna](work-items/user-management/tasks.md): 205 test/1213 assertion, UI/browser gates |

## QA Automated

- Backend focused: `php artisan test --filter=Identity`.
- Permission baseline: `php artisan test --filter=PermissionBaselineTest`.
- UI akses: `php artisan test --filter=AccessManagementTest`.
- Self-delete: `php artisan test --filter=AccountDeletionTest`.
- Akun internal: UserManagementTest dan UserStatusTest.
- Frontend: `npx eslint resources/js`, `npx tsc --noEmit` dan `npm run build`.
- Browser: Chrome DevTools MCP dahulu; bila unavailable catat BLOCKED dan
  jalankan fallback Identity sesuai [Quality](../../../QUALITY.md).

## Batas Berhenti

Module tidak mengambil ownership data customer, supplier, employee, atau
multi-company. Kebutuhan tersebut masuk module lain.
