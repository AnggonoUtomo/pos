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

- [ ] Tambah/edit/status dialog dan badge pada tab pengguna.
  - Acceptance: per-operation permission, role guard, processing/error/empty,
    password cleared, Sonner, shortcut dropdown dan keyboard tersedia.
  - Verification: lint/typecheck/build.
- [ ] Browser QA desktop/tablet, light/dark dan privilege/auth lifecycle.
  - Acceptance: workflow nyata disposable, screenshots, console tanpa error baru;
    mencoba Chrome DevTools lebih dulu dan fallback berstatus terpisah.
  - Verification: hasil tool/script dan pemeriksaan visual.
- [ ] Update module/API, full tests, review dan selective commit/push.
  - Acceptance: status berbasis realita, tidak mengubah credential/role existing,
    risiko MySQL concurrency tertulis; artefak user tidak ikut commit.
  - Verification: full tests, route/status, diff/staged diff, remote status.

## Sesudah Coding

- [ ] Catat hasil aktual, migration/seeder dan risiko.
- [ ] Checklist/status diisi setelah verification lulus.
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
