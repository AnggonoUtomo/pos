# Membangun Panduan PDF

Tool dokumentasi saja, tanpa bootstrap Laravel, koneksi database, akses akun,
perubahan aplikasi, atau pemasangan scheduler/runner OS. Naskah ada pada
`docs/user-guide/`; hasil yang dibagikan ada di `output/pdf/user-guide/`.
Tanggal edisi adalah tanggal penyusunan naskah, bukan tanggal build otomatis.

## Prasyarat Existing

- PHP dengan `vendor` project, CommonMark, DOM; GD untuk contact sheet.
- Node, Chrome dan Playwright yang sudah tersedia. Jika Playwright bukan
  dependency lokal, arahkan `QA_PLAYWRIGHT_MODULE` ke module existing.
- Windows PowerShell 5.1/Windows.Data.Pdf untuk render PDF ke PNG di PC ini.
  Ini hanya command QA sekali jalan dengan jendela tersembunyi, bukan runner
  aplikasi atau task otomatis. Pada Linux dapat memakai Poppler yang tersedia.

Tidak ada pemasangan dependency oleh command berikut. Jalankan dari root project.

## Langkah

```powershell
php scripts/docs/prepare-user-guide.php --check
php scripts/docs/prepare-user-guide.php

# Hanya bila Playwright tidak terpasang sebagai dependency lokal:
$env:QA_PLAYWRIGHT_MODULE = 'C:/lokasi-existing/node_modules/playwright-core'
node scripts/docs/build-user-guide.mjs

powershell.exe -NoLogo -NoProfile -NonInteractive -WindowStyle Hidden -ExecutionPolicy Bypass -File scripts/docs/render-user-guide-pages.ps1
php scripts/docs/contact-sheets.php

php scripts/docs/prepare-user-guide.php --check
```

Periksa contact sheet dan halaman penuh di
`storage/framework/testing/user-guide/`. Folder intermediate ini diabaikan Git.
PNG QA bukan pengganti delapan PDF final. Jika layout berubah, bangun/render
ulang output terdampak sebelum menyatakan lulus.

## Yang Diverifikasi

1. CommonMark memproses Markdown, DOM memeriksa tautan lokal dan menyusun daftar
   isi; tidak memakai parser Markdown buatan sendiri.
2. Key permission dalam naskah dicocokkan dengan literal authorization pada
   controller aktif, bukan key legacy seeder. Ini pemeriksaan keberadaan key,
   bukan pengujian hak akun atau bukti isi database.
3. Chrome mencetak HTML lokal tanpa jaringan, mengecek overflow horizontal dan
   target daftar isi. Tautan antarbab global berada di PDF; referensi lain menuju
   Markdown repo GitHub dan memerlukan koneksi/akses repo.
4. SHA-256 memastikan naskah tidak berubah sejak prepare dan PDF tidak berubah
   sebelum dirender. `manifest.json` final mencatat hash, ukuran dan jumlah halaman.
5. Windows.Data.Pdf membuka PDF final dan merender semua halaman; sampling
   piksel menolak halaman kosong. Inspeksi manusia tetap wajib untuk pemotongan
   vertikal, kepadatan tabel dan keterbacaan yang tidak dibuktikan oleh DOM.

Setelah mengubah naskah, jalankan ulang prepare/build/render. Jangan mengubah
PDF terpisah dari Markdown. Jumlah halaman bisa berbeda antarversi Chrome/font;
periksa ulang visual saat lingkungan berubah.
