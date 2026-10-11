# Work Item: UI Manajemen Akses

## Status

Completed

Implementasi, QA, provisioning permission aman dan dokumentasi selesai pada
11 Oktober 2026. Source/dokumen sudah dipush ke origin/main; hasil aktual dan
batas verifikasi tercatat pada [tasks](tasks.md).

## Scope Disetujui

Pada 11 Oktober 2026 user menyetujui pengelolaan role, memilih permission
terdaftar dari modul, dan assignment role pengguna existing. Tidak membuat key
permission bebas, CRUD akun/password, registrasi publik atau direct user grants.
Target: Platform/Identity; frontend resources/js/pages/platform/identity.

## Acceptance Dan Pengaman

- List Role/Permission/Akses Pengguna dengan pencarian/pagination server.
- Create/rename/delete role biasa, sync permission role, sync role user.
- Permission view/manage: platform.identity.view dan platform.identity.manage.
- Role super-system/super-admin reserved: tidak dibuat/rename/delete lewat CRUD.
- Permission role super-system tidak diedit; bypass tetap mengikuti ADR-010.
- Hanya super-system dapat mengubah assignment role super-system. Minimal satu
  pemegang super-system wajib tersisa; pengaman berjalan walaupun Gate bypass.
- Manager biasa tidak dapat memberikan permission/role melebihi aksesnya.
- Role yang masih dirujuk tidak dapat dihapus. Role memakai hard delete audited
  pada tabel package Spatie sebagai pengecualian soft delete: tidak memperkenalkan
  model/soft-delete lifecycle paralel yang dapat menyisakan akses package.
- Existing integer PK User/Role/Permission tetap mengikuti starter kit/package;
  tidak melakukan migrasi identifier dalam pekerjaan UI akses ini.
- Auth/verified/can per action, validation allowlist, audit atomic, cache refresh.
- Sonner, shortcut dropdown, command palette/sidebar, desktop/tablet, theme tetap.

## Boundary

Presentation memanggil Application. Port AccessRepository memisahkan query,
persistence dan adapter Spatie/audit. Tidak ada model privat module lain atau
event tanpa consumer. Dua helper Sync existing tetap menjadi wrapper kompatibel
tetapi memakai use case yang menegakkan authorization/guard baru.

## Seeder

Seeder permission khusus tidak mengubah role/user/password. Demo role
cashier/inventory-manager/inventory-viewer dipertimbangkan dan disediakan hanya
local/testing, idempotent dan tidak menimpa role existing atau membuat akun.
Prasyarat permission modul harus tersedia; tidak memakai seeder akun existing.

## Risiko Dan Non-Scope

Privilege delegation, last-super guard dan race assignment memerlukan locking
role bersama dengan urutan tetap. Direct permission existing tetap berlaku dan
tidak dimutasi. UI akun, deaktivasi user, force logout/session invalidation,
stock/FIFO, company, Old-docs dan perubahan theme di luar scope.
Guard last-super mencakup assignment role pada module ini; penghapusan akun
melalui profil starter kit existing masih di luar scope dan perlu hardening
terpisah. Jangan mengklaim seluruh jalur penghapusan akun sudah terlindungi.

## QA

Failing HTTP/action test dahulu, kemudian permission/guard/rollback/query/seeder
tests, regresi penuh, lint/typecheck/build, route/status dan QA browser
desktop/tablet. DevTools dicoba dulu; fallback disposable tersedia.
