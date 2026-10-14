# Tasks: Fondasi Master Finance

## Sebelum Mulai

- [x] AGENTS/README/arsitektur/struktur dan pola Parties dibaca.
- [x] Target Finance/Payments, scope/non-scope dan relasi diinformasikan.
- [x] Generator dry-run dan dokumen sebelum coding tersedia.
- [x] Schema/route/permission/audit privacy/demo/soft delete dicakup.
- [x] Saldo/payment/POS posting/FIFO/tax/discount bukan scope.
- [x] Test, lint/TS/build, DevTools/fallback desktop/tablet, MySQL direncanakan.

## Increment 1: Fondasi Backend

- [x] Schema ULID/unique/FK dan entity master soft delete.
  - Acceptance: account/metode milik module, kode terhapus tetap reserved.
  - Verification: migration up/down dan persistence tests.
- [x] DTO/use case/port/adapter dan kompatibilitas Domain.
  - Acceptance: account aktif/type cocok, reference termasuk trashed, current
    lock, atomic audit/no-op, super-system tidak melewati invariant.
  - Verification: focused action/privacy/rollback tests dan Pint.
- [x] Seeder permission/demo dan commit.
  - Acceptance: idempotent/preserve edit/trashed, local/testing only, no grant.
  - Verification: seeder tests termasuk rollback prerequisite.

## Increment 2: HTTP

- [x] Route/controller/request/query dua master dan filter/choices.
  - Acceptance: auth/403/422/404, CSRF, payload whitelist, unique teredaksi,
    pagination25 dan account options aktif max1000.
  - Verification: HTTP permission/validation/CRUD/restore/filter tests.
- [x] Route/migration regression dan commit.
  - Acceptance: route/migration module nyata, no runtime gap pada scaffold.
  - Verification: route:list, migration disposable/status dan focused tests.

## Increment 3: UI Admin

- [x] Form/tabel dua halaman modular, navigasi/palette dan permission UX.
  - Acceptance: shadcn/Sonner, metadata bank conditional/masked, CRUD/restore,
    readonly, empty/error/loading, shortcut dropdown, type/account pilihan jelas.
  - Verification: lint/TS/build dan browser desktop/tablet Light/Dark.
- [x] Guard/error/shortcut dan commit.
  - Acceptance: account terpakai ditolak, edit/reload relasi tersimpan,
    sidebar expanded/collapsed, tanpa perubahan theme/POS payment.
  - Verification: QA automated dan screenshot/report ignored.

## Increment 4: Penutupan

- [x] Full gates dan QA MySQL current read/FK.
  - Acceptance: semua gates PASS, tidak mengklaim SQLite sebagai row-lock proof.
  - Verification: PHP suite, Pint, lint/TS/build, route/migration dan MySQL QA.
- [x] Aktivasi migration/demo lokal additive dan docs/status.
  - Acceptance: data user lama/account/grant/stock/ledger identik, no reset.
  - Verification: fingerprints sebelum/sesudah dan command dicatat.
- [x] Commit/push dan cocokkan remote HEAD.
  - Acceptance: hanya file scope terkirim; SampleUI/LSP/Old-docs tidak disentuh.
  - Verification: staged scope/check, push/ls-remote dan divergence.

## Hasil Verifikasi

Increment 1: RED 9 tests sebelum action/migration tersedia. Setelah implementasi,
PaymentsFoundationTest 9 tests/44 assertions PASS; Pint PASS. Audit/no-op,
metadata-only redacted, guard termasuk trashed, invalid relation/type, restore,
rollback persistence, demo idempotent/preservation/production refusal dan
prerequisite rollback serta migration up/down terverifikasi.

Increment 2: HTTP RED 404 sebelum endpoint tersedia, lalu Foundation/HTTP
15 tests/105 assertions PASS; 12 route finance nyata; migration up/down pada
SQLite disposable PASS. Request whitelist, metadata conditional/leading zeros,
permission/guest/super-system, CRUD/restore, kode terhapus reserved, filter dan
error guard terverifikasi. PUT memerlukan is_active eksplisit. MySQL/local
migration status menunggu Increment 4; belum dinyatakan sudah diterapkan.

Penguatan HTTP sesudah QA UI: pesan code.unique dua form disamakan dengan
error conflict Indonesia. Test tambahan mencakup delapan mutation viewer 403,
Inertia props/choices aktif tanpa metadata rekening, kode metode terhapus dan
akun tidak ditemukan. Suite keseluruhan 300 tests/1912 assertions PASS;
Pint scope Finance PASS. Pemisahan commit menjaga perbaikan backend terfokus.

Increment 3: TypeScript, ESLint resources/js dan Vite build 2704 modules PASS.
DevTools MCP BLOCKED: discovery tool sesi tidak menyediakan namespace DevTools.
Fallback Playwright existing PASS dengan database/profile/credential disposable;
report storage/framework/testing/payments-browser-0b70b4eaaf091df0/report.json
dan screenshot lokal ignored. CRUD/restore dua master, relasi reload/jenis,
metadata conditional/leading zero/masked, guard account termasuk metode terhapus,
duplicate error, filter/search/empty, shortcut/palette, viewer/outsider dan
console kosong terverifikasi. Desktop1440x1000, tablet1024x768/768x1024,
Light/Dark dan sidebar expanded/collapsed; tidak QA mobile. Screenshot diperiksa
visual, dialog/body tanpa overflow; tabel tablet scroll dalam panel agar kode
tidak dimampatkan. Theme dan POS payment tidak diubah.

Increment 4: `php artisan test --compact` 300 tests/1912 assertions PASS,
termasuk 18 tests/164 assertions Finance. Pint scope module/test/QA, ESLint
resources/js, TypeScript noEmit, Prettier frontend dan Vite build PASS.
`php artisan route:list --path=finance` menunjukkan 12 route. Migration up/down
SQLite disposable PASS; `php artisan migrate:status
--path=app/Modules/Finance/Payments/Database/Migrations` pada MySQL local Ran
batch10. `php scripts/qa/payments-mysql.php` PASS: demo idempotent, current
reference guard sesudah snapshot, assignment menolak account nonaktif terbaru,
dirty check current, restore assignment stale ditolak dan FK RESTRICT.
Database generated dibersihkan; bukan load test semua interleaving.

`php scripts/qa/apply-payments-local.php` diterapkan lalu diulang PASS:
2 akun/3 metode, existing account/permission/audit row dan fingerprint data
user/role/grant/master/harga/stok/ledger terjaga. Tidak reset password, tidak
membuat saldo/mutasi/payment/penjualan. docs/API, QUALITY, MODULES dan dokumen
module diselaraskan. Tidak ada dependency/branch baru, perubahan theme,
Old-docs, SampleUI atau LSP. DevTools tetap BLOCKED dengan fallback PASS,
bukan klaim DevTools sudah menguji CRUD.

Delivery: commit fondasi 8896d0a, HTTP b7ccd6d, penguatan validasi e706c16,
UI/QA dfdb3d1, MySQL/local/docs 0e62e63 telah dipush ke origin/main.
`git ls-remote origin refs/heads/main` sama dengan HEAD 0e62e63 dan divergence
0/0. Staging/diff whitespace dan pemeriksaan pola secret terarah PASS.
Hanya SampleUI dan empat LSP user yang tetap untracked; tidak ikut commit.
HTTP pos.test terjangkau (200 setelah redirect login); QA authenticated CRUD
memakai fixture disposable, bukan akun pengguna lokal.
