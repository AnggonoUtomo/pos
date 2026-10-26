# Tasks: Panduan Pengguna

## Sebelum Mulai

- [x] AGENTS/README/MODULES dan source UI/controller terkait dibaca.
- [x] User mengonfirmasi role tambahan hanya dokumentasi.
- [x] Alat existing dipastikan: PHP CommonMark, Playwright/Chrome, Windows.Data.Pdf.
- [x] Scope tidak menyentuh aplikasi/database/Old-docs/file LSP user.

## Increment 1: Naskah

- [x] Lifecycle global dan urutan belajar per module.
  - Acceptance: persiapan sampai akhir hari; latihan CCTV relasional dengan angka konsisten.
  - Verification: review langkah dan perhitungan latihan, tanpa transaksi pada database toko.
- [x] Panduan module dan alur lintas module, contoh serta penanganan masalah.
  - Acceptance: prasyarat, langkah, hasil dan pengaman setiap tahap tersedia.
  - Verification: cocokkan label/action UI; pisahkan draft, posting stok dan penyelesaian uang.
- [x] Tujuh panduan role dan rekomendasi permission yang nyata.
  - Acceptance: tugas harian, serah terima, checklist dan batas akses; tanpa grant database.
  - Verification: 42 key pada naskah ditemukan di authorization controller aktif.
- [x] Bedakan fitur tersedia, keterbatasan dan backlog; review/link check.
  - Acceptance: tidak menjanjikan tempo, shift, approval, karantina atau UAT selesai.
  - Verification: 11 Markdown, 40 tautan lokal valid, git diff --check lulus.

## Increment 2: PDF Dan Handoff

- [x] Render satu PDF global dan tujuh PDF role dari Markdown.
  - Acceptance: berkas nyata, judul/daftar isi/footer dan sumber naskah yang sama.
  - Verification: Chrome PDF 8 output; hash sumber cocok, overflow 0, daftar isi valid.
- [x] Render halaman PDF ke PNG dan periksa visual.
  - Acceptance: teks/tabel terbaca, tidak terpotong/bertumpuk, tidak ada halaman kosong.
  - Verification: native PDF 43 halaman; 11 contact sheet dan enam halaman penuh diperiksa.
- [x] Verifikasi link, permission, konsistensi sumber/PDF dan diff scoped.
  - Acceptance: tautan lokal/output lengkap; permission nyata; tidak menyertakan file user.
  - Verification: 40 tautan, 42 key, manifest SHA-256, sintaks/Pint dan staged diff check.
- [ ] Catat hasil, commit dan push tanpa file user.
  - Acceptance: laporan hasil tersedia, dua increment tersimpan, remote sama dengan HEAD.
  - Verification: commit scoped, push origin main, verifikasi SHA remote dan divergence 0/0.
