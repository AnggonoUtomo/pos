# PRD: Manajemen Pengguna

## Status

Selesai

## Masalah Dan Tujuan

Admin belum dapat menambah akun operasional tanpa seeder. Sediakan manajemen
akun internal dari tab pengguna agar akses dapat diberikan/dicabut secara aman
tanpa membuka registrasi publik atau menghapus riwayat operasional.

## Pengguna Dan Requirement

- Super-system atau operator dengan permission per operasi.
- Tambah: nama, email unik ternormalisasi, password awal minimal 12 karakter
  beserta konfirmasi, maksimal 72 byte untuk mencegah truncation bcrypt;
  role opsional dan membutuhkan identity.manage.
- Edit: nama/email; perubahan email membatalkan verification/reset token lama.
- Status: aktif/nonaktif lewat konfirmasi, bukan toggle tanpa konfirmasi.
- Assignment role memakai editor existing; direct grants tetap dipertahankan.
- Akun nonaktif tidak boleh login/menggunakan sesi lama. Aktivasi kembali wajib
  login baru; jangan menghidupkan kembali sesi yang sebelumnya dicabut.
- Anti-escalation, last-super aktif dan audit rollback berlaku di Application,
  bukan hanya frontend/policy; tidak dilewati Gate bypass.
- Password/PII tidak dicatat pada audit; audit profil hanya nama field berubah.

## Di Luar Scope

Reset password admin, kirim email credential/invitation, hapus akun oleh admin,
registrasi publik, migrasi identifier dan QA mobile.

## Acceptance

Mengikuti checklist pada [tasks](tasks.md); scope disetujui user melalui
interview satu pertanyaan sebelum implementasi.
