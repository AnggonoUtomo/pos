# Tasks: Manajemen Pengguna

## Sebelum Mulai

- [x] AGENTS, README, arsitektur/struktur, workflow/quality dan Identity dibaca.
- [x] Source auth, access management, profil, seeder dan QA fixture diperiksa.
- [x] Target Platform/Identity diinformasikan dan scope interview disetujui.
- [x] Git diperiksa; Old-docs/theme/artefak user di luar scope.

## Increment 1: Scope

- [x] Susun PRD, boundary, permission, session dan rencana QA.
  - Acceptance: password admin, tanpa reset/hapus admin, anti-escalation dan
    active last-super eksplisit; seeder demo skip diberi alasan.
  - Verification: review manual, git diff --check, selective commit.

## Increment 2: Tambah Dan Edit

- [x] Tulis failing feature tests.
  - Acceptance: permission per action, duplicate email, role grant, spoof payload,
    credential allowlist dan rollback audit diuji sebelum implementation.
  - Verification: RED focused test.
- [x] Implementasikan action/port/adapter/request/controller/route dan seed aman.
  - Acceptance: hanya validated payload; create password hashed, profil tanpa
    reset password, role assignment memakai pengaman existing dan atomic.
  - Verification: GREEN, regresi Identity/profil, route:list, Pint, diff.
- [x] Commit increment setelah verifikasi.

## Increment 3: Status Dan Auth

- [x] Migration module dan default existing aktif.
  - Acceptance: akun/password/pivot existing tidak berubah; version default 0.
  - Verification: migrate disposable, focused schema test, migrate:status.
- [x] Pengaman last-super aktif, self-deactivation dan session version.
  - Acceptance: inactive login/remember/session ditolak, reaktivasi tidak
    memulihkan sesi lama; audit failure rollback status/token/version.
  - Verification: RED/GREEN auth/Identity/profile tests, route/middleware review.
- [x] Commit increment setelah verifikasi.

## Increment 4: UI Dan QA

- [x] Tutup edge case hasil review dengan regression test.
  - Acceptance: count tanpa role super tidak gagal, recall stale tidak dinaikkan
    ke version baru, email berubah mencabut remember token, password multibyte
    tidak terpotong oleh bcrypt.
  - Verification: RED 4 gagal, GREEN 4 test/18 assertion, full regression.
- [x] Tambah/edit/status dialog dan badge pada tab pengguna.
  - Acceptance: per-operation permission, role guard, processing/error/empty,
    password cleared, Sonner, shortcut dropdown dan keyboard tersedia.
  - Verification: lint/typecheck/build.
- [x] Browser QA desktop/tablet, light/dark dan privilege/auth lifecycle.
  - Acceptance: workflow nyata disposable, screenshots, console tanpa error baru;
    mencoba Chrome DevTools lebih dulu dan fallback berstatus terpisah.
  - Verification: hasil tool/script dan pemeriksaan visual.
- [x] Update module/API, full tests dan review.
  - Acceptance: status berbasis realita, tidak mengubah credential/role existing,
    risiko MySQL concurrency tertulis; artefak user tidak ikut commit.
  - Verification: full tests, route/status, diff/staged diff.
- [ ] Selective commit/push setelah scope selesai.
  - Acceptance: tidak menyertakan artefak user, remote sesuai commit delivery.
  - Verification: staged diff, push dan remote status sesudah command berhasil.

## Sesudah Coding

- [x] Catat hasil aktual, migration/seeder dan risiko.
- [x] Checklist/status diisi setelah verification lulus.
- [ ] Handoff ringkas beserta bagian yang tidak disentuh.

## Hasil Aktual

