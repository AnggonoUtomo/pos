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

- [ ] Tulis failing feature tests.
  - Acceptance: permission per action, duplicate email, role grant, spoof payload,
    credential allowlist dan rollback audit diuji sebelum implementation.
  - Verification: RED focused test.
- [ ] Implementasikan action/port/adapter/request/controller/route dan seed aman.
  - Acceptance: hanya validated payload; create password hashed, profil tanpa
    reset password, role assignment memakai pengaman existing dan atomic.
  - Verification: GREEN, regresi Identity/profil, route:list, Pint, diff.
- [ ] Commit increment setelah verifikasi.

## Increment 3: Status Dan Auth

- [ ] Migration module dan default existing aktif.
  - Acceptance: akun/password/pivot existing tidak berubah; version default 0.
  - Verification: migrate disposable, focused schema test, migrate:status.
- [ ] Pengaman last-super aktif, self-deactivation dan session version.
  - Acceptance: inactive login/remember/session ditolak, reaktivasi tidak
    memulihkan sesi lama; audit failure rollback status/token/version.
  - Verification: RED/GREEN auth/Identity/profile tests, route/middleware review.
- [ ] Commit increment setelah verifikasi.

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

Belum ada hasil coding. Increment 1: scope/dokumen diperiksa sebelum coding.
