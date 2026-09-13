# Work Item: Application UI Baseline

## Status

Completed

## Owner Dan Lokasi

- Owner module: lintas module.
- Target kode:
  - `resources/js/components`
  - `resources/js/layouts`
  - `resources/js/pages`
  - `resources/js/hooks`
  - route Laravel/Inertia terkait bila diperlukan
- Target dokumen:
  - `docs/work-items/application-ui-baseline/`

## Kondisi Awal

Laravel starter kit sudah menyediakan layout, komponen shadcn/ui dasar, halaman
auth/settings/dashboard, dan hook `use-permission.ts`. Project POS belum memiliki
standar awal final untuk admin ERP sidebar, POS fullscreen, peta module lengkap
yang controlled, Sonner toast, dan mock alur kasir realistis.

## Scope

- Menetapkan UI baseline sebagai standar awal final, bukan shell sementara.
- Membuat admin ERP sidebar sebagai layout utama admin.
- Menampilkan peta module lengkap secara controlled tanpa membuat route palsu.
- Membuat POS fullscreen mock yang realistis untuk desktop/tablet.
- POS mock memuat search item, cart, pilih gudang, customer level, diskon,
  pajak, dan payment drawer.
- Admin dashboard mengikuti pola dashboard shell dengan sidebar, top nav,
  quick action POS, user menu, badge accent, dan content area operasional.
- Menyiapkan shadcn/ui sebagai default UX dan Sonner toast untuk feedback CRUD.
- Menggunakan `usePermission()` dan `isSuperSystem` untuk UX permission guard.
- Menetapkan pola folder frontend modular:
  `resources/js/pages/{domain}/{module}/components`.
- Menjalankan QA automated dan Chrome DevTools MCP untuk baseline UI.

## Tidak Dikerjakan

- Logic transaksi real.
- Posting stok, FIFO, payment posting, retur, void, atau adjustment.
- Query database item/customer/gudang real.
- Membuat route palsu untuk module yang belum ada.
- Membuat design system paralel di luar shadcn/ui.
- Mengubah struktur `Old-docs/`.

## Acceptance Criteria

- [x] Admin ERP sidebar tampil sebagai layout utama dengan navigasi module
  lengkap tetapi controlled.
- [x] Menu tanpa route nyata tidak clickable, disabled, atau diberi state
  coming soon tanpa route palsu.
- [x] POS fullscreen mock dapat dibuka dari route nyata yang disepakati.
- [x] POS mock menampilkan search item, cart, pilih gudang, customer level,
  diskon, pajak, subtotal/total, dan payment drawer.
- [x] POS mock memakai data statis lokal untuk UX, bukan transaksi backend real.
- [x] Admin dashboard memiliki top nav, quick action POS, user menu, badge
  accent, dan content area operasional.
- [x] Sonner toast tersedia dan dipakai pada contoh feedback UI yang relevan.
- [x] Permission UX memakai `usePermission()` dan `isSuperSystem`.
- [x] Komponen fitur diletakkan dekat page sesuai struktur modular frontend.
- [x] Layout desktop/tablet POS dan admin responsive dasar lulus QA browser.
- [x] `npm run lint`, `npm run build`, dan verifikasi route relevan lulus.

## Dampak Yang Harus Dicek

- [x] Database/migration: tidak disentuh pada work item ini.
- [x] Route: route nyata untuk POS mock/admin entry dicek bila ditambah.
- [x] Permission/policy: backend tetap authority; frontend guard hanya UX.
- [x] UI/Inertia: terdampak utama.
- [x] Seeder demo: tidak disentuh.
- [x] Activity log: tidak relevan untuk mock UI baseline.
- [x] Soft delete atau mekanisme koreksi: tidak disentuh.
- [x] Transaksi stok/FIFO/payment/tax/diskon/pricing: hanya mock UI, tidak
  menjalankan mutation bisnis.

## Dependency Dan Keputusan

- Interview intent disetujui user: UI baseline dibuat matang sebagai standar
  awal final.
- shadcn/ui menjadi default UI/UX.
- Sonner toast wajib untuk feedback CRUD.
- Admin ERP memakai sidebar.
- POS memakai fullscreen desktop/tablet.
- Menu module belum tersedia harus controlled tanpa route palsu.
- Chrome DevTools MCP dipakai untuk QA browser bila tersedia.

## Increment

