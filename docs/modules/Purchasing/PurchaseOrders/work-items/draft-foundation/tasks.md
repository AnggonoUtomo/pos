# Tasks: Fondasi Draft Pembelian

## Sebelum Mulai

- [x] AGENTS/README/ARCHITECTURE/FOLDER-STRUCTURE/template dan module terkait dibaca.
- [x] Target diinformasikan; scope draft/harga positif dikonfirmasi user.
- [x] Source/kontrak/generator dan pola stok awal dipelajari.
- [x] Dependency publik supplier, math/format angka, lifecycle/audit dipetakan.
- [x] Non-scope stok/FIFO/Finance/payment/diskon/pajak dan QA ditetapkan.

## Increment 1: Scope

- [x] Tulis PRD/spec/module/work item sebelum coding.
  - Acceptance: contract, non-scope, demo, privasi dan aktivasi jelas.
  - Verification: review/link/diff; tanpa ADR abstraksi spekulatif.
- [x] Dry-run generator canonical.
  - Acceptance: provider/route/database/seeder di app/Modules/Purchasing/PurchaseOrders.
  - Verification: php artisan module:make Purchasing PurchaseOrders --dry-run.

## Increment 2: Domain Dan Application

- [x] Test RED math/CRUD/audit/no-ledger lalu implementasi.
  - Acceptance: decimal exact positif/per satuan, overflow/rounding ditolak;
    total backend, snapshot, status/version server, inactive/unit nonpurchase ditolak.
  - Verification: unit/feature tests RED/GREEN, rollback/veto, no-op/stale.
- [x] Schema ULID/FK/soft-delete dan supplier lookup publik.
  - Acceptance: tidak memakai model/domain privat module lain; lock current read;
    Domain murni, composition root binding, tidak ada integrasi Stock/Finance.
  - Verification: migration disposable up/down, boundary review/Pint/commit.

## Increment 3: HTTP, Query Dan Seeder

- [ ] Test RED lalu implementasi request/controller/query/routes.
  - Acceptance: guest/outsider/viewer/manage/super, CSRF, whitelist, 404/422,
    search literal/filter/pagination/trashed, read no mutation, stale ditolak.
  - Verification: focused tests, route:list, Pint.
- [ ] Access/demo seeder module.
  - Acceptance: no grant; CCTV/kelistrikan relasional dari public lookup; demo
    local/testing saja, existing/trashed tidak diubah, tidak menyentuh ledger.
  - Verification: idempotency/production/no-ledger tests dan commit.

## Increment 4: UI

- [ ] Page/types/komponen lokal, nav/palette, form/detail/lifecycle.
  - Acceptance: angka Indonesia exact, total readonly backend, no posting palsu;
    loading/error/empty, form tidak hilang saat error, guard UX dan Sonner.
  - Verification: typecheck/lint/build dan authenticated browser fixture.
- [ ] Shortcut/dropdown dan responsivitas.
  - Acceptance: desktop1440x1000, tablet1024x768/768x1024, Light/Dark,
    sidebar expanded/collapsed, dialog tidak overflow, tidak mengubah theme.
  - Verification: DevTools dahulu, screenshot/runtime checks dan commit.

## Increment 5: QA Dan Delivery

- [ ] Full/backend/native MySQL dan browser.
  - Acceptance: math/unique/FK/current read/stale/audit/no-ledger teruji;
    tidak mengklaim SQLite membuktikan locking MySQL.
  - Verification: full test/Pint/TS/lint/build, audits, QA disposable.
- [ ] Aktivasi lokal hanya schema/catalog dan preservasi data lama.
  - Acceptance: tidak seed draft user atau grant/reset akun, fingerprint identik.
  - Verification: helper guarded dua kali, migrate:status.
- [ ] API/MODULES/QUALITY/module/work item aktual dan commit.
  - Acceptance: checklist hanya sesudah lulus; gap/resiko/exclusion tercatat.
  - Verification: review/link/diff/staged/secret check.
- [ ] Push dan verifikasi remote.
  - Acceptance: HEAD=ls-remote, divergence0/0, artefak user tetap terpisah.
  - Verification: git push/status/ls-remote/rev-list.

## Sesudah Coding

- [ ] Scope/acceptance/test/QA terpenuhi.
- [ ] Migration/data existing aman dan tidak ada perubahan ledger.
- [ ] Dokumen/checklist/risiko/commit/push terverifikasi.

## Bukti Verifikasi

- Increment1: review scope/boundary/relative links PASS, generator dry-run
  menampilkan provider/routes/migration directory/demo seeder canonical;
  git diff --check PASS. Runtime belum berubah pada checkpoint ini.
- Increment2: RED 7 test class/action/schema belum ada; GREEN 7 test/55 assertion.
  Math exact/overflow/rounding, audit veto create/update/delete/restore, stale/
  no-op, no-ledger, lookup supplier redacted dan disposable SQLite up/down PASS.
  Pint PASS. Native MySQL dan HTTP/UI masih menunggu increment berikutnya.