Increment 1: scope/dokumen diperiksa sebelum coding; commit f10d429.
Increment 2: RED 9 gagal (endpoint/action/seed belum ada), GREEN 9 test/56
assertion PASS; regresi Identity + profil 51 test/305 assertion PASS. Pint dan
route:list (8 route dengan permission per action) serta git diff --check PASS.
Create/edit hanya menerima field allowlist, normalized email case-insensitive,
password hashed dan audit tanpa PII/credential. Assignment role atomic memakai
ManageAccess; operator tidak dapat takeover super/higher privilege/guard lain.
Seeder permission-only diperluas; belum dijalankan terhadap MySQL lokal.

Increment 3: RED 10 gagal setelah helper test diperbaiki (nama status reserved
PHPUnit); GREEN UserStatusTest 12 test/79 assertion PASS. Assertion rollback
membaca snapshot fresh agar default DB ikut dibandingkan, tanpa credential di
output. Regresi Identity/auth/profil 82 test/420 assertion PASS; Pint dan
route inspection 9 endpoint PASS. Migration down/up SQLite menjaga akun existing.
Migration MySQL local incremental + permission-only seed PASS; snapshot internal
akun/password/role/pivot sebelum/sesudah identik (credential tidak ditampilkan).
migrate:status 10 migration Ran. Tidak menjalankan seeder akun/demo credential.
Actor inactive ditolak Application juga; middleware sebelum Inertia membatalkan
session/version lama, login active dan remember token dicabut saat deaktivasi.

Review: count memakai whereHas agar zero role valid; fresh version dibandingkan
dengan user hasil recall sebelum sesi diinisialisasi; remember token diputar
saat email berubah. Password minimal 12 karakter/maksimal 72 byte divalidasi
sebelum hashing (bukan truncation multibyte). RED 4 gagal lalu GREEN 4 test/18
assertion PASS. Full suite terbaru 205 test/1213 assertion PASS; Pint PASS.
UserStatusTest juga menguji cookie recall lama ditolak dan recall baru sesudah
aktivasi diterima, tanpa token/credential di output. Bukti concurrency interleaving
berupa test state terkontrol, bukan load test MySQL.

Increment 4: npx eslint resources/js, npx tsc --noEmit, npm run build (2683
module, Vite 6.4.3) dan node --check scripts/qa/identity-browser.mjs PASS.
Resolved middleware dengan HTTP Kernel menunjukkan EnsureActiveAccount sebelum
Inertia/Authorize. Route inspection 9 endpoint dan migration/status PASS.
Review independent source: tiga Required tertutup; residual MySQL concurrency.

Chrome DevTools MCP smoke PASS: context pos-users-qa terisolasi, guest route
Identity redirect login, desktop/tablet, asset build 200 dan console kosong.
Tab QA ditutup; tidak memakai credential akun MySQL. Workflow authenticated
memakai script Playwright/Chromium existing pada SQLite disposable, PASS dua
kali (terakhir sesudah perbaikan backend). Semua workflow create/edit/role,
invalid email/confirmation, password clearing, deactivate/reactivate, badge,
inactive login dan old-session rejection/fresh login berjalan; permission UX
operator/users-only/readonly/delegated, palette, shortcut serta regresi akses
dan self-delete PASS. Console error/warning kosong; server/browser ditutup.
17 screenshot pada storage/framework/testing/identity-browser-10680acbe1570df2/;
tablet portrait/landscape dan dialog dark diperiksa visual tanpa overlap.
Font Bunny distub CSS sehingga bukan pengujian CDN/font eksternal.

Dokumen module/API/module index diselaraskan; link relatif dan whitespace
diperiksa sebelum delivery. Theme, Old-docs, SampleUI, artefak LSP dan akun/
credential/assignment MySQL tidak diubah. Schema baru dan permission registration
diterapkan tanpa fresh/reset; tidak membuat demo akun statis/seeder destruktif.
Gap starter profil update/checkbox remember UI dicatat tanpa diperbaiki di luar
scope; remember backend diverifikasi pada feature test. Risiko load/konkurensi
MySQL, ownership transaksi dan cleanup fisik sesi masih terbuka.