| Increment | Status | Ringkasan | Verifikasi |
| --- | --- | --- | --- |
| 1 | Passed | Audit UI starterkit dan tentukan route/layout target | `rg`; `php artisan route:list --except-vendor`; `npm list sonner --depth=0` |
| 2 | Passed | Tambah/aktifkan Sonner dan UI feedback baseline | `npm list sonner --depth=0`; `npm run lint`; `npm run build` |
| 3 | Passed | Bangun admin ERP sidebar dengan navigasi module controlled | `npm run lint`; `npm run build`; Chrome DevTools MCP |
| 4 | Passed | Bangun POS fullscreen mock realistis | `npm run lint`; `npm run build`; `php artisan test --filter=PosRouteTest` |
| 5 | Passed | Rapikan folder modular frontend dan dokumentasi hasil | `npm run lint`; `npm run build`; `git diff --check`; review file |
| 6 | Passed | Final verification dan handoff | `npm run lint`; `npm run build`; `php artisan route:list --except-vendor`; `php artisan test --filter=PosRouteTest`; `git diff --check`; Chrome DevTools MCP desktop/tablet |
| 7 | Passed | Polish dashboard shell sesuai referensi `SampleUI/dashboard-shell-01` dan shadcnstudio | `npm run lint`; `npm run build`; `git diff --check`; Chrome DevTools MCP desktop/tablet |
| 8 | Passed | Polish sidebar footer dan theme toggle top nav | `npm run lint`; `npm run build`; `git diff --check`; Chrome DevTools MCP desktop/tablet |

## Handoff

- Perubahan sampai Increment 6:
  - Sonner baseline aktif pada root Inertia dan contoh feedback profile.
  - Admin ERP sidebar memakai peta module controlled.
  - Menu module tanpa route nyata tampil disabled/coming soon tanpa URL palsu.
  - Mobile sidebar memiliki title dan description screen-reader.
  - Route authenticated `pos.index` tersedia pada `/pos`.
  - POS fullscreen mock memakai data lokal untuk search item, cart, gudang,
    customer level, diskon, pajak, dan payment drawer.
  - Komponen POS berada dekat page pada `resources/js/pages/sales/pos`.
  - Tidak ada `resources/js/components/shared` yang dibuat untuk komponen yang
    belum benar-benar reusable.
  - Branding shell admin berubah dari Laravel Starter Kit menjadi POS Modular.
- Verifikasi sampai Increment 6:
  - `npm run lint` lulus.
  - `npm run build` lulus.
  - `php artisan route:list --except-vendor` lulus dan menunjukkan 22 route
    aktif termasuk `pos.index`.
  - `php artisan test --filter=PosRouteTest` lulus.
  - `git diff --check` lulus.
  - Chrome DevTools MCP lulus untuk admin desktop/tablet dan POS
    desktop/tablet; console bersih.
- Risiko terbuka:
  - Mobile QA POS dilewati sesuai instruksi user.
  - Belum ada klaim transaksi backend berjalan; POS masih mock UI.
  - Nilai pada dashboard operasional masih snapshot statis UI baseline, belum
    query dari module transaksi/gudang real.

## Hasil Audit Increment 1

- Layout admin awal tersedia melalui `resources/js/layouts/app-layout.tsx` dan
  `resources/js/layouts/app/app-sidebar-layout.tsx`.
- Sidebar starterkit masih minimal: hanya Dashboard dan footer link starterkit.
- `NavItem` belum memiliki metadata `disabled`, `comingSoon`, `permission`, atau
  grouping module; ini perlu ditambah pada increment admin sidebar.
- Hook `resources/js/hooks/use-permission.ts` sudah tersedia dan memakai
  `isSuperSystem`.
- Komponen shadcn/ui dasar sudah tersedia, tetapi `sonner` belum terpasang.
- Route aktif baru mencakup home, dashboard, auth, dan settings. Belum ada route
  POS fullscreen atau UI Identity.
- Target route yang direkomendasikan untuk implementasi:
  - Admin ERP shell tetap memakai `dashboard` sebagai entry awal.
  - POS fullscreen mock ditambahkan sebagai route nyata authenticated, misalnya
    `pos.index`, tanpa membuat route palsu untuk module lain.

## Hasil Increment 2

- Dependency `sonner` sudah terpasang.
- Komponen `resources/js/components/ui/sonner.tsx` tersedia.
- `Toaster` sudah dipasang pada root Inertia di `resources/js/app.tsx`.
- Contoh pola toast CRUD update dipasang pada halaman profile settings:
  success toast saat profil berhasil diperbarui dan error toast saat validasi
  gagal.
- Chrome DevTools MCP smoke check berhasil membuka aplikasi lokal; route profile
  redirect ke login karena belum authenticated, halaman login render tanpa
  console error.

## Hasil Increment 3

- `NavItem` frontend mendukung `badge`, `comingSoon`, `disabled`, dan
  `permission`.
- `NavMain` menerima group navigasi dan memakai `usePermission()` hanya untuk
  UX guard.
- Sidebar admin menampilkan arah module Platform, Inventori, Penjualan,
  Pembelian, Keuangan, dan Laporan.
- Hanya logo dan Dasbor yang memiliki link nyata ke `/dashboard`.
- Tiga belas item module yang belum tersedia tampil `Segera`, disabled, dan
  tidak memiliki `href`.
