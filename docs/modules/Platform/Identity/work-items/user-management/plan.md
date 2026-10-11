# Plan: Manajemen Pengguna

## Urutan

1. Catat scope dan checklist; save point dokumen.
2. Tulis failing HTTP test tambah/edit, privilege, validasi dan audit rollback.
   Perluas port AccessRepository/adapter, action ManageUsers dan inbound
   UserController/UserRequest. Create dengan role memakai ManageAccess existing
   dalam transaction yang sama; tanpa role tidak membutuhkan identity.manage.
3. Tambah migration is_active/access_version di module; guard sesi sebagai
   inbound middleware, kondisi active saat auth. Hitungan last-super active,
   pengaman self-deactivate, reaktivasi tanpa mengembalikan sesi lama.
4. Dialog local page, badge/status/permission UX, shortcut, Sonner; QA fixture
   disposable dan Chrome DevTools. Dokumentasikan route/API serta final gate.

## Verifikasi

Focused Identity/auth/profile tests setiap increment; Pint, route:list dan
diff. Migration dicoba pada SQLite disposable lewat RefreshDatabase/fixture,
status MySQL diperiksa; migrate incremental tanpa reset akun. UI lint/typecheck,
build, desktop/tablet expanded/collapsed, light/dark, form invalid/empty,
readonly/delegated privilege, login/disable/reactivate, console dan screenshot.
Full suite sebelum selesai; selective commit per increment, push setelah semua
scope selesai. SampleUI dan artefak LSP tetap lokal.

## Referensi Implementasi

Login memakai additional credential conditions dan invalidasi sesi sesuai
[Laravel Authentication](https://laravel.com/docs/12.x/authentication).
Source framework/package terpasang tetap diperiksa untuk behavior aktual.
Tidak membuat contract/event publik lintas module tanpa consumer.
