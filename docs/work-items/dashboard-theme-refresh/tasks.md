# Tasks: Penyegaran Theme Dashboard

## Sebelum Mulai

- [x] AGENTS/README/QUALITY dan shell/token existing dipelajari.
- [x] Referensi light/dark dibuka di browser sampai konten selesai dimuat.
- [x] Scope user/kontrak desain dicatat; file LSP dan Old-docs tidak disentuh.
- [x] QA config/route cache dipisahkan dari cache produksi existing.

## Increment 1: Entry Aplikasi

- [x] Test RED root guest/login dan authenticated/dashboard.
  - Acceptance: tidak ada welcome, auth/starterkit/registrasi tetap sama.
  - Verification: focused redirect/auth test pada SQLite disposable.
- [x] Ubah route root dan hapus page welcome yang tidak digunakan.
- [x] Verifikasi save point tanpa memasukkan file user; commit setelah gate lulus.
  - RED2 gagal karena root200 welcome; GREEN redirect/auth6 test21 assertion.

## Increment 2: Tampilan Shell Dan Workspace

- [x] Terapkan token light/dark dan aksen semantic berlapis.
- [x] Rapikan sidebar/header/logo/command trigger serta login/dashboard.
  - Acceptance: semua menu dan guard tetap; tidak membuat data/kontrol palsu.
- [x] Pertahankan tooltip, sidebar scroll, mode/accent persistence dan shortcut.
- [x] Lint/typecheck/build dan browser desktop/tablet alur berubah lulus.
  - Verification: dashboard nyata, theme, collapse, no hover request, scroll,
    command palette, master dialog, POS; console/overflow/overlap diperiksa.
- [x] Update route cache produksi dan smoke root/login/asset HTTPS.
- [x] Catat hasil dan review scope; commit/push setelah pemeriksaan akhir.

## Hasil Verifikasi

- Test redirect/auth/dashboard: 13 lulus, 178 assertion pada SQLite disposable.
- Prettier scoped, ESLint seluruh TSX yang berubah, `npx tsc --noEmit`,
  `npm run build` (2757 module), dan `git diff --check`: PASS.
- Setelah koreksi warna icon aktif, ESLint file terkait dan build diulang;
  tidak mengulang test backend yang source behavior-nya tidak berubah.
- `node scripts/qa/mvp-checkout-devtools.mjs --ui-refresh-only`: PASS memakai
  executable Chrome DevTools MCP existing, tanpa instalasi dependency.
- Browser 1440x1000 dan 1024x768: login, empat surface metric berbeda pada
  light/dark, invoice nyata, accent Forest bertahan, command palette/shortcut,
  dialog barang, sidebar scroll/tooltip/tanpa hover request, header glass,
  quote POS dan dialog metode pembayaran. Tidak melakukan posting.
- Snapshot aksesibilitas DevTools tidak memuat seluruh teks tooltip/label
  dropdown; harness memakai DOM dan screenshot untuk bagian tersebut. Harness
  juga menunggu metode pembayaran dan toast selesai sebelum memotret/navigasi.
- Console error kosong, overflow dokumen tidak ditemukan, batas sidebar/header
  tidak bertumpuk, dan fingerprint ledger fixture tidak berubah.
- Artefak ignored: `storage/framework/testing/mvp-browser-8856e3a45eb60f3d/`,
  termasuk `ui-refresh-report.json` dan screenshot yang diperiksa visual.
- Route cache telah diperbarui; HTTPS produksi root302 ke login, login200,
  aset build200, verifikasi TLS0. Tidak mengubah `.env` atau data toko.

## Batas Pekerjaan

Tidak mengubah aturan transaksi, permission, database/migration, seeder,
konfigurasi produksi atau scheduler. Pending UAT/printer/tablet fisik dan
salinan backup terpisah tetap ditunda sesuai instruksi user. Old-docs dan
file LSP user tidak termasuk commit.

## Increment 3: Konsistensi Dark Dan Urutan Platform

- [x] Konfirmasi user: dark netral seperti Stone, warna hanya pada aksen;
  light dan Stone dipertahankan.
- [x] Rekam baseline warna light semua tema dan dark Stone sebelum koreksi.
- [x] Terapkan surface dark netral bersama tanpa mengubah warna aksen.
- [x] Pindahkan Operasional tepat di bawah Pengaturan toko; guard tetap.
- [x] Verifikasi baseline light/Stone identik, surface dark konsisten,
  screenshot desktop/tablet, console, lint/typecheck/build dan diff scoped.
- [x] Catat hasil dan review scope; commit/push setelah gate terkait lulus.

### Hasil Increment 3

- Baseline computed color sebelum koreksi:
  `storage/framework/testing/mvp-browser-f41a70837c488743/dark-theme-report.json`.
- RED: default dark background masih RGB22/27/35, bukan netral RGB28/28/28.
- GREEN: seluruh token light 11 tema dan dark Stone identik dengan baseline;
  surface utama 10 tema dark lainnya netral/konsisten, primary masing-masing
  tema tidak berubah. Guard, route dan behavior sidebar tidak diubah.
- Chrome DevTools MCP:
  `node scripts/qa/mvp-checkout-devtools.mjs --dark-theme-only`, dengan
  `QA_THEME_BASELINE` menunjuk laporan baseline dan executable existing via
  `QA_DEVTOOLS_ENTRY`: PASS. Baseline direkam dengan `--dark-theme-baseline`.
- Screenshot desktop1440x1000 Default/Forest/Stone dan tablet1024x768 collapse
  diperiksa visual; urutan Platform sesuai, tidak ada overflow/console error,
  dan fingerprint ledger fixture identik. Tidak memakai data toko.
- Bukti ignored:
  `storage/framework/testing/mvp-browser-32aa28a1136e6e8b/dark-theme-report.json`.
- Prettier CSS/sidebar, ESLint sidebar, `npx tsc --noEmit`, build2757 module,
  dan `git diff --check`: PASS. Test backend/transaksi sebelumnya tidak diulang
  karena tidak ada perubahan source behavior backend/transaksi.
