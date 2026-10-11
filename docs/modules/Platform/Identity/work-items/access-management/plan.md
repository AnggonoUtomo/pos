# Plan: UI Manajemen Akses

| Increment | Status | Scope | Acceptance | Verifikasi |
| --- | --- | --- | --- | --- |
| 1 | Passed | Rencana dan backend | Public HTTP aman; port/adapter; audit; guard privilege/last-super; seed aman | 15 focused test/113 assertion; Pint, route, full regression |
| 2 | Passed | UI Inertia | Tab role/permission/user; form/matrix; Sonner; shortcut; navigasi | HTTP props, lint/typecheck/build dan fallback browser PASS |
| 3 | In Progress | QA dan penutupan | Desktop/tablet, readonly, lifecycle, guard/error, documentation aktual | Browser/full test PASS; seed aman dan dokumentasi final pending |

## Urutan

1. Dokumentasi scope dan checklist sebelum coding.
2. Test role CRUD/sync dan user assignment termasuk escalation, reserved role,
   last super, audit failure, input invalid, guest, readonly dan manage.
3. Port/persistence adapter, use case orchestration, wrappers Sync existing,
   request/controller/routes, serta permission/demo seeder tanpa akun.
4. Commit backend setelah gate lulus.
5. UI tab/layout/filter; permission checkbox per prefix dan user role editor;
   shortcut dropdown dan sidebar/palette nyata. Commit sesudah verifikasi.
6. QA browser pada SQLite disposable; tidak mengubah assignment user lokal.
7. Seed permission aman saja pada lokal, dokumentasi final, commit dan push.

## Contract Awal

GET /platform/identity?tab=roles|permissions|users&search=&page=1 mengembalikan
Inertia. POST /platform/identity/roles; PUT/DELETE roles/{id}; PUT
roles/{id}/permissions; PUT users/{id}/roles. ID existing integer positif.
Permission list wajib array distinct nama terdaftar guard web; role list serupa.
View memakai platform.identity.view; semua mutation platform.identity.manage.
CRUD/sync sukses JSON id atau redirect web; validation JSON 422/session errors.
Data list 25/page; options/catalog maksimum 1000, overflow ditolak eksplisit
agar pilihan tidak dipotong diam-diam. Tidak mengekspos credential user.
