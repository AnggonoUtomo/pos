# Tasks: Fondasi Draft Transfer Antar-Gudang

## Sebelum Mulai

- [x] AGENTS/README/ARCHITECTURE/FOLDER-STRUCTURE dibaca.
- [x] Scope draft dan non-scope disetujui; module target diumumkan.
- [x] Template/dokumen/consumer master dan generator dry-run ditinjau.
- [x] Database/route/permission/UI/audit/seeder/soft delete dipetakan.
- [x] Stock/FIFO/Finance hanya fingerprint; bukan mutation scope ini.
- [x] Rencana acceptance/QA dan file user yang dikecualikan dicatat.

## Increment 1: Baseline

- [x] Dokumen module dan work item disusun sebelum coding.
  - Acceptance: boundary, snapshot, qty, version, non-scope dan QA eksplisit.
  - Verification: review template/source, generator dry-run, git diff --check.

## Increment 2: Schema Dan Domain

- [x] Generator, dua tabel ULID dan TransferQuantity.
  - Acceptance: routes/database module, exact6/overflow/precision guard, down aman.
  - Verification: unit red-green, scaffold/schema/empty up-down tests, Pint.

## Increment 3: Simpan Dan Query

- [x] Prepare/save/version/query dan route/permission/options.
  - Acceptance: master aktif/pasangan berbeda, duplicate/forgery/stale ditolak,
    view/manage terpisah, no-op, snapshot aman dan data pagination/filter.
  - Verification: HTTP403/419/422, valid/invalid feature, ledger fingerprint.

## Increment 4: Lifecycle, Audit Dan Demo

- [x] Delete/restore berversi, audit atomic dan demo relasional.
  - Acceptance: snapshot tetap, non-draft immutable, rollback saat audit/model veto,
    demo tidak overwrite edited/trashed atau mengubah ledger/master/grant.
  - Verification: focused lifecycle/rollback/demo, production refusal.

## Increment 5: UI

- [x] Workspace/dialog/table/filter/nav/palette/shortcut/Sonner.
  - Acceptance: format Indonesia, active/trashed/viewer, master unavailable,
    validation/loading/error/processing; tidak ada tombol posting palsu.
  - Verification: lint/typecheck/build, DevTools MCP1440/1024/768 Light/Dark.

## Increment 6: QA Dan Aktivasi

- [x] Native MySQL/full gate, activation disposable/lokal dua kali.
  - Acceptance: worker stale/lock/JSON/exact6/audit/FK/down guard, source/Stock/
    Finance/grant lama identik, tidak membuat draft demo pada database toko.
  - Verification: scripts QA, full test/Pint, route/status, browser/fingerprint.
- [ ] Docs/checklist/review dan delivery.
  - Acceptance: semua scope lulus; user files utuh, local/remote SHA sama0/0.
  - Verification: link/diff/staged scope, commit/push/ls-remote.

## Sesudah Coding

- [x] Acceptance dan status increment sesuai bukti.
- [x] Backend/UI/native/browser/activation relevan lulus.
- [x] Docs/API/module index selaras; gap/non-scope dicatat.
- [ ] Commit incremental dan delivery terverifikasi.

## Hasil Verifikasi

Dry-run generator PASS: provider, Routes, Database/Migrations/Seeders dan test
canonical. File user staged30 tetap terpisah. Gate implementasi belum dijalankan.

Increment2: unit red-green, scaffold/schema/empty down-up 4 test/20 assertion
PASS; Pint PASS. Belum menjalankan migration pada DB lokal.

Increment3: 9 test/85 assertion PASS, termasuk snapshot/no-op/stale/pasangan,
quantity/forgery, permission/options terpisah, CSRF dan query literal/filter.
Error duplicate import Route sisa scaffold diperbaiki sebelum test lulus.

Increment4: 16 test/126 assertion PASS; lifecycle/stale/immutable super-system,
audit veto seluruh mutation, model veto, down populated guard dan demo CCTV/
kelistrikan idempotent/skip/production refusal. Master/ledger/grant tidak
dimutasi oleh demo. Pint PASS. Tidak ada duplicate inherited test pada suite.

Increment5: typecheck, ESLint resources/js, build2750 module PASS. Full suite
777 test/6142 assertion PASS. Chrome DevTools MCP discovery/list_pages PASS;
runner isolated/stdio existing juga PASS untuk CRUD via shortcut, konversi
Indonesia, filter empty, viewer readonly, palette, Light/Dark sidebar expanded/
collapsed desktop1440/tablet1024/768, console/network bersih. Tujuh screenshot/
report ignored pada storage/framework/testing/transfer-draft-browser-311231d1944e5803;
tablet Light dan Dark ditinjau visual. Fixture membuktikan empat audit lifecycle,
version4 serta Stock/FIFO/Finance/master identik. Tidak menguji posting transfer.

## Bukti Increment 6

- Native MySQL PASS: empty down/up, decimal20,6, no-op, dua worker LOCK WAIT,
  concurrent edit stale tanpa lost update, current read gudang nonaktif,
  audit rollback, restore/FK/down guard dan fingerprint populated source/Stock/
  Finance/master. Database/worker dibersihkan pada finally.
- Activation MySQL disposable PASS: baseline tanpa schema transfer, dua child
  activation, dua tabel baru kosong, catalog unik, role/grant lama identik,
  kemudian rerun mempertahankan draft fixture. Cache permission QA array.
- Activation lokal dua kali PASS, migration2026_10_23_000003 Ran batch26;
  draft0 dan dua permission catalog. Tidak menjalankan demo/master reset,
  posting stok, mutation Finance, akun/password atau grant otomatis.
- Full777 test/6142 assertion; focused16/126, Pint scoped, lint resources/js,
  typecheck/build PASS. Route7 sesuai scope. MCP namespace langsung tersedia:
  list_pages dan guest URL http://pos.test/inventory/transfers diarahkan ke
  login; snapshot/console PASS tanpa menggunakan sesi user. Tab QA ditutup.
- PRD mengonsolidasikan scope produk yang sudah disetujui; tidak menambah
  requirement. Tidak perlu ADR posting karena posting belum ditetapkan.

## Review Dan Risiko Terbuka

Review correctness/layer/security/readability/query: dependency hanya published
read contracts, lock terurut, strict payload/version, audit atomic, projection
bulk pagination25 dan opsi bounded. Tidak ada temuan tersisa dalam scope.
Gap existing nama aksesibel tombol command/theme topnav tablet di luar scope;
palette/tema diuji melalui pola existing. Mobile tidak diuji sesuai keputusan user.
Biaya FIFO, layer tanggal transfer, posting satu/dua tahap, stok perjalanan,
parsial dan reversal belum disetujui. Draft tidak mereservasi ketersediaan.
Old-docs/theme/dependency/SampleUI26/LSP4 staged tidak disentuh. Delivery pending
commit QA/docs ini dan verifikasi remote.
