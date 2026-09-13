# Work Item: Application UI Baseline

## Status

Ready

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

- [ ] Admin ERP sidebar tampil sebagai layout utama dengan navigasi module
  lengkap tetapi controlled.
- [ ] Menu tanpa route nyata tidak clickable, disabled, atau diberi state
  coming soon tanpa route palsu.
- [ ] POS fullscreen mock dapat dibuka dari route nyata yang disepakati.
- [ ] POS mock menampilkan search item, cart, pilih gudang, customer level,
  diskon, pajak, subtotal/total, dan payment drawer.
- [ ] POS mock memakai data statis lokal untuk UX, bukan transaksi backend real.
- [ ] Sonner toast tersedia dan dipakai pada contoh feedback UI yang relevan.
- [ ] Permission UX memakai `usePermission()` dan `isSuperSystem`.
- [ ] Komponen fitur diletakkan dekat page sesuai struktur modular frontend.
- [ ] Layout desktop/tablet POS dan admin responsive dasar lulus QA browser.
- [ ] `npm run lint`, `npm run build`, dan verifikasi route relevan lulus.

## Dampak Yang Harus Dicek

- [ ] Database/migration: tidak disentuh pada work item ini.
- [ ] Route: route nyata untuk POS mock/admin entry dicek bila ditambah.
- [ ] Permission/policy: backend tetap authority; frontend guard hanya UX.
- [ ] UI/Inertia: terdampak utama.
- [ ] Seeder demo: tidak disentuh.
- [ ] Activity log: tidak relevan untuk mock UI baseline.
- [ ] Soft delete atau mekanisme koreksi: tidak disentuh.
- [ ] Transaksi stok/FIFO/payment/tax/diskon/pricing: hanya mock UI, tidak
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
| 4 | Planned | Bangun POS fullscreen mock realistis | `npm run lint`; `npm run build`; Chrome DevTools MCP |
| 5 | Planned | Rapikan folder modular frontend dan dokumentasi hasil | `git diff --check`; review docs |

## Handoff

- Perubahan sampai Increment 3:
  - Sonner baseline aktif pada root Inertia dan contoh feedback profile.
  - Admin ERP sidebar memakai peta module controlled.
  - Menu module tanpa route nyata tampil disabled/coming soon tanpa URL palsu.
  - Mobile sidebar memiliki title dan description screen-reader.
- Verifikasi sampai Increment 3:
  - `npm run lint` lulus.
  - `npm run build` lulus.
  - `php artisan route:list --except-vendor` lulus dan tetap menunjukkan 21
    route aktif.
  - Chrome DevTools MCP lulus untuk desktop expanded/collapsed dan mobile
    drawer; console bersih setelah reload.
- Risiko terbuka:
  - Route POS mock belum dibuat; akan dikerjakan pada Increment 4.
  - Visual mock POS belum tersedia; belum ada klaim transaksi backend berjalan.

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
