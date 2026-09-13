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
| 5 | Planned | UI manajemen akses | Page role/permission/user access | Admin bisa mengelola akses dari UI | Ditentukan pada work item UI |

## QA Automated

- Backend focused: `php artisan test --filter=Identity`.
- Permission baseline: `php artisan test --filter=PermissionBaselineTest`.
- Frontend: `npm run lint` dan `npm run build` bila UI/hook berubah.
- Browser: Chrome DevTools MCP bila UI identity dibuat.

## Batas Berhenti

Module tidak mengambil ownership data customer, supplier, employee, atau
multi-company. Kebutuhan tersebut masuk module lain.
