# Tasks: Card Laporan Dan Profil

## Sebelum Mulai

- [x] Baca AGENTS/README, arsitektur/struktur dan dokumen laporan relevan.
- [x] Periksa dashboard, laporan, profil, panduan dan kondisi Git.
- [x] Batasi scope tampilan; tidak mengubah angka/query/izin/aturan backend.

## Increment 1: Koreksi UI Dan Verifikasi

- [x] Buat pemeriksaan terfokus sebelum perubahan UI.
  - Acceptance: UI lama gagal pada ekspektasi empat card bertone.
  - Verification: browser disposable RED lalu GREEN.
- [x] Ubah empat ringkasan laporan menjadi card berwarna seperti dashboard.
  - Acceptance: warna/ikon semantic, angka/detail dan filter tetap benar.
  - Verification: Chrome desktop/tablet, light/dark, periode kosong.
- [x] Tata profil dan sembunyikan hapus akun pribadi.
  - Acceptance: form nama/email, validasi, simpan, Sonner dan panduan bekerja;
    tidak ada kontrol hapus; endpoint/pengaman backend tidak berubah.
  - Verification: browser disposable dan focused ProfileUpdateTest.
- [x] Perbarui panduan dan jalankan gate frontend/coverage/diff check.
- [x] Review, catat bukti, commit dan push; file LSP dikecualikan.

Review: hanya presentasi/metadata panduan dan QA. Tidak ada data contoh pada
ringkasan, penghapusan endpoint, perubahan izin/query atau dependency baru.
Command dan hasil terperinci tersedia pada README pekerjaan ini.
