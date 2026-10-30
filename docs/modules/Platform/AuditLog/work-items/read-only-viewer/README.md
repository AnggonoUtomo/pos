# Viewer Audit Baca-Saja

Status: selesai untuk scope AuditLog. Gate project tidak diklaim seluruhnya
hijau karena satu kegagalan existing dan advisory dependency di luar scope.

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

## Penutupan Dan Aktivasi

- Gate penuh sekali: `php artisan test --compact`, 897 PASS, 1 FAIL,
  7361 assertion. FAIL existing InvoiceHistoryTest:95 pada filter tanggal WIB;
  tetap di luar scope dan tidak diubah. Tidak ada kegagalan AuditLog.
- Review akhir melengkapi label module Harga jual/Satuan barang dan field aman
  amount/conversion_ratio serta ID relasi/barcode. Setelah perubahan ini hanya
  pemeriksaan terdampak diulang: 12 focused PASS/99 assertion, typecheck,
  ESLint detail, build 2930 module PASS. Layout/alur browser tidak berubah;
  bukti browser sebelumnya dipakai kembali.
- `php scripts/qa/activate-audit-log-local.php` PASS dua eksekusi: catalog
  idempotent tanpa grant, search JSON/opsi/detail MySQL baca-saja, route cache,
  fingerprint seluruh tabel/baris existing identik (cache/session dikecualikan).
  Tidak ada migration baru atau koreksi audit historis.
- Cached route:list memperlihatkan tiga GET/HEAD. migrate:status exit0, tidak
  ada Pending. HTTPS tanpa melewati validasi sertifikat: index anonymous302,
  data anonymous401, sesuai auth. E2E akun berizin diverifikasi di disposable.
- Pint scoped dan git diff --check PASS. Tidak ada dependency, .env, role/grant,
  log/transaksi lama, Old-docs, LSP user, scheduler atau service OS yang diubah.
- Commit backend `6fb860d`, UI/QA `325551c`; penutupan disimpan terpisah,
  kemudian seluruh work item dipush. File LSP user tidak masuk commit.

## Risiko Di Luar Scope

- Gap test tanggal dan advisory qs/symfony/mime yang ditemukan saat pekerjaan
  ini kemudian ditutup atas instruksi user pada
  [perbaikan tanggal dan dependency](../../../../../work-items/invoice-date-and-dependency-fix/README.md).
  Regresi penuh terbaru 933 PASS; audit npm/Composer bersih. Hasil gate awal
  di atas dipertahankan sebagai bukti pada saat pekerjaan AuditLog.
- Nilai sebelum/sesudah hanya ditampilkan bila memang pernah dicatat. Nama
  actor berasal dari akun saat ini; causer/subject yang hilang tidak dihidupkan
  kembali. Log tanpa field aman dapat memiliki detail kosong.
