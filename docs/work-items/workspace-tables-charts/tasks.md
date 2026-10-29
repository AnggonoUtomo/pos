# Tasks: Tabel Workspace Dan Chart

## Sebelum Mulai

- [x] Baca AGENTS, README, arsitektur/struktur dan module Reporting.
- [x] Periksa Git; pertahankan file LSP user dan jangan sentuh Old-docs.
- [x] Pelajari source Satuan, tabel modul, dashboard dan laporan.
- [x] Tetapkan scope UI-only, tanpa dependency/migration/data contoh.

## Increment 1: Tabel

- [x] Rekam baseline browser Satuan/tabel flat dan referensi widget.
  - Acceptance: perubahan berpatokan surface/header/badge nyata, bukan redesign.
  - Verification: screenshot dan inspeksi source/DOM.
- [x] Tambah gaya tabel opt-in dan perbaiki badge plain yang relevan.
  - Acceptance: list modul konsisten; struk, print preview dan editor tidak kena.
  - Verification: browser light/dark representatif, hover, scroll dan empty.
- [x] Commit save point setelah pemeriksaan terkait lulus.

## Increment 2: Chart

- [x] Test rasio exact/zero/angka besar sebelum implementasi helper chart.
  - Acceptance: konversi angka hanya geometri, label nominal tetap string exact.
  - Verification: pemeriksaan Node existing tanpa dependency baru.
- [x] Widget transaksi dan arus pembayaran real pada dashboard/laporan.
  - Acceptance: animasi finite, reduced motion, scope/filter/permission tidak
    berubah; tanpa nilai tren buatan, ringkasan nol jelas.
  - Verification: typecheck/lint/build dan browser desktop/tablet.
- [x] QA perubahan tabel/chart, tooltip keyboard, perubahan filter dan refresh.
  - Acceptance: tidak blank/overflow, fungsi existing tetap ada, console bersih.
  - Verification: DevTools MCP bila tersedia atau fallback Chrome/Playwright.
- [ ] Update dokumentasi/checklist, commit/push dan verifikasi remote.

## Hasil Verifikasi

- Referensi user dibuka melalui web dan Chrome isolated. Render awal blank
  sebelum hydration selesai; load + tunggu singkat menghasilkan dashboard
  batang/donut/aksen lembut. Screenshot referensi ignored.
- Baseline Satuan, invoice, laporan dan dashboard: PASS,
  `storage/framework/testing/mvp-browser-bb1bc6b39c74e1f7`.
- Chrome DevTools MCP: BLOCKED, namespace tidak tersedia pada discovery;
  fallback Playwright existing/channel Chrome, tanpa instalasi package.
- QA utama desktop1440x1000/tablet1024x768: PASS light/dark, surface/header,
  hover, tooltip keyboard, animasi finite, reduced motion, filter periode nol,
  angka sesuai props nyata, console bersih, body tidak overflow. Screenshot
  diperiksa. `storage/framework/testing/mvp-browser-4691d0f38af39751`.
- `node scripts/qa/chart-ratios.mjs`: RED sebelum helper tersedia, lalu PASS
  max/sum, zero/empty, pecahan6, residual persen dan angka > presisi Number.
  Rupiah tidak dihitung ulang memakai float; hanya geometri memakai Number.
- `npx tsc --noEmit`, ESLint file frontend berubah dan `npm run build`: PASS.
- Backend focused `DashboardTest|OperationalReportTest`: 8 PASS/168 assertions.
  Tidak mengulang suite transaksi karena tidak ada perubahan backend/query,
  permission/schema/ledger. Fixture/browser memverifikasi fingerprint readonly.
- Setelah cleanup duplikasi ringkasan pembayaran pada laporan, hanya gate
  frontend terdampak dan QA laporan/reduced-motion/refresh yang diulang.
  PASS: `storage/framework/testing/mvp-browser-7fe8a151cee6d9cd`, periode
  Indonesia, saldo/angka widget utuh, refresh selesai, transisi reduced-motion
  0 detik. Browser/server ditutup pada finally.
- Empty row dan overflow container tabel existing dipertahankan, bukan
  mengganti komponen operasi/CRUD. Opt-in tidak menyentuh struk, print preview
  maupun tabel editor. Tidak mengubah theme sidebar/topnav.
- Save point tabel: `431b490`. `.env.testing` sementara dihapus sesudah QA;
  .env produksi, database toko, dependency, Old-docs dan file LSP tidak diubah.
