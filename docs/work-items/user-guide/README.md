# Panduan Pengguna POS

Scope: dokumentasi pemula dalam Markdown dan PDF, berurutan dari lifecycle
global, per module/lintas module, kemudian pembagian tugas tujuh profil role.
Tanggal penyusunan: 27 Oktober 2026. Acuan source awal: af410c1.

Role tambahan hanya rekomendasi dokumentasi, sesuai konfirmasi user; tidak
membuat role, akun, grant atau perubahan database toko. Tidak mengaktifkan
backlog, mengubah source aplikasi, memasang dependency atau mengakses Old-docs.

Hasil: docs/user-guide dan output/pdf/user-guide, satu buku global dan tujuh
PDF role. PDF dibangun dari Markdown yang sama, bukan naskah terpisah.

Kriteria selesai: alur sesuai source, permission operasional tidak memakai key
legacy, tautan valid, PDF terbaca/tidak terpotong, dan pengiriman Git scoped.

## Hasil

- [Indeks pengguna](../../user-guide/README.md): lifecycle, 22 bagian panduan
  penggunaan dan serah terima, matriks akses, serta tujuh lembar tugas role.
- [PDF global](../../../output/pdf/user-guide/panduan-global-pos.pdf): 22 halaman.
- Tujuh PDF role: masing-masing 3 halaman, ditautkan dari indeks pengguna.
- [Manifest output](../../../output/pdf/user-guide/manifest.json): hash sumber,
  hash PDF, ukuran, jumlah halaman dan hasil pemeriksaan otomatis.
- [Cara membangun ulang](../../../scripts/docs/README.md).

## Verifikasi 27 Oktober 2026

| Pemeriksaan | Hasil |
| --- | --- |
| `php scripts/docs/prepare-user-guide.php --check` | PASS: 11 Markdown, 40 tautan lokal, 42 permission, 0 PDF tertunda |
| `php scripts/docs/prepare-user-guide.php` | PASS: 8 HTML dari Markdown sumber |
| `node scripts/docs/build-user-guide.mjs` | PASS: 8 PDF, hash sumber cocok, target daftar isi valid, overflow horizontal 0 |
| Windows.Data.Pdf melalui `render-user-guide-pages.ps1` | PASS: 43 halaman PDF final dibuka/dirender ke PNG, tidak kosong, hash PDF cocok |
| `php scripts/docs/contact-sheets.php` | PASS: contact sheet seluruh halaman tersedia pada folder QA ignored |
| Inspeksi visual | PASS: 11 contact sheet mencakup 43 halaman; halaman penuh global 2/7/14/21, Kasir 2, Keuangan 3 dibaca untuk TOC/tabel/angka/teks/footer |
| `php -l` kedua script PHP, `node --check`, Pint scoped | PASS |
| Diff/check scope | PASS: dokumen/tool PDF/output saja; file LSP staged user dikecualikan |

Playwright existing dipakai melalui `QA_PLAYWRIGHT_MODULE`; tidak memasang
dependency. Python PDF/Poppler tidak tersedia, sehingga render aktual memakai
Windows.Data.Pdf. Tidak menguji aplikasi melalui DevTools MCP atau mengulang
test/build aplikasi karena tidak ada perubahan runtime/UI/schema. Pemeriksaan
Chrome hanya HTML cetak lokal tanpa jaringan/akun toko.

## Batas Hasil

- Role tambahan hanya profil tugas/rekomendasi izin; tidak di-seed/diberi grant.
- Alur latihan diperiksa hitungannya, tidak dijalankan pada database produksi.
- Dokumen bukan pengesahan UAT operator, printer fisik, tablet atau backup terpisah.
- Tautan referensi PDF di luar bab global mengarah ke Markdown GitHub dan
  memerlukan jaringan/akses repo. Daftar isi dan antarbab global berada di PDF.
- Aplikasi, database, .env, scheduler OS, dependency dan Old-docs tidak diubah.
- File sementara/render berada di `storage/framework/testing/user-guide/`
  yang ignored; tidak ikut commit. PDF final memang disertakan sebagai hasil user.
