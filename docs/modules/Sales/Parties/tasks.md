# Tasks: Pelanggan Dan Level Pelanggan

## Sebelum Mulai

- [x] AGENTS.md, docs/README.md, ARCHITECTURE, FOLDER-STRUCTURE dan QUALITY dibaca.
- [x] Scope Sales/Parties dan invariant level terpakai disetujui user.
- [x] Plan, specification dan acceptance dibuat sebelum coding.
- [x] Dampak route/schema/permission/audit/demo/soft delete dipetakan.
- [x] Tidak menyentuh stok/FIFO/pricing/payment/theme/Old-docs.

## Increment 1: Backend Dan Demo

- [x] Generate module melalui dry-run lalu generator dengan test.
  - Acceptance: route/database/provider/test berada di path canonical.
  - Verification: module:make --dry-run, scaffold test, route:list.
- [x] Buat schema ULID dan CRUD level/pelanggan atomic.
  - Acceptance: kode unique termasuk trashed, filter/pagination, lifecycle.
  - Verification: feature test dan disposable migration/up/down.
- [x] Terapkan invariant referensi, permission dan audit aman.
  - Acceptance: level terpakai tidak dapat dinonaktifkan/dihapus; inactive
    level tidak dapat dipilih; auth/super-system tidak melewati invariant.
  - Verification: HTTP/action tests, rollback dan audit actor/subject/privacy.
- [x] Tambahkan access/demo seeder relasional dan commit increment.
  - Acceptance: repeat tanpa perubahan user/history/grant, production ditolak.
  - Verification: seeder tests, focused suite, Pint, diff check.

## Increment 2: UI

- [x] Buat halaman level/pelanggan dan komponen lokal module.
  - Acceptance: CRUD/restore, filter/search/pagination, Sonner, shortcut.
  - Verification: lint/typecheck/build dan Inertia page tests.
- [x] Hubungkan menu dan palette dengan permission UX.
  - Acceptance: readonly hanya melihat, unauthorized tidak mendapat menu.
  - Verification: backend permission tests dan browser QA.
- [x] Verifikasi regresi shared workspace Inventory dan commit increment.
  - Acceptance: default route lama tidak berubah, theme tidak berubah.
  - Verification: full PHP suite, lint/typecheck/build.

## Increment 3: Penutupan QA

- [ ] Jalankan Chrome DevTools MCP dan workflow disposable browser.
  - Acceptance: desktop/sidebar collapsed/tablet portrait-landscape tidak
    overflow, CRUD persisten, readonly, shortcut/palette, dark, console bersih.
  - Verification: screenshot dan report lokal, PASS/BLOCKED per alat.
- [ ] Jalankan MySQL disposable dan pemeriksaan route/migration.
  - Acceptance: FK/unique/locking/invariant tepat; DB user tidak direset.
  - Verification: script QA, route:list, migrate:status.
- [ ] Lengkapi API/status/bukti, commit dan push.
  - Acceptance: checklist sesuai hasil aktual, artefak user tidak ikut commit.
  - Verification: git diff --check, git status, remote HEAD.

## Sesudah Coding

- [ ] Scope selesai dan seluruh gate relevan lulus.
- [ ] Dokumentasi, risiko dan hasil QA diperbarui.
- [ ] Tidak ada akun/grant/demo otomatis pada database user.

## Hasil Verifikasi

Increment 1 Passed: module:make dry-run/actual PASS; test HTTP RED 6/6 (404)
sebelum implementasi, seed RED 3/3 sebelum seed behavior. Setelah implementasi
`php artisan test --compact --filter=Parties`: 14 tests/114 assertions PASS.
Pint module/tests PASS; route:list --path=sales menampilkan 12 route. Migration
up/down SQLite PASS; MySQL/lock QA masuk Increment 3.
Increment 2 Passed: Inertia tests PASS; php artisan test --compact 254 tests,
1577 assertions PASS; npx tsc --noEmit PASS; npx eslint resources/js PASS;
npm run build PASS (2694 modules). Test halaman menggunakan withoutVite agar
tidak tergantung manifest lokal. Primitive textarea tanpa dependency baru.
Workspace shared tetap memakai prefix inventory default; Parties memakai
prefix eksplisit. Increment 3 In Progress.
