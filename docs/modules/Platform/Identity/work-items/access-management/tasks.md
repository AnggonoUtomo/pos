# Tasks: UI Manajemen Akses

## Sebelum Mulai

- [x] AGENTS, README, arsitektur, struktur, Quality dan dokumen Identity dibaca.
- [x] Target Platform/Identity diinformasikan; scope dikonfirmasi user.
- [x] No CRUD akun/key permission bebas; Old-docs/theme/user artefak tidak disentuh.
- [x] Risiko privilege, last-super, direct grants dan soft-delete exception dicatat.

## Increment 1: Backend

- [x] Tulis failing test HTTP/action dan seeder.
  - Acceptance: permission matrix, input, audit/rollback, last-super/reserved,
    escalation dan preservation diuji sebelum implementasi.
  - Verification: RED kemudian GREEN.
- [x] Implementasikan port/adapter/use case dan HTTP boundary.
  - Acceptance: Presentation tidak melakukan mutation; locking/audit atomic;
    wrapper Sync kompatibel dan authorization ditegakkan.
  - Verification: focused tests dan review layer/security.
- [x] Tambahkan permission/demo role seeder aman.
  - Acceptance: tanpa credential/grant akun; demo local/testing; repeat aman.
  - Verification: seeder tests, bukan seeder akun existing.
- [x] Verifikasi backend dan commit.
  - Acceptance: full regression, Pint, route/status dan diff lulus.
  - Verification: hasil aktual dan save point dicatat.

## Increment 2: UI

- [ ] Implementasikan tab/list/search/pagination.
  - Acceptance: role/permission/user props allowlist; tanpa data credential;
    empty/loading/filter state tersedia.
  - Verification: Inertia test, typecheck/lint/build.
- [ ] Implementasikan CRUD role, matrix permission dan assignment user.
  - Acceptance: checkbox/dialog/konfirmasi, Sonner, error field, processing,
    permission UX dan pengaman reserved/last-super.
  - Verification: HTTP regressions dan browser workflow.
- [ ] Aktifkan shortcut dropdown, sidebar dan palette nyata; commit UI.
  - Acceptance: navigasi berfungsi, readonly tidak mendapat tombol mutation.
  - Verification: lint/typecheck/build dan save point.

## Increment 3: QA Dan Penutupan

- [ ] QA desktop/tablet expanded/collapsed, dark/light, keyboard dan console.
  - Acceptance: CRUD/sync/guard terverifikasi tanpa overflow baru atau error.
  - Verification: DevTools MCP atau BLOCKED dengan fallback disposable.
- [ ] Full gate, seed permission aman lokal, docs/API/module index aktual.
  - Acceptance: tidak mengubah credential/assignment user lokal.
  - Verification: full test, format/lint/typecheck/build, route/status/diff.
- [ ] Commit dan push seluruh work item selesai.
  - Acceptance: remote sinkron; artefak user tetap lokal.
  - Verification: staged diff dan status remote.

## Sesudah Coding

- [ ] Scope dan gate selesai, hasil command aktual dicatat.
- [ ] Risiko terbuka/yang tidak disentuh dijelaskan.
- [ ] Checklist diisi sesudah verifikasi, bukan sebelum hasil tersedia.

## Hasil Verifikasi

Increment 1: 9 test RED sebelum route/use case tersedia. GREEN access tests dan
helper existing 15 test/113 assertion, mencakup reserved/last-super/escalation,
audit failure, invalid input, preservation, pagination et demo safety. Full
suite terakhir setelah perluasan test: hasil command dicatat pada penutupan.
Pint, route middleware (6 route nyata), dan diff PASS. UI index belum dibangun
pada increment backend; Inertia props test memakai withoutVite/component check
sementara, yang diaktifkan kembali pada Increment 2. Tidak seed akun/data lokal.
