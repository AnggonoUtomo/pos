# Tasks: Fondasi Master Supplier

## Sebelum Mulai

- [x] AGENTS, docs/README, ARCHITECTURE, FOLDER-STRUCTURE dan template dibaca.
- [x] Scope user dan pola master existing dipelajari.
- [x] Target Purchasing/Suppliers diinformasikan sebelum coding.
- [x] Dampak schema/route/permission/UI/seeder/audit/soft delete dipetakan.
- [x] Stok/FIFO/Finance/pricing bukan scope, tidak membuat integrasi prematur.
- [x] QA/aktivasi additive/exclusion dan checklist incremental direncanakan.

## Increment 1: Scope

- [x] Tulis dokumen module/work item/PRD sebelum coding.
  - Acceptance: requirement, non-scope, audit privacy dan aktivasi jelas.
  - Verification: review/link/diff, tidak perlu ADR keputusan mahal baru.
- [x] Jalankan dry-run generator.
  - Acceptance: module app/Modules/Purchasing/Suppliers canonical.
  - Verification: php artisan module:make Purchasing Suppliers --dry-run.

## Increment 2: Schema Dan Application

- [x] Scaffold generator dan test RED persistence/action/audit.
  - Acceptance: route/database/provider di module, tanpa Domain/port publik kosong.
  - Verification: generator, RED tests dan migration disposable up/down.
- [x] Implementasikan CRUD/lock/audit atomic dan privasi.
  - Acceptance: ULID/code canonical/unique/soft delete/restore, no-op,
    audit gagal/veto me-rollback semua; kontak hanya changed_fields.
  - Verification: focused tests, Pint dan boundary review.
- [x] Catat bukti dan commit setelah GREEN.

## Increment 3: HTTP, Query Dan Seeder

- [x] Tulis test HTTP/query/permission/seeder RED lalu implementasi.
  - Acceptance: guest/outsider/viewer/manage/super benar, CSRF/validasi/duplikat,
    pagination/status/trashed/search literal, privacy/read-only.
  - Verification: feature tests dan route:list.
- [x] Implementasikan akses catalog dan demo module.
  - Acceptance: hanya dua key web tanpa grant, demo lokal/testing relevan,
    idempotent/skip existing/trashed; tidak membuat login/transaction/saldo.
  - Verification: seed tests termasuk production ditolak, Pint dan commit.

## Increment 4: UI

- [ ] Buat page/types/komponen Suppliers lokal dan nav/palette.
  - Acceptance: pola shared master existing, state loading/error/empty,
    permission guard UX, icon semantik, tidak mengubah theme.
  - Verification: TS/lint/build dan browser.
- [ ] Dialog CRUD/lifecycle dan shortcut.
  - Acceptance: Sonner, nilai optional, data persisten/reload, shortcut dropdown,
    form/konfirmasi aman desktop/tablet Light/Dark.
  - Verification: browser fixture dan screenshot, commit setelah lulus.

## Increment 5: QA Dan Delivery

- [ ] Full/backend/native MySQL dan browser QA.
  - Acceptance: audit/unique/current read native teruji, bukan klaim SQLite lock.
  - Verification: full/Pint/TS/lint/build, MySQL disposable, DevTools/fallback.
- [ ] Aktifkan lokal additive dengan fingerprint existing.
  - Acceptance: schema/catalog/demo baru boleh ditambah; akun/grant/master/ledger
    lama tetap, audit lama tidak berubah, tidak reset/seed transaksi user.
  - Verification: helper guarded/status/fingerprint dan ulang idempotency.
- [ ] Selaraskan API/MODULES/QUALITY/dokumen dan commit.
  - Acceptance: capability/risiko aktual, checklist hanya sesudah verifikasi.
  - Verification: review/link/diff/staged/secret exclusions.
- [ ] Push dan cocokkan GitHub.
  - Verification: push/ls-remote/divergence0/0, artefak user tidak ikut.

## Sesudah Coding

- [ ] Scope/acceptance terverifikasi.
- [ ] Full/backend/frontend/browser dan status migration sesuai hasil aktual.
- [ ] Aktivasi/demo/audit/privacy dan data pengguna aman.
- [ ] Dokumen/checklist/risiko/commit/remote terverifikasi.

## Bukti Command

- Increment 1: review scope dan tautan relatif lokal PASS; generator dry-run
  PASS (provider, routes, migration directory, module demo seeder); diff check PASS.
- Increment 2: RED 6 test class/action belum ada; GREEN 7 test/33 assertion,
  termasuk migration down/up SQLite disposable. Pint PASS. Native MySQL
  dan HTTP/UI belum diverifikasi pada checkpoint ini.
- Increment 3: HTTP/seeder RED 5 test (route/catalog/demo belum tersedia);
  GREEN total Suppliers 13 test/114 assertion. Enam route auth/verified/can
  PASS; CSRF 419 semua mutation tanpa token PASS, Pint PASS. Assertion
  Inertia menguji nama component tanpa pemeriksaan file sampai Increment 4.
