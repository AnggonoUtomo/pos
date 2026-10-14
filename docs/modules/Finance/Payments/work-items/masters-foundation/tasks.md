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

- [ ] Route/controller/request/query dua master dan filter/choices.
  - Acceptance: auth/403/422/404, CSRF, payload whitelist, unique teredaksi,
    pagination25 dan account options aktif max1000.
  - Verification: HTTP permission/validation/CRUD/restore/filter tests.
- [ ] Route/migration regression dan commit.
  - Acceptance: route/migration module nyata, no runtime gap pada scaffold.
  - Verification: route:list, migration disposable/status dan focused tests.

## Increment 3: UI Admin

- [ ] Form/tabel dua halaman modular, navigasi/palette dan permission UX.
  - Acceptance: shadcn/Sonner, metadata bank conditional/masked, CRUD/restore,
    readonly, empty/error/loading, shortcut dropdown, type/account pilihan jelas.
  - Verification: lint/TS/build dan browser desktop/tablet Light/Dark.
- [ ] Guard/error/shortcut dan commit.
  - Acceptance: account terpakai ditolak, edit/reload relasi tersimpan,
    sidebar expanded/collapsed, tanpa perubahan theme/POS payment.
  - Verification: QA automated dan screenshot/report ignored.

## Increment 4: Penutupan

- [ ] Full gates dan QA MySQL current read/FK.
  - Acceptance: semua gates PASS, tidak mengklaim SQLite sebagai row-lock proof.
  - Verification: PHP suite, Pint, lint/TS/build, route/migration dan MySQL QA.
- [ ] Aktivasi migration/demo lokal additive dan docs/status.
  - Acceptance: data user lama/account/grant/stock/ledger identik, no reset.
  - Verification: fingerprints sebelum/sesudah dan command dicatat.
- [ ] Commit/push dan cocokkan remote HEAD.
  - Acceptance: hanya file scope terkirim; SampleUI/LSP/Old-docs tidak disentuh.
  - Verification: staged scope/check, push/ls-remote dan divergence.

## Hasil Verifikasi

Increment 1: RED 9 tests sebelum action/migration tersedia. Setelah implementasi,
PaymentsFoundationTest 9 tests/44 assertions PASS; Pint PASS. Audit/no-op,
metadata-only redacted, guard termasuk trashed, invalid relation/type, restore,
rollback persistence, demo idempotent/preservation/production refusal dan
prerequisite rollback serta migration up/down terverifikasi.
