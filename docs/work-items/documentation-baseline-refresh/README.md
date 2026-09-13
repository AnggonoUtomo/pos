# Work Item: Documentation Baseline Refresh

## Status

Done

## Owner Dan Lokasi

- Owner module: lintas module.
- Target kode: tidak ada perubahan runtime yang diniatkan.
- Target dokumen: `AGENTS.md` dan `docs/`.

## Kondisi Awal

Project menerima template dokumentasi baru. Acuan arsitektur dan pola
pengembangan harus dipindahkan ke dokumen aktif baru. `Old-docs/` hanya boleh
menjadi referensi garis besar PRD dan ADR.

## Scope

- Mengisi baseline project POS pada template docs baru.
- Memindahkan keputusan PRD/ADR utama ke dokumen aktif.
- Menyelaraskan arsitektur, struktur folder, workflow, quality, API, module
  index, ADR, dan template kerja.
- Menambahkan dokumen module awal untuk capability platform yang relevan.

## Tidak Dikerjakan

- Coding fitur bisnis baru.
- Commit dan push.
- Menghapus `Old-docs/`.
- Mengubah dependency package.

## Acceptance Criteria

- [x] Placeholder aktif pada dokumen root diganti dengan baseline POS.
- [x] ADR utama tersedia pada `docs/decisions/`.
- [x] Struktur module memakai `app/Modules/{Domain}/{Module}`.
- [x] Konvensi `Application/DTOs` terdokumentasi.
- [x] Workflow work item memuat increment dan checklist sebelum/sesudah coding.
- [x] QA automated dan Chrome DevTools MCP terdokumentasi.
- [x] Sonner toast, soft delete, permission middleware, demo seeder, dan
  `isSuperSystem` terdokumentasi.
- [x] Pemeriksaan placeholder dan whitespace selesai.

## Dampak Yang Harus Dicek

- [x] Database/migration: tidak disentuh.
- [x] Route: tidak disentuh.
- [x] Permission/policy: hanya didokumentasikan.
- [x] UI/Inertia: hanya didokumentasikan.
- [x] Seeder demo: hanya didokumentasikan.
- [x] Activity log: hanya didokumentasikan.
- [x] Soft delete atau mekanisme koreksi: hanya didokumentasikan.
- [x] Transaksi stok/FIFO/payment/tax/diskon/pricing: hanya didokumentasikan.

## Dependency Dan Keputusan

- User meminta dokumen baru menjadi acuan utama.
- `Old-docs/` hanya dipakai untuk garis besar PRD dan ADR.

## Increment

| Increment | Status | Ringkasan | Verifikasi |
| --- | --- | --- | --- |
| 1 | Passed | Isi AGENTS, README, PROJECT, ARCHITECTURE, FOLDER-STRUCTURE | Review dokumen |
| 2 | Passed | Isi MODULES, API, QUALITY, WORKFLOW, DECISIONS, ADR | Review dokumen |
| 3 | Passed | Isi template dan dokumen module platform | Review dokumen |
| 4 | Passed | Jalankan pemeriksaan placeholder dan whitespace | `rg`; `git diff --check` |

## Handoff

- Perubahan: baseline docs baru sudah diisi untuk project, arsitektur, struktur,
  workflow, quality, module index, API, ADR, template, dan module platform awal.
- Verifikasi: `rg` placeholder spesifik PASS; `git diff --check` PASS.
- Chrome DevTools QA: SKIPPED karena tidak ada perubahan UI runtime.
- Risiko terbuka: `Platform/ModuleRuntime` masih capability/scaffolding, belum
  module fisik; Sonner belum boleh diasumsikan terpasang sampai work item UI
  baseline memasangnya.
