# Tasks: Inventory Foundation

## Sebelum Mulai

- [x] `AGENTS.md` dibaca.
- [x] `docs/README.md`, `docs/PROJECT.md`, `docs/ARCHITECTURE.md`,
  `docs/FOLDER-STRUCTURE.md`, `docs/MODULES.md`, `docs/WORKFLOW.md`, dan
  `docs/QUALITY.md` dirujuk sesuai scope.
- [x] Scope awal dibatasi ke perencanaan Inventory foundation.
- [x] Module target disebut ke user: lintas module Inventory (`Warehouses`,
  `Units`, `Items`).
- [x] Dampak database, route, permission, UI, seeder, audit, soft delete, stok,
  FIFO, payment, tax, diskon, dan pricing dicek sesuai scope.
- [x] QA automated ditentukan per increment.
- [x] Chrome DevTools QA direncanakan hanya untuk increment UI.
- [x] `Old-docs/` tidak disentuh.

## Increment 1: Work Item Dan Rencana Implementasi

- [x] Buat `README.md` work item.
  - Acceptance: mencatat kondisi awal, scope, non-scope, acceptance criteria,
    dependency, dampak, dan handoff.
  - Verification: review manual dokumen.
- [x] Buat `plan.md`.
  - Acceptance: berisi urutan increment, dependency graph, QA automated,
    risiko, dan open questions.
  - Verification: review manual dokumen.
- [x] Buat `tasks.md`.
  - Acceptance: checklist sebelum mulai, increment detail, dan hasil verifikasi
    tersedia sebelum coding.
  - Verification: `git diff --check`.

## Increment 2: Module Warehouses

- [ ] Jalankan dry-run scaffold `Inventory/Warehouses`.
  - Acceptance: output membuat module di `app/Modules/Inventory/Warehouses`
    dengan `Routes/`, `Database/Migrations`, dan `Database/Seeders`.
  - Verification: `php artisan module:make Inventory Warehouses --dry-run`.
- [ ] Buat module source `Inventory/Warehouses`.
  - Acceptance: provider, route, migration folder, seeder demo, permission key,
    dan test minimal tersedia.
  - Verification: focused test; `php artisan route:list --except-vendor`.
- [ ] Tentukan data gudang awal.
  - Acceptance: gudang memiliki ULID, kode, nama, status aktif, dan soft delete
    sebagai master mutable.
  - Verification: migration/status check.

## Increment 3: Module Units

- [ ] Jalankan dry-run scaffold `Inventory/Units`.
  - Acceptance: output module sesuai struktur canonical.
  - Verification: `php artisan module:make Inventory Units --dry-run`.
- [ ] Buat module source `Inventory/Units`.
  - Acceptance: mendukung base unit dan satuan turunan untuk multi satuan.
  - Verification: focused test; migration/status check.
- [ ] Tentukan aturan konversi.
  - Acceptance: conversion ratio tidak nol/negatif dan siap dipakai item.
  - Verification: unit/feature test validasi.

## Increment 4: Module Items

- [ ] Jalankan dry-run scaffold `Inventory/Items`.
  - Acceptance: output module sesuai struktur canonical.
  - Verification: `php artisan module:make Inventory Items --dry-run`.
- [ ] Buat module source `Inventory/Items`.
  - Acceptance: item memiliki ULID, kode/SKU, nama, base unit, status aktif,
    dan soft delete master.
  - Verification: focused test; migration/status check.
- [ ] Siapkan relasi satuan item.
  - Acceptance: item dapat memiliki satuan jual/beli awal yang mengarah ke unit
    dan conversion ratio valid.
  - Verification: feature test relasi.

## Increment 5: Permission, Audit, Dan Seeder Demo

- [ ] Tambahkan permission backend.
  - Acceptance: permission minimal `inventory.view` dan `inventory.manage`
    tersedia serta dipakai pada controller/action terkait.
  - Verification: permission focused test.
- [ ] Tambahkan activity log untuk mutation master.
  - Acceptance: create/update/delete master penting tercatat melalui Spatie
    Activitylog.
  - Verification: activity log smoke test.
- [ ] Tambahkan demo seeder relasional.
  - Acceptance: seeder membuat gudang, satuan, dan item demo yang dapat dipakai
    alur POS mock.
  - Verification: seed command module; focused test bila tersedia.

## Increment 6: UI Admin Inventory

- [ ] Buat halaman list master gudang, satuan, dan item.
  - Acceptance: halaman memakai layout admin, shortcut operasi dropdown, dan
    permission UX.
  - Verification: `npm run lint`; `npm run build`.
- [ ] Tambahkan form CRUD master.
  - Acceptance: memakai Sonner toast success/error, loading/empty/error state,
    soft delete UI bila relevan, dan validasi user-friendly.
  - Verification: Chrome DevTools MCP desktop/tablet.
- [ ] Pastikan route palsu tidak dibuat.
  - Acceptance: navigasi hanya mengarah ke route nyata; menu yang belum siap
    tetap controlled.
  - Verification: `php artisan route:list --except-vendor`; browser QA.

## Sesudah Coding

- [ ] Scope selesai.
- [ ] Focused test lulus atau dinyatakan tidak relevan.
- [ ] Lint/build/typecheck lulus bila relevan.
- [ ] Route/migration check lulus bila relevan.
- [ ] Chrome DevTools QA PASS, atau BLOCKED/SKIPPED dengan alasan.
- [ ] Work item dan dokumen module diperbarui.
- [ ] Risiko terbuka dicatat.

## Hasil Verifikasi

| Command | Hasil | Catatan |
| --- | --- | --- |
| `git push origin main` | PASS | Commit UI/theme sebelumnya sudah dipush sebelum work item Inventory dibuat |
| `git diff --check` | PASS | Tidak ada whitespace error pada work item Inventory foundation |

Jangan menambahkan pekerjaan baru ke checklist ini tanpa persetujuan user.
