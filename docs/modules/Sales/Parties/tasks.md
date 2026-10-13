# Tasks: Pelanggan Dan Level Pelanggan

Status: Done. Increment 1/2/3 Passed; ditutup 13 Oktober 2026.

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

- [x] Jalankan Chrome DevTools MCP dan workflow disposable browser.
  - Acceptance: desktop/sidebar collapsed/tablet portrait-landscape tidak
    overflow, CRUD persisten, readonly, shortcut/palette, dark, console bersih.
  - Verification: screenshot dan report lokal, PASS/BLOCKED per alat.
- [x] Jalankan MySQL disposable dan pemeriksaan route/migration.
  - Acceptance: FK/unique/locking/invariant tepat; DB user tidak direset.
  - Verification: script QA, route:list, migrate:status.
- [x] Lengkapi API/status/bukti, commit dan push.
  - Acceptance: checklist sesuai hasil aktual, artefak user tidak ikut commit.
  - Verification: git diff --check, git status, remote HEAD.

## Sesudah Coding

- [x] Scope selesai dan seluruh gate relevan lulus.
- [x] Dokumentasi, risiko dan hasil QA diperbarui.
- [x] Tidak ada akun/grant/demo otomatis pada database user.

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
prefix eksplisit. Increment 3 Passed.

Review penutupan 13 Oktober 2026: regresi MySQL RED membuktikan dirty check
snapshot lama melewatkan deactivate. Adapter mutation kini memakai current
locking read; script parties-mysql seluruh pemeriksaan PASS setelah fix.
Test edit kontak RED membuktikan audit hilang, lalu PASS setelah audit
changed_fields tanpa nilai kontak. Focused Parties: 17 tests/166 assertions
PASS, Pint dan diff check PASS. Review independen tidak selesai akibat limit
layanan; review lokal dan proof runtime digunakan, bukan klaim approval agent.

## Bukti Penutupan

| Pemeriksaan | Hasil | Catatan |
| --- | --- | --- |
| php artisan test --compact | PASS | 255 tests, 1587 assertions |
| php artisan test --compact --filter=Parties | PASS | 17 tests, 166 assertions |
| Pint --test module/tests/QA PHP | PASS | Scope terarah, bukan arsip |
| npx eslint resources/js | PASS | Nonmutating |
| npx tsc --noEmit | PASS | Tidak ada error tipe |
| npm run build | PASS | 2694 modules; production asset tersedia |
| node --check scripts/qa/parties-browser.mjs | PASS | Sintaks script |
| php scripts/qa/parties-mysql.php | PASS | Dua koneksi REPEATABLE READ, snapshot stale, mutation current read, unique, FK, cleanup |
| node scripts/qa/parties-browser.mjs | PASS | CRUD/persist/reload, level terpakai/trashed, filter, empty, readonly/outsider, shortcut/palette, Inventory regression, dark |
| Chrome DevTools MCP | PARTIAL/BLOCKED | Sebelum interupsi smoke redirect ke login PASS dan console bersih; authenticated CRUD tidak diuji. Saat resume tool tidak tersedia, workflow penuh memakai Playwright disposable |
| php artisan route:list --path=sales --except-vendor | PASS | 12 route |
| php scripts/qa/apply-parties-local.php | PASS | Migration additive + permission catalog; fingerprint existing identik, tanpa akun/grant/demo/reset |
| php artisan migrate:status | PASS | Customer masters Ran batch 8 |
| git diff --check | PASS | Tidak ada whitespace error |

Artefak lokal ignored: storage/framework/testing/parties-browser-a75dd277d0ad15e0
(report dan 10 screenshot). Desktop 1440x1000 expanded/collapsed; tablet
1024x768/768x1024; dialog di dalam viewport, tidak ada overflow halaman,
console/page errors kosong. Screenshot form portrait dan desktop ditinjau.
Font CDN distub, sehingga tidak mengklaim verifikasi jaringan font eksternal.
QA mobile tetap ditunda sesuai scope user.

## Batas Dan Risiko

- Pricing per barang/satuan dan transaksi POS belum diimplementasikan.
- Level choices dibatasi 1000; remote search memerlukan pekerjaan terpisah
  ketika katalog level melampaui batas tersebut.
- QA MySQL menguji interleaving terarah, bukan load/race semua skenario.
- Demo CCTV/kelistrikan dan Parties tersedia, tetapi tidak di-seed otomatis ke
  MySQL pengguna. Demo lama/master/history existing tidak dihapus.
- Old-docs, SampleUI, artefak LSP user dan theme tidak diubah/diikutkan commit.
