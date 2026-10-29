# Card Laporan Dan Workspace Profil

Status: selesai dan terverifikasi. Koreksi UI Reporting/OperationalReports dan profil
pribadi starter kit, tanpa perubahan backend/schema/dependency.

Empat ringkasan laporan memakai Card dan tone dashboard existing. Angka,
filter periode/gudang, chart dan izin tetap. Profil mendapat ringkasan identitas,
form yang lebih tertata dan label Indonesia; pembaruan nama/email, validasi,
verifikasi email dan Sonner tetap berjalan. Kontrol hapus akun pribadi tidak
dirender; endpoint serta pengaman backend tidak dihapus/dinonaktifkan.

Tidak menyentuh halaman password/appearance, ledger, data produksi atau LSP.
Panduan profil in-app/Markdown disesuaikan; PDF tidak dibangun ulang.

## Verifikasi

- Browser test awal RED: ringkasan laporan lama belum memiliki empat card.
- `npx tsc --noEmit`, ESLint resources/js dan skrip QA terkait, serta
  `npm run build` PASS (2922 module). Coverage panduan 26 halaman PASS.
- `php artisan test --compact --filter=ProfileUpdateTest`: 5 PASS/21 assertion,
  database disposable; kontrak pembaruan dan penghapusan backend tetap.
- Chrome DevTools MCP BLOCKED: tool tidak tersedia pada discovery sesi ini.
  Fallback Chrome/Playwright existing tanpa instalasi, fixture MVP SQLite dan
  cache/view terpisah; bukan database toko.
- Mode `QA_REPORT_PROFILE=1` pada `scripts/qa/workspace-tables-charts-browser.mjs`
  PASS: empat tone/angka/detail, filter periode kosong, desktop 1440/tablet1024,
  profil tanpa hapus, isian saat membuka panduan, validasi email ganda,
  simpan/reload dan dark. Console bersih; fingerprint ledger tidak berubah.
- Bukti: `storage/framework/testing/mvp-browser-33e19a9f7267bfb5/visual-report.json`
  dan screenshot ignored, ditinjau visual. `git diff --check` PASS.
- Tidak menjalankan migration atau QA ulang seluruh modul karena tidak berubah.
