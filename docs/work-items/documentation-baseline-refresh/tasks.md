# Tasks: Documentation Baseline Refresh

## Sebelum Coding

- [x] Scope dan non-scope disetujui.
- [x] Module target sudah disebut ke user: lintas module dokumentasi.
- [x] Dokumen rujukan dibaca.
- [x] Acceptance criteria jelas.
- [x] Dampak database, route, permission, UI, seeder, dan audit dicek.
- [x] Risiko FIFO, stok, payment, tax, diskon, dan pricing dicek karena masuk
  dokumentasi baseline.
- [x] QA automated ditentukan.
- [x] Chrome DevTools QA direncanakan sebagai SKIPPED karena docs-only.
- [x] Gap conformance dicatat bila ditemukan.

## Increment

| No | Status | Task | Acceptance | Verification |
| --- | --- | --- | --- | --- |
| 1 | Passed | Isi dokumen root project | Placeholder root docs diganti baseline POS | Review dokumen |
| 2 | Passed | Isi ADR dan keputusan aktif | Keputusan utama tersedia di `docs/decisions` | Review dokumen |
| 3 | Passed | Isi template kerja | Template memuat increment dan checklist QA | Review dokumen |
| 4 | Passed | Isi dokumen module platform | Identity dan ModuleRuntime terdokumentasi | Review source dan docs |
| 5 | Passed | Verifikasi docs | Placeholder tidak sengaja dan whitespace dicek | `rg`; `git diff --check` |

## Sesudah Coding

- [x] Scope selesai.
- [x] Focused test lulus atau dinyatakan tidak relevan.
- [x] Lint/build/typecheck lulus atau dinyatakan tidak relevan.
- [x] Route/migration check lulus atau dinyatakan tidak relevan.
- [x] Chrome DevTools QA PASS, atau BLOCKED/SKIPPED dengan alasan.
- [x] Work item dan dokumen module diperbarui.
- [x] Risiko terbuka dicatat.

## Hasil Verifikasi

| Command | Hasil | Catatan |
| --- | --- | --- |
| `rg placeholder aktif` | PASS | Tidak ada placeholder template mentah pada dokumen aktif non-template |
| `git diff --check` | PASS | Tidak ada output |

Jangan menambahkan pekerjaan baru ke checklist ini tanpa persetujuan user.
