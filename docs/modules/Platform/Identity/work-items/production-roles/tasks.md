# Tasks: Role Produksi

## Sebelum Mulai

- [x] AGENTS, arsitektur, struktur, Quality dan dokumen Identity/panduan dibaca.
- [x] User menyetujui supervisor menangani tindakan kritis dan refund keuangan.
- [x] Role lokal diperiksa read-only, tidak menampilkan akun/credential.
- [x] Tidak mengubah assignment akun, demo, role test, Old-docs atau file LSP user.

## Increment 1: Seeder Dan Pengaman

- [x] Tambahkan test sebelum implementasi.
  - Acceptance: least privilege, idempotensi, guard web, preservasi akun/grant dan rollback.
  - Verification: test gagal karena seeder belum ada, lalu lulus setelah implementasi.
- [x] Tambahkan seeder produksi module dan matriks tujuh role.
  - Acceptance: tidak membuat akun/permission bebas; audit atomic dan konflik fail-closed.
  - Verification: focused feature test, Pint dan git diff --check.

## Increment 2: Aktivasi Dan Handoff

- [x] Verifikasi regresi otorisasi backend.
  - Acceptance: bypass super-system tetap benar, batas kasir/gudang/finance benar.
  - Verification: test Identity dan suite backend; frontend/browser hanya bila UI berubah.
- [x] Terapkan seeder ke MySQL toko dan verifikasi ulang.
  - Acceptance: tujuh profil tersedia; rerun no-op; akun/pivot/direct grant/data bisnis tetap.
  - Verification: fingerprint sebelum/sesudah dan matriks permission aktual.
- [x] Dokumentasikan matriks dan cara assignment.
  - Acceptance: administrator dapat memilih role tanpa menganggap pengguna sudah diganti.
  - Verification: review dokumentasi dan tautan, tidak menyatakan role usulan lama otomatis aktif.
- [x] Commit dan push scoped.
  - Acceptance: hanya source/test/dokumen terkait; file staged user tidak ikut.
  - Verification: SHA remote sama dengan HEAD dan divergence 0/0.
