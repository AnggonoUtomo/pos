# Tasks: Application UI Baseline

## Sebelum Mulai

- [x] `AGENTS.md` dibaca.
- [x] `docs/README.md`, `docs/ARCHITECTURE.md`,
  `docs/FOLDER-STRUCTURE.md`, `docs/QUALITY.md`, dan `docs/WORKFLOW.md`
  dirujuk sesuai scope.
- [x] Intent user dikonfirmasi dengan `interview-me`.
- [x] Scope dibatasi ke UI baseline standar awal final.
- [x] Non-scope disepakati: belum membuat transaksi real, FIFO, payment posting,
  query data backend item/customer/gudang, atau route palsu.
- [x] Module target disebut ke user: lintas module UI baseline.
- [x] Dampak database, route, permission, UI, seeder, audit, soft delete, stok,
  FIFO, payment, tax, diskon, dan pricing dicek.
- [x] QA automated dan Chrome DevTools MCP direncanakan.
- [x] `Old-docs/` tidak disentuh.

## Increment 1: Audit UI Starterkit Dan Route Aktif

- [x] Audit layout dan komponen UI starterkit.
  - Acceptance: layout admin, sidebar, page starterkit, komponen shadcn/ui, dan
    hook permission terinventarisasi.
  - Verification: `rg` audit UI.
- [x] Audit route aktif.
  - Acceptance: route yang benar-benar ada tercatat; tidak ada asumsi route
    palsu untuk module yang belum tersedia.
  - Verification: `php artisan route:list --except-vendor`.
- [x] Audit status Sonner.
  - Acceptance: diketahui apakah package/component Sonner sudah tersedia.
  - Verification: `npm list sonner --depth=0`.
- [x] Tentukan target route awal untuk coding berikutnya.
  - Acceptance: admin tetap memakai `dashboard` sebagai entry awal; POS mock
    direkomendasikan memakai route nyata authenticated `pos.index`.
  - Verification: review manual hasil audit.
- [x] Catat gap UI baseline.
  - Acceptance: gap NavItem, disabled/coming soon, Toaster, komponen Sonner, dan
    route POS tercatat.
  - Verification: review manual catatan audit pada dokumen ini.

## Increment 2: Sonner Toast Baseline

- [x] Pastikan dependency Sonner tersedia.
  - Acceptance: `sonner` terdaftar pada package dependency, atau ada alasan
    eksplisit bila memakai mekanisme lain.
  - Verification: `npm list sonner --depth=0`.
- [x] Tambahkan komponen shadcn/ui Sonner bila belum ada.
  - Acceptance: file UI Sonner tersedia pada lokasi shadcn/ui project.
  - Verification: review file dan `npm run build`.
- [x] Pasang `Toaster` pada root/layout yang tepat.
  - Acceptance: toast dapat dipanggil dari page/layout tanpa pemasangan ulang per
    fitur.
  - Verification: `npm run build`.
- [x] Tambahkan contoh pola toast CRUD untuk baseline UI.
  - Acceptance: ada contoh success/error toast yang akan menjadi pola module
    CRUD berikutnya.
  - Verification: `npm run lint` dan review manual.

## Increment 3: Admin ERP Sidebar Controlled

- [x] Perluas tipe navigasi frontend.
  - Acceptance: `NavItem` mendukung metadata yang dibutuhkan seperti permission,
    disabled, coming soon, badge, dan/atau children tanpa memaksa route palsu.
  - Verification: `npm run build`.
- [x] Susun peta navigasi module.
  - Acceptance: Platform, Inventory, Sales, Purchasing, Finance, dan Reporting
    tampil sebagai arah produk.
  - Verification: review manual sidebar.
- [x] Implement item controlled untuk module yang belum memiliki route nyata.
  - Acceptance: item tanpa route nyata disabled/coming soon dan tidak clickable
    ke URL palsu.
  - Verification: Chrome DevTools MCP dan review DOM/link.
- [x] Gunakan permission UX.
  - Acceptance: `usePermission()` dan `isSuperSystem` dipakai hanya untuk UX;
    backend tetap authority.
  - Verification: review source dan `npm run build`.
- [x] Verifikasi layout admin desktop dan responsive dasar.
  - Acceptance: sidebar expanded/collapsed dan mobile drawer tidak overlap.
  - Verification: Chrome DevTools MCP.

