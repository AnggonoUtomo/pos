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

- [x] Implementasikan tab/list/search/pagination.
  - Acceptance: role/permission/user props allowlist; tanpa data credential;
    empty/loading/filter state tersedia.
  - Verification: Inertia test, typecheck/lint/build.
- [x] Implementasikan CRUD role, matrix permission dan assignment user.
  - Acceptance: checkbox/dialog/konfirmasi, Sonner, error field, processing,
    permission UX dan pengaman reserved/last-super.
  - Verification: HTTP regressions dan browser workflow.
- [x] Aktifkan shortcut dropdown, sidebar dan palette nyata; commit UI.
  - Acceptance: navigasi berfungsi, readonly tidak mendapat tombol mutation.
  - Verification: lint/typecheck/build dan save point.

## Increment 3: QA Dan Penutupan

- [x] QA desktop/tablet expanded/collapsed, dark/light, keyboard dan console.
  - Acceptance: CRUD/sync/guard terverifikasi tanpa overflow baru atau error.
  - Verification: DevTools MCP atau BLOCKED dengan fallback disposable.
- [x] Full gate, seed permission aman lokal, docs/API/module index aktual.
  - Acceptance: tidak mengubah credential/assignment user lokal.
  - Verification: full test, format/lint/typecheck/build, route/status/diff.
- [x] Review boundary input dan tambahkan regresi identifier route overflow.
  - Acceptance: ID di luar integer PHP positif ditolak 404 tanpa mutation/audit.
  - Verification: RED reproduksi 500 lalu GREEN seluruh empat mutation route.
- [x] Commit dan push seluruh work item selesai.
  - Acceptance: remote sinkron; artefak user tetap lokal.
  - Verification: staged diff dan status remote.

## Sesudah Coding

- [x] Scope dan gate selesai, hasil command aktual dicatat.
- [x] Risiko terbuka/yang tidak disentuh dijelaskan.
- [x] Checklist diisi sesudah verifikasi, bukan sebelum hasil tersedia.

## Hasil Verifikasi

Increment 1: 9 test RED sebelum route/use case tersedia. GREEN access tests dan
helper existing 15 test/113 assertion, mencakup reserved/last-super/escalation,
audit failure, invalid input, preservation, pagination dan keamanan demo. Full
suite pada save point backend: 168 test/981 assertion PASS.
Pint, route middleware (6 route nyata), dan diff PASS. UI index belum dibangun
pada increment backend; Inertia props test memakai withoutVite/component check
sementara, yang diaktifkan kembali pada Increment 2. Tidak seed akun/data lokal.

Increment 2: strict Inertia component check aktif. Focused 18 test/123 assertion
PASS setelah tambahan preservation guard lain/direct grant, rollback assignment,
dan batas katalog. Full suite 171 test/991 assertion PASS. Typecheck, lint source
aktif, production build, Pint, 6 route beserta middleware dan migrate:status PASS
(semua migration Ran; tidak ada schema baru). Save point backend: `3a0cccb`.

Chrome DevTools MCP: BLOCKED karena tidak ada tool DevTools yang diekspos sesi.
Fallback `scripts/qa/identity-browser.mjs`: PASS, 8 screenshot desktop/tablet
expanded/collapsed, dialog matrix, light/dark/clean; console error/warning kosong.
CRUD role, permission dan assignment persisted sesudah reload, guard role
terpakai/last-super, readonly/delegated manager, search/empty, shortcut dan palette
PASS. Artefak lokal: `storage/framework/testing/identity-browser-dbe6eb4966ff6688/`.
Skenario QA memakai SQLite disposable dan akun random runtime, bukan MySQL user.
Percobaan pertama timeout login; pada rerun CSS font eksternal Bunny distub agar
QA tidak bergantung jaringan. Font fallback dipakai; ketersediaan CDN font tidak
termasuk klaim PASS. Screenshot desktop dan dialog tablet diperiksa visual.

Increment 3: MySQL migrate:status seluruh migration Ran; tanpa schema baru.
IdentityPermissionsSeeder dijalankan di dalam transaction lokal dengan snapshot
akun/role/pivot sebelum-sesudah. Permission bertambah 10 ke 12; dua key Identity
terdaftar. Data akun/password, role, direct grants dan pivot assignment tetap
identik (hanya boolean preservation dicetak). Seeder akun/demo tidak dijalankan.
Save point UI: `615d6f5`. Docs module, API, module index dan Quality diperbarui.
Tidak menyentuh Old-docs, SampleUI, theme/CSS, stok/FIFO atau CRUD akun.
Risiko terbuka: last-super pada delete akun profil, uji konkurensi MySQL dan
katalog remote >1000 berada di luar scope; tidak diklaim sudah terverifikasi.

Review penutupan menemukan overflow route identifier menyebabkan TypeError 500.
Test reproduksi RED kemudian parsing FILTER_VALIDATE_INT di Presentation
menghasilkan 404 sebelum use case. Focused akhir 19 test/128 assertion dan full
suite akhir 172 test/996 assertion PASS; Pint PASS. Save point fix: `7722840`.
Build frontend tetap artefak source UI yang sudah lulus; fix hanya PHP/tes.
Typecheck dan lint source aktif akhir PASS. Pemeriksaan Laragon
`http://pos.test/login`: HTTP 200, memakai asset production build dan tidak
memuat Vite development client. Diff whitespace dan 9 link relatif dokumen
yang diubah PASS. Review correctness, boundary, authorization, query pagination
dan performance dilakukan; tidak ada dependency/branch baru.

Delivery: source/backend/UI/fix dan dokumentasi dipush melalui save point
`49a9eba` ke origin/main; status main sinkron. Checklist delivery ditutup
sesudah push berhasil. SampleUI dan empat file LSP user tetap untracked lokal;
tidak ikut staged diff/push. Catatan penutupan ini dikirim sebagai commit docs
terpisah; verifikasi final remote dilakukan sesudah push catatan tersebut.
