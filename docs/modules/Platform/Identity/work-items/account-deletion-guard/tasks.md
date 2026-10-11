# Tasks: Pengaman Penghapusan Akun

## Sebelum Mulai

- [x] AGENTS, README, Architecture, Folder Structure dan dokumen Identity dibaca.
- [x] ProfileController, route, User, dialog serta test existing diperiksa.
- [x] User menyetujui scope; target Platform/Identity diinformasikan.
- [x] Scope tanpa CRUD akun/schema/key permission baru dan tanpa akun MySQL nyata.

## Increment 1: Scope

- [x] Susun acceptance, boundary dan rencana QA.
  - Acceptance: lock bersama, fresh roles, last-super, audit atomic dan lifecycle
    sesi tertulis; seeder skip/non-scope eksplisit.
  - Verification: review dokumen, git diff --check dan commit save point.

## Increment 2: Backend

- [x] Tambahkan failing HTTP tests.
  - Acceptance: last-super, ordinary/super success, password, guest, stale auth,
    audit/delete failure dan veto delete diuji tanpa credential di output.
  - Verification: hasil RED sebelum perubahan implementation.
- [x] Implementasikan action, port/adapter dan controller.
  - Acceptance: lock role lalu user; last-super tidak di policy saja; audit/delete
    atomic; target dari auth; logout/invalidate hanya setelah action berhasil.
  - Verification: focused GREEN, regresi profil/Identity, review dependency.
- [x] Verifikasi dan commit backend.
  - Acceptance: test/Pint/route/diff lulus dan tidak ada schema baru.
  - Verification: hasil command aktual serta commit dicatat.

## Increment 3: UI Dan Penutupan

- [ ] Tampilkan error account/password, Sonner dan processing dialog existing.
  - Acceptance: blok last-super tidak tampil seperti sukses; password dibersihkan;
    Ctrl/Cmd+Enter dan Escape tersedia; konfirmasi tetap eksplisit.
  - Verification: lint/typecheck/build serta browser workflow.
- [ ] QA desktop/tablet dan console pada database disposable.
  - Acceptance: last-super masih login setelah penolakan; ordinary deletion
    logout; password salah tidak memutasi; tidak ada overflow/error baru.
  - Verification: DevTools atau BLOCKED dengan fallback dan screenshot.
- [ ] Full gate, review dan dokumentasi aktual.
  - Acceptance: module/API tidak lagi menyatakan profil tanpa guard; risiko
    session lain/referensi transaksi/MySQL concurrency tetap jelas.
  - Verification: focused/full test, Pint, route, lint/typecheck/build, diff/link.
- [ ] Commit/push setelah seluruh scope selesai.
  - Acceptance: remote sinkron, artefak user tidak ikut commit.
  - Verification: staged diff, push dan status remote.

## Sesudah Coding

- [ ] Hasil aktual dan seeder skip dicatat.
- [ ] Checklist diisi sesudah verifikasi; status increment/work item aktual.
- [ ] Scope, risiko dan bagian yang tidak disentuh dilaporkan.

## Hasil Aktual

Scope/checklist dibuat sebelum coding dan disimpan melalui `9d50ede`.
RED: 7 gagal/2 lulus, menunjukkan last-super dihapus, audit tidak ada, pivot
tidak rollback dan logout/delete veto tidak terlindungi. Assertion helper/session
test disesuaikan dengan API Laravel existing; bukan perubahan runtime.
GREEN: AccountDeletionTest + ProfileUpdateTest + AccessManagementTest + helper
akses existing 33 test/208 assertion PASS. Pint dan route inspection PASS.
Route settings/profile GET/PATCH/DELETE dan middleware auth tetap, tanpa schema
atau seeder baru. Tidak ada penghapusan akun MySQL nyata.
Full regression: 181 test/1055 assertion PASS; diff whitespace PASS.