## Increment 4: POS Fullscreen Mock Realistis

- [ ] Tambahkan route nyata authenticated untuk POS mock.
  - Acceptance: route bernama `pos.index` tersedia dan tidak menggantikan route
    transaksi real masa depan.
  - Verification: `php artisan route:list --except-vendor`.
- [ ] Buat page POS fullscreen.
  - Acceptance: page memakai layout fullscreen, bukan admin card/dashboard
    layout.
  - Verification: Chrome DevTools MCP desktop/tablet.
- [ ] Tambahkan search item mock.
  - Acceptance: kasir melihat area pencarian item yang jelas dan ergonomis.
  - Verification: review manual UI dan Chrome DevTools MCP.
- [ ] Tambahkan cart mock.
  - Acceptance: cart menampilkan item, qty, satuan, harga, diskon, subtotal, dan
    total.
  - Verification: review manual UI.
- [ ] Tambahkan kontrol gudang dan customer level.
  - Acceptance: gudang dan customer level terlihat sebagai bagian alur POS sejak
    awal.
  - Verification: review manual UI.
- [ ] Tambahkan diskon, pajak, dan payment drawer mock.
  - Acceptance: drawer payment menampilkan multi payment secara mock tanpa
    mutation backend.
  - Verification: Chrome DevTools MCP.

## Increment 5: Folder Modular Frontend Dan Polish

- [ ] Letakkan komponen fitur dekat page.
  - Acceptance: komponen POS berada di
    `resources/js/pages/sales/pos/components` atau path modular yang disepakati.
  - Verification: review struktur file.
- [ ] Batasi shared components.
  - Acceptance: `resources/js/components/shared` hanya dibuat bila ada komponen
    lintas fitur yang benar-benar stabil.
  - Verification: review struktur file.
- [ ] Rapikan import dan naming frontend.
  - Acceptance: technical naming tetap English, label UI Bahasa Indonesia.
  - Verification: `npm run lint` dan review manual.
- [ ] Cek tidak ada visual overlap utama.
  - Acceptance: admin dan POS tidak memiliki teks/tombol saling tindih pada
    viewport target.
  - Verification: Chrome DevTools MCP.

## Increment 6: Final Verification Dan Handoff

- [ ] Jalankan lint frontend.
  - Acceptance: lint selesai tanpa error.
  - Verification: `npm run lint`.
- [ ] Jalankan build frontend.
  - Acceptance: production build selesai tanpa error.
  - Verification: `npm run build`.
- [ ] Jalankan route check.
  - Acceptance: route POS/admin yang ditambahkan muncul sesuai nama dan tidak ada
    route palsu.
  - Verification: `php artisan route:list --except-vendor`.
- [ ] Jalankan whitespace check.
  - Acceptance: tidak ada whitespace error.
  - Verification: `git diff --check`.
- [ ] Jalankan Chrome DevTools QA.
  - Acceptance: admin desktop/mobile dan POS desktop/tablet lulus atau alasan
    BLOCKED dicatat.
  - Verification: Chrome DevTools MCP.
- [ ] Update hasil verifikasi dan status work item.
  - Acceptance: checklist ini berisi command, hasil, risiko, dan gap terbuka.
  - Verification: review manual dokumen.

## Hasil Verifikasi

| Command | Hasil | Catatan |
| --- | --- | --- |
| `rg` audit UI | PASS | Layout starterkit, route usage, hook permission, dan Sonner references terinventarisasi |
| `php artisan route:list --except-vendor` | PASS | 21 routes: home, dashboard, auth, settings; increment ini tidak menambah route dan belum ada POS route |
| `npm list sonner --depth=0` | PASS | `sonner@2.0.8` terpasang |
| `npm run lint` | PASS | ESLint selesai tanpa error setelah Increment 3 |
| `npm run build` | PASS | Vite production build selesai setelah Increment 3 |
| `git diff --check` | PASS | Tidak ada whitespace error |
| Chrome DevTools MCP | PASS | Desktop expanded/collapsed dan mobile drawer dicek; hanya logo dan Dasbor memiliki `href`, 13 item coming-soon disabled tanpa URL palsu, console bersih setelah reload |

Jangan menambahkan pekerjaan baru ke checklist ini tanpa persetujuan user.
