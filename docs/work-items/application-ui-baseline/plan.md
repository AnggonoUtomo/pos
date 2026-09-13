# Implementation Plan: Application UI Baseline

## Scope

Membangun standar awal final UI aplikasi POS: admin ERP sidebar, peta module
controlled, POS fullscreen mock realistis, Sonner toast, permission UX, dan pola
folder frontend modular. Pekerjaan implementasi dilakukan setelah dokumen ini
direview dan disetujui user.

## Increment

| No | Status | Nama | Perubahan | Acceptance | Verifikasi |
| --- | --- | --- | --- | --- | --- |
| 1 | Passed | Audit UI starterkit | Inventarisasi layout, komponen shadcn/ui, route, permission hook, dan kebutuhan Sonner | Target file dan route jelas sebelum coding | `rg`; `php artisan route:list --except-vendor`; `npm list sonner --depth=0` |
| 2 | Passed | UI feedback baseline | Pastikan Sonner tersedia dan `Toaster` dipasang pada root/layout yang tepat | Toast bisa dipakai konsisten untuk CRUD UI | `npm list sonner --depth=0`; `npm run lint`; `npm run build` |
| 3 | Passed | Admin ERP sidebar | Sesuaikan sidebar untuk module Platform, Inventory, Sales, Purchasing, Finance, Reporting dengan item controlled | Menu route nyata clickable; menu belum tersedia disabled/coming soon | `npm run lint`; `npm run build`; Chrome DevTools MCP desktop/mobile |
| 4 | Passed | POS fullscreen mock | Buat page POS mock dengan search item, cart, gudang, customer level, diskon, pajak, payment drawer | Alur kasir realistis dapat dipakai sebagai target UI module bisnis | `npm run lint`; `npm run build`; `php artisan test --filter=PosRouteTest`; Chrome DevTools MCP desktop/tablet bila tersedia |
| 5 | Passed | Folder modular dan polish | Pindahkan/letakkan komponen fitur pada `resources/js/pages/{domain}/{module}/components` dan update docs bila perlu | Struktur frontend sesuai baseline docs | `npm run lint`; `npm run build`; `git diff --check`; review file |
| 6 | Passed | Final verification | Jalankan gate relevan dan catat hasil ke `tasks.md` | Work item siap handoff | `npm run lint`; `npm run build`; `php artisan route:list --except-vendor`; `php artisan test --filter=PosRouteTest`; `git diff --check`; Chrome DevTools MCP desktop/tablet |
| 7 | Passed | Dashboard shell reference polish | Selaraskan admin dashboard dengan pola `SampleUI/dashboard-shell-01` dan referensi shadcnstudio dashboard shell | Admin memiliki top nav, quick action POS, badge accent, dan dashboard operasional pengganti placeholder | `npm run lint`; `npm run build`; `git diff --check`; Chrome DevTools MCP desktop/tablet |
| 8 | Passed | Sidebar footer dan theme toggle | Hapus footer menu sidebar dan tambahkan toggle light/dark pada top nav | Sidebar tanpa footer user menu; theme dapat ditoggle dari top nav | `npm run lint`; `npm run build`; `git diff --check`; Chrome DevTools MCP desktop/tablet |
| 9 | Passed | Collapsed sidebar scroll dan tooltip | Sembunyikan scrollbar sidebar tanpa mematikan scroll dan pastikan tooltip tersedia saat collapsed | Sidebar collapsed tetap bisa discroll; tooltip menu/logo tersedia | `npm run lint`; `npm run build`; `git diff --check`; Chrome DevTools MCP desktop/tablet |

## QA Automated

- Backend focused: tidak wajib kecuali route/controller Laravel berubah.
- Route check: `php artisan route:list --except-vendor`.
- Frontend lint/build: `npm run lint`; `npm run build`.
- Sonner check: `npm list sonner --depth=0` atau bukti package/component yang
  dipakai.
- Chrome DevTools MCP:
  - Admin desktop sidebar expanded/collapsed.
  - Dashboard shell desktop/tablet dengan top nav.
  - POS fullscreen desktop.
  - POS tablet viewport.
  - Console error dan layout overlap.
- Mobile QA POS dilewati untuk fase ini sesuai instruksi user.

## Batas Berhenti

Pekerjaan berhenti ketika baseline UI siap menjadi standar awal final dan semua
acceptance criteria terpenuhi. Logic transaksi real, FIFO, payment posting, dan
integrasi data backend dilaporkan sebagai scope berikutnya.

## Rollback

Karena scope utama frontend, rollback dilakukan dengan revert commit work item.
Jika dependency Sonner ditambahkan, rollback harus mengembalikan perubahan
`package.json`, lock file, dan pemasangan `Toaster`.
