# Tasks: Riwayat Pembayaran

## Sebelum Mulai

- [x] AGENTS/README/ARCHITECTURE/FOLDER-STRUCTURE dan module dibaca.
- [x] User menyetujui scope read-only; target diinformasikan.
- [x] Ownership, timezone, presisi/filter split, permission dan non-scope ditetapkan.
- [x] Worktree user terpisah; Old-docs tidak dibaca/disentuh.

## Increment 1: Contract

- [x] Tulis README/plan/tasks sebelum coding.
  - Acceptance: tidak mengklaim saldo, tidak mutate ledger, tanpa dependency baru.
  - Verification: review manual/diff/link.

## Increment 2: Backend

- [x] Port/query/read adapter, request/controller/route, permission catalog.
  - Acceptance: dua arah/snapshot exact, filter split sama/date Jakarta/literal,
    deterministic pagination25, invalid422/missing404/unauthorized403, read-only.
  - Verification: RED/GREEN, permission matrix/super/privacy/GET fingerprint,
    route/Pint/commit.

## Increment 3: UI

- [ ] Daftar/filter/detail, nav/palette/shortcut.
  - Acceptance: Indonesia, badge semantic, readonly/no mutation control,
    loading/empty/error/retry, cancel stale detail response, framing tablet.
  - Verification: TS/lint/build, DevTools dan browser desktop1440/tablet1024/768,
    Light/Dark expanded/collapsed, detail/console/permission/shortcut; commit.

## Increment 4: QA Dan Delivery

- [ ] Full/native QA dan aktivasi catalog aman.
  - Acceptance: native union/date/decimal/filter query sesuai, no business mutation,
    catalog tanpa grant/reset/seed; existing data identik.
  - Verification: full tests/Pint/TS/lint/build/audits, MySQL disposable,
    aktivasi fingerprint dua kali dan migration status.
- [ ] Update API/module/QUALITY dan review akhir.
  - Acceptance: dokumen menjelaskan behavior aktual, limit/non-scope terbuka.
  - Verification: link/diff/staged/exclusions/secret/commit.
- [ ] Push dan remote verifikasi.
  - Verification: HEAD=ls-remote/divergence0/0, user untracked terpisah.

## Sesudah Coding

- [ ] Acceptance dan QA terisi sesuai hasil nyata.
- [ ] Tidak menyentuh data toko/arsip, commit/push terverifikasi.

## Bukti

- Increment1: contract scope read-only dikonfirmasi; struktur/source konsisten,
  tidak ada module baru atau perubahan identifier/stack/bounded context.
- Increment2: RED4 (route belum ada), GREEN29/338 bersama regresi writer sale/
  purchase dan HTTP master. Permission dedicated, 405 mutation, GET fingerprint,
  privacy allowlist, snapshot sesudah master terhapus, date Jakarta, literal
  wildcard, filter split sama, pagination deterministic dan super validation PASS.
  Pint/3 route PASS. Pemeriksaan file page ditunda ke UI (assert component false
  hanya pada checkpoint backend); UI tidak diklaim selesai pada increment ini.
