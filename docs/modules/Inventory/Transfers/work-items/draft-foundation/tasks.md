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

- [ ] Prepare/save/version/query dan route/permission/options.
  - Acceptance: master aktif/pasangan berbeda, duplicate/forgery/stale ditolak,
    view/manage terpisah, no-op, snapshot aman dan data pagination/filter.
  - Verification: HTTP403/419/422, valid/invalid feature, ledger fingerprint.

## Increment 4: Lifecycle, Audit Dan Demo

- [ ] Delete/restore berversi, audit atomic dan demo relasional.
  - Acceptance: snapshot tetap, non-draft immutable, rollback saat audit/model veto,
    demo tidak overwrite edited/trashed atau mengubah ledger/master/grant.
  - Verification: focused lifecycle/rollback/demo, production refusal.

## Increment 5: UI

- [ ] Workspace/dialog/table/filter/nav/palette/shortcut/Sonner.
  - Acceptance: format Indonesia, active/trashed/viewer, master unavailable,
    validation/loading/error/processing; tidak ada tombol posting palsu.
  - Verification: lint/typecheck/build, DevTools MCP1440/1024/768 Light/Dark.

## Increment 6: QA Dan Aktivasi

- [ ] Native MySQL/full gate, activation disposable/lokal dua kali.
  - Acceptance: worker stale/lock/JSON/exact6/audit/FK/down guard, source/Stock/
    Finance/grant lama identik, tidak membuat draft demo pada database toko.
  - Verification: scripts QA, full test/Pint, route/status, browser/fingerprint.
- [ ] Docs/checklist/review dan delivery.
  - Acceptance: semua scope lulus; user files utuh, local/remote SHA sama0/0.
  - Verification: link/diff/staged scope, commit/push/ls-remote.

## Sesudah Coding

- [ ] Acceptance dan status increment sesuai bukti.
- [ ] Backend/UI/native/browser/activation relevan lulus.
- [ ] Docs/API/module index selaras; gap/non-scope dicatat.
- [ ] Commit incremental dan delivery terverifikasi.

## Hasil Verifikasi

Dry-run generator PASS: provider, Routes, Database/Migrations/Seeders dan test
canonical. File user staged30 tetap terpisah. Gate implementasi belum dijalankan.

Increment2: unit red-green, scaffold/schema/empty down-up 4 test/20 assertion
PASS; Pint PASS. Belum menjalankan migration pada DB lokal.
