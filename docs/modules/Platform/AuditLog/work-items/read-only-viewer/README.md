# Viewer Audit Baca-Saja

Status: implementasi.

Scope mengikuti spesifikasi module: daftar, filter, detail aman, permission dan
navigasi. Tidak mengubah ledger/log existing, .env, role akun atau Old-docs.
PRD ringkas adalah specification.md module karena requirement telah disetujui
user; tidak ada keputusan arsitektur sulit dibalik yang membutuhkan ADR baru.

QA: focused feature untuk authorization/whitelist/filter/pagination/immutability,
frontend gate sekali di akhir, browser desktop/tablet representatif disposable.
Satu gate backend akhir; kegagalan existing di luar scope dicatat terpisah.

## Increment 1

Generator dry-run lalu scaffold aktual memakai module:make Platform AuditLog
--with-tests. RED awal: tujuh test endpoint 404; RED tambahan membuktikan
pencarian snapshot sebelum penghapusan belum tercakup dan angka besar belum
diproyeksikan sebagai string. Sesudah implementasi: 12 PASS/97 assertion pada
Feature/Unit Platform/AuditLog; Pint scoped PASS.

Route list dengan cache testing terisolasi menunjukkan hanya tiga GET/HEAD
index/data/detail. Tidak ada migration baru: schema package dan ULID subject
existing dipakai, histori audit ID numerik tetap dipertahankan. Seeder akses
idempotent tanpa grant; demo skip dan tidak menambah histori buatan.

## Increment 2

- Typecheck `npx tsc --noEmit` PASS; ESLint scoped UI/navigasi/QA PASS.
- Build PASS, 2930 module. Cakupan page-guides PASS, 27 halaman.
- QA_AUDIT_LOG=1 pada workspace-tables-charts-browser memakai fixture SQLite,
  cache/view terisolasi dan credential acak stdin; tidak memakai database toko.
- Chrome DevTools MCP existing bin/stdio isolated: auth, DOM, projection aman
  dan screenshot desktop PASS. Executable diperoleh dari package.json bin,
  bukan main/index.js. Tool dengan pageId memakai ID halaman hasil new_page.
- Chrome/Playwright existing: sidebar, Alt+F/Ctrl+Enter, search literal,
  pagination/ukuran/filter, modul/pengguna/tindakan/tanggal WIB, detail loading,
  kegagalan 503 terkontrol/retry, redaksi, angka besar exact, empty/reset,
  F1/palette, desktop1440/tablet1024 Light/Dark PASS. Hanya console 503 yang
  sengaja diinjeksi dikecualikan; tidak ada error lain.
- Akun non-super dengan permission khusus mendapat 200 pada tiga endpoint;
  akun tanpa izin tidak melihat menu dan mendapat 403 pada ketiganya.
- Screenshot sudah ditinjau: storage/framework/testing/mvp-browser-cf7ef04130cafc0b
  (visual-report.json, audit-mcp-desktop.png dan screenshot daftar/detail).
  Fingerprint seluruh audit dan ledger identik, browser/server ditutup.
- Panduan operator baru LOG-AKTIVITAS.md dan guide lokal tersedia; PDF lama
  tidak dibangun ulang, batas cakupannya dicatat pada indeks panduan.