- Link footer starterkit bawaan dihapus agar sidebar fokus pada produk POS.
- QA Chrome DevTools MCP:
  - Desktop expanded: group tampil, tidak ada horizontal overflow.
  - Desktop collapsed: sidebar mengecil tanpa horizontal overflow.
  - Mobile drawer: drawer tampil, item disabled tetap non-link, console bersih
    setelah reload.

## Hasil Increment 4

- Route `pos.index` tersedia pada `/pos` di dalam middleware `auth`.
- Sidebar admin mengubah menu POS dari coming soon menjadi link nyata ke
  `/pos` dengan permission UX `sales.pos.invoices.create`.
- Page POS fullscreen berada pada `resources/js/pages/sales/pos/index.tsx`
  tanpa memakai layout admin card/dashboard.
- Komponen POS diletakkan dekat page:
  `resources/js/pages/sales/pos/components`.
- POS mock memakai data lokal untuk item, gudang, customer level, dan multi
  payment; belum melakukan mutation backend.
- Cart menampilkan item, qty, satuan, harga level pelanggan, diskon item,
  subtotal, pajak 11%, dan total.
- Qty mock dibatasi berdasarkan stok base dan rasio satuan agar tidak melebihi
  stok yang tersedia.
- Payment drawer mendukung multi payment mock dan Sonner toast saat pembayaran
  mock disimpan.
- QA desktop/tablet via browser: `BLOCKED`; Chrome DevTools MCP tidak terekspos
  pada sesi ini dan percobaan CDP headless lokal tidak membuka endpoint debug.
  Verifikasi fallback yang lulus: lint, build, route list, dan route feature
  test.

## Hasil Increment 5

- Struktur frontend POS sudah modular:
  - `resources/js/pages/sales/pos/index.tsx`
  - `resources/js/pages/sales/pos/components`
  - `resources/js/pages/sales/pos/mock-data.ts`
  - `resources/js/pages/sales/pos/types.ts`
- Tidak ada folder `resources/js/components/shared`; belum ada komponen POS yang
  dipromosikan menjadi shared karena belum memiliki consumer lintas fitur.
- Branding app shell diganti dari Laravel Starter Kit menjadi POS Modular.
- Label dashboard pada shell admin dirapikan menjadi Dasbor.
- Header layout cadangan dibersihkan dari link starterkit Laravel dan kontrol
  search yang belum berfungsi.
- QA visual browser tetap `BLOCKED` karena Chrome DevTools MCP tidak tersedia
  pada sesi ini. Verifikasi fallback yang lulus: lint, build, review struktur
  file, dan `git diff --check`.

## Hasil Increment 6

- Final verification lulus:
  - `npm list sonner --depth=0`
  - `npm run lint`
  - `npm run build`
  - `php artisan route:list --except-vendor`
  - `php artisan test --filter=PosRouteTest`
  - `git diff --check`
- Chrome DevTools MCP lulus:
  - POS desktop: item bisa ditambahkan ke cart, payment drawer terbuka, tidak
    ada horizontal overflow, input POS memiliki `id` atau `name`.
  - POS tablet: search, cart, total, payment drawer tetap terbaca dan tidak ada
    horizontal overflow.
  - Admin desktop/tablet: branding POS Modular dan label Dasbor tampil, menu POS
    punya link nyata `/pos`, dan tidak ada horizontal overflow.
  - Console bersih setelah QA.
- Mobile QA POS tidak dijalankan sesuai instruksi user.

## Hasil Increment 7

- Admin shell mengikuti pola referensi dashboard shell:
  - Sidebar tetap menjadi navigasi utama.
  - Header admin menjadi sticky top nav dengan breadcrumb, shortcut Dasbor/POS,
    status Gudang Utama, status Finance Lite, dan user menu.
  - Badge sidebar mendukung accent tone untuk membedakan status aktif, segera,
    inventori, retur, dan finance.
- Halaman `dashboard` tidak lagi memakai placeholder starterkit. Dashboard
  menampilkan ringkasan penjualan, transaksi POS, stok menipis, retur pending,
  transaksi terbaru, status gudang, dan baseline operasional yang dijaga.
- Referensi yang dipakai:
  - Local sample: `SampleUI/dashboard-shell-01`.
  - External: `https://shadcnstudio.com/blocks/dashboard-and-application/dashboard-shell`.
- Verifikasi:
  - `npm run lint` lulus.
  - `npm run build` lulus.
  - `git diff --check` lulus.
  - Chrome DevTools MCP desktop/tablet lulus.

## Hasil Increment 8

- Footer user menu pada sidebar dihapus agar navigasi sidebar fokus pada module.
- User menu tetap tersedia pada top nav.
- Top nav memiliki toggle light/dark memakai mekanisme `useAppearance()`
  bawaan starterkit.
- Verifikasi:
  - `npm run lint` lulus.
  - `npm run build` lulus.
  - `git diff --check` lulus.
  - Chrome DevTools MCP desktop/tablet lulus.
