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

- [x] Test RED lalu implementasi request/controller/query/routes.
  - Acceptance: guest/outsider/viewer/manage/super, CSRF, whitelist, 404/422,
    search literal/filter/pagination/trashed, read no mutation, stale ditolak.
  - Verification: focused tests, route:list, Pint.
- [x] Access/demo seeder module.
  - Acceptance: no grant; CCTV/kelistrikan relasional dari public lookup; demo
    local/testing saja, existing/trashed tidak diubah, tidak menyentuh ledger.
  - Verification: idempotency/production/no-ledger tests dan commit.

## Increment 4: UI

- [x] Page/types/komponen lokal, nav/palette, form/detail/lifecycle.
  - Acceptance: angka Indonesia exact, total readonly backend, no posting palsu;
    loading/error/empty, form tidak hilang saat error, guard UX dan Sonner.
  - Verification: typecheck/lint/build dan authenticated browser fixture.
- [x] Shortcut/dropdown dan responsivitas.
  - Acceptance: desktop1440x1000, tablet1024x768/768x1024, Light/Dark,
    sidebar expanded/collapsed, dialog tidak overflow, tidak mengubah theme.
  - Verification: DevTools dahulu, screenshot/runtime checks dan commit.

## Increment 5: QA Dan Delivery

- [x] Full/backend/native MySQL dan browser.
  - Acceptance: math/unique/FK/current read/stale/audit/no-ledger teruji;
    tidak mengklaim SQLite membuktikan locking MySQL.
  - Verification: full test/Pint/TS/lint/build, audits, QA disposable.
- [x] Aktivasi lokal hanya schema/catalog dan preservasi data lama.
  - Acceptance: tidak seed draft user atau grant/reset akun, fingerprint identik.
  - Verification: helper guarded dua kali, migrate:status.
- [ ] API/MODULES/QUALITY/module/work item aktual dan commit.
  - Acceptance: checklist hanya sesudah lulus; gap/resiko/exclusion tercatat.
  - Verification: review/link/diff/staged/secret check.
- [ ] Push dan verifikasi remote.
  - Acceptance: HEAD=ls-remote, divergence0/0, artefak user tetap terpisah.
  - Verification: git push/status/ls-remote/rev-list.

## Sesudah Coding

- [x] Scope/acceptance/test/QA terpenuhi.
- [x] Migration/data existing aman dan tidak ada perubahan ledger.
- [ ] Dokumen/checklist/risiko/commit/push terverifikasi.

## Bukti Verifikasi

- Increment1: review scope/boundary/relative links PASS, generator dry-run
  menampilkan provider/routes/migration directory/demo seeder canonical;
  git diff --check PASS. Runtime belum berubah pada checkpoint ini.
- Increment2: RED 7 test class/action/schema belum ada; GREEN 7 test/55 assertion.
  Math exact/overflow/rounding, audit veto create/update/delete/restore, stale/
  no-op, no-ledger, lookup supplier redacted dan disposable SQLite up/down PASS.
  Pint PASS. Native MySQL dan HTTP/UI masih menunggu increment berikutnya.
- Increment3: RED 5 HTTP/seeder test (route/catalog/demo belum ada); GREEN
  total12 test/133 assertion, Pint/6 route auth+verified+can PASS. Termasuk
  CSRF419, manage-only tidak view, super-system tetap invariant, snapshot stale,
  whitelist, read-only/filter/pagination, demo_key idempotent sesudah edit catatan
  dan trashed. Inertia nama component diuji; file page menyusul Increment4.
- Increment4: TS/lint/build PASS (2723 module), node item-ratio-format dan
  purchase-draft-format PASS. Helper angka Indonesia dipindah ke js/lib karena
  dua consumer nyata, semua consumer internal/QA diubah; output Items tetap sama.
  Inertia page file check aktif kembali. DevTools URL/login/assets21/console PASS;
  authenticated QA memakai fixture isolated, tidak memakai credential user.
- Browser QA: PASS pada report lokal
  storage/framework/testing/purchase-drafts-browser-736d3065ac15769e/report.json.
  CRUD/detail/reload/error retention, dua tab stale, filter/empty/shortcut/palette,
  viewer/outsider, persistence/audit privacy/no-ledger; console/page error0.
  Desktop1440x1000/tablet1024x768/768x1024, Light/Dark expanded/collapsed;
  total/footer detail kini terverifikasi di dalam dialog, tabel scroll internal.
  Review screenshot awal menemukan clipping meski frame PASS; test baru RED
  light-detail-tablet: total terpotong. Perbaikan min-w-0 pada form/wrapper
  lokal, build ulang dan seluruh browser QA GREEN. Selector no-posting awal
  diperbaiki agar tidak menghitung menu sidebar Pembayaran.
- Increment5: php artisan test --compact PASS449 test/3565 assertion (termasuk
  regression persisted precision maksimum); focused PurchaseOrders13/135 PASS;
  Pint --test scoped PHP PASS. TS/lint/build ulang sesudah perbaikan tablet PASS.
  npm audit0 vulnerabilities, composer audit --locked --no-interaction0 advisory.
- php scripts/qa/purchase-drafts-mysql.php PASS: migration lengkap/up-down,
  demo idempotent, locked current read sesudah peer commit menolak stale version
  dan supplier nonaktif, audit veto rollback header/baris/revision, unique order/
  item dan FK RESTRICT supplier. Ledger stok existing nonnol (opening posted),
  FIFO/movement/finance/sales identik sepanjang lifecycle draft. QA database
  dihapus pada finally; schema QA sisa dicek kosong. Tidak mengklaim seluruh
  race scenario teruji. Run terinterup tidak dipakai sebagai bukti PASS.
- php scripts/qa/apply-purchase-drafts-local.php dua kali PASS; batch15 Ran,
  dua permission tanpa grant, tanpa draft demo. Fingerprint seluruh tabel lama
  (kecuali derived cache/migrations) dan tiap permission lama identik; tidak
  reset akun/password, mengubah master/ledger/audit user atau seed transaksi.
- Review boundary/security/performance PASS: hanya public lookup/DTOs antar
  module, Domain bebas framework, query parameterized/25*100 batas baris,
  permission per action/CSRF/whitelist/version dan audit atomic/redacted.
  Scope tidak memperbaiki module lain di luar helper angka shared/consumer nyata.
  Risiko lookup bounded1000, master bisa invalid setelah draft, no posting dan
  mobile ditunda terdokumentasi. Old-docs/SampleUI/empat LSP tidak disentuh.
