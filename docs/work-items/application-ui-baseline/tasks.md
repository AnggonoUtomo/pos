# Tasks: Application UI Baseline

## Sebelum Coding

- [x] Scope dan non-scope disetujui.
- [x] Module target sudah disebut ke user: lintas module UI baseline.
- [x] Dokumen rujukan dibaca.
- [x] Acceptance criteria jelas.
- [x] Dampak database, route, permission, UI, seeder, dan audit dicek.
- [x] Risiko FIFO, stok, payment, tax, diskon, dan pricing dicek; pada work item
  ini hanya mock UI, bukan mutation bisnis.
- [x] QA automated ditentukan.
- [x] Chrome DevTools QA direncanakan bila UI/browser tersentuh.
- [x] Gap conformance dicatat bila ditemukan.

## Increment

| No | Status | Task | Acceptance | Verification |
| --- | --- | --- | --- | --- |
| 1 | Planned | Audit UI starterkit dan route aktif | Target route/layout/komponen jelas; tidak ada asumsi route palsu | `rg`; `php artisan route:list --except-vendor`; `npm list sonner --depth=0` |
| 2 | Planned | Siapkan Sonner toast baseline | `Toaster` tersedia dan pola toast CRUD terdokumentasi/terpakai | `npm run lint`; `npm run build` |
| 3 | Planned | Implement admin ERP sidebar controlled | Navigasi module lengkap tampil; item tanpa route nyata disabled/coming soon | `npm run lint`; `npm run build`; Chrome DevTools MCP |
| 4 | Planned | Implement POS fullscreen mock realistis | Search, cart, gudang, customer level, diskon, pajak, payment drawer tampil dan ergonomis | `npm run lint`; `npm run build`; Chrome DevTools MCP |
| 5 | Planned | Rapikan folder modular frontend | Komponen fitur berada dekat page; shared hanya untuk komponen stabil | `git diff --check`; review struktur |
| 6 | Planned | Final verification dan update checklist | Semua command dan QA browser dicatat | `npm run lint`; `npm run build`; `php artisan route:list --except-vendor`; `git diff --check` |

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
| `rg` audit UI | Pending | Dijalankan saat increment 1 |
| `php artisan route:list --except-vendor` | Pending | Dijalankan saat route dicek |
| `npm list sonner --depth=0` | Pending | Dijalankan saat increment 1 |
| `npm run lint` | Pending | Dijalankan setelah UI berubah |
| `npm run build` | Pending | Dijalankan setelah UI berubah |
| `git diff --check` | Pending | Dijalankan sebelum handoff |
| Chrome DevTools MCP | Pending | Dijalankan untuk admin dan POS |

Jangan menambahkan pekerjaan baru ke checklist ini tanpa persetujuan user.
