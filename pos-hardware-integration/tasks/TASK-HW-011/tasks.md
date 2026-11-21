# Tasks: Akses Produksi Uji Scanner

## Sebelum Coding

- [x] Commit/push TASK-HW-002 selesai, remote main sesuai 96bddfe.
- [x] Status file user dan guard/cache existing diperiksa read-only.
- [x] Susun PLAN exact-file dan batas scanner, bukan printer/gateway.
- [x] Validasi PLAN dan catat fingerprint.
- [x] Pemilik menyetujui PLAN, QA dan aktivasi .env/cache secara eksplisit.
- [x] Validator --require-approved lulus sebelum source/runtime diubah.

## Increment 1: Akses Dan Panduan Scanner

- [x] Tambahkan test production ON/OFF, auth/permission dan compiled route.
  - Acceptance: ON tidak membuka akses publik; OFF tetap 404 termasuk super-system.
  - Verification: red-green Hardware feature/unit tests terisolasi.
- [x] Perbarui guard serta UI, PageGuide dan Markdown operator.
  - Acceptance: akses produksi didukung, default source OFF, status fisik jujur.
  - Verification: typecheck, scoped lint, guides dan build satu kali.
- [x] Jalankan browser production-like disposable dan regresi terdampak.
  - Acceptance: scan/queue/cancel berfungsi, data bisnis identik, POS tetap utuh.
  - Verification: harness existing, fingerprint, focused POS/barcode tests.

## Increment 2: Aktivasi Toko Dan Handoff

- [x] Backup konfigurasi/cache privat dan aktifkan hanya flag hardware.
  - Acceptance: konfigurasi lain dan data toko tidak diubah, tanpa service OS.
  - Verification: backup tersedia, effective flag dan route cache diperiksa.
- [x] Smoke HTTPS login/akses guest dan siapkan rollback.
  - Acceptance: feature ON terverifikasi tetapi tetap membutuhkan login/permission.
  - Verification: HTTP smoke; tidak membuat transaksi/akun uji toko.
- [ ] Lengkapi REPORT, checklist, metadata, commit dan push sesuai izin.
  - Acceptance: PHYSICAL_PENDING, printer/gateway tidak diklaim siap.
  - Verification: check_report, diff allowlist, status Git/file user.

Tidak ada checklist implementasi diisi sebelum pekerjaan benar-benar dilakukan.

## Bukti Ringkas

- PLAN SHA256 tetap 10DD7FB3D65ACEB12A122C7CB7B4BE472FC0FB56E57104F384DAFF5860654D41;
  --require-approved lulus sebelum implementasi.
- RED: dua test gagal404 sesuai guard lama; GREEN guard 4 test/27 assertion.
- Gate Hardware + POS cart/checkout: 39 test/449 assertion PASS.
- Test flag OFF dibuat eksplisit agar tidak bergantung .env toko; subset final
  sesudah aktivasi 4 test/26 assertion PASS. Config default source tetap false.
- Typecheck, scoped ESLint/Pint, build2959, barcode-unit dan guides33 PASS.
- Browser production-like 12 cek PASS; report hardware-scanner-9d2ca02114c52165,
  fingerprint71 tabel identik, errors kosong, serverStopped=true.
- Backup privat3 file hash-identik; hanya POS_HARDWARE_ENABLED berubah di .env.
- config:cache dan route:cache PASS; config production ON dan dua route efektif.
- HTTPS terverifikasi: login200, scanner guest302/login, resolve guest401,
  URL salinan privat404. Tidak ada login atau transaksi uji pada database toko.
- REPORT mencatat batas physical pending dan bukti rollback yang tidak disimulasikan
  dengan mematikan ulang fitur toko.
