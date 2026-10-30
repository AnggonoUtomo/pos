# Pengaturan Global Dan Pagination Tabel

Status: selesai untuk scope pengaturan/pagination, dengan gap regresi existing
terbuka pada bagian di bawah. Disetujui user: default global 10 baris; pilihan
10/25/50/100. Override sementara per tabel tidak mengubah default toko.
Perubahan jumlah baris kembali ke halaman 1 dengan filter tetap.

Operasional memakai tab vertikal Umum/Cetak/Backup. Cetak, backup manual,
jadwal, permission, version dan audit tetap. Tabel daftar master, akses,
stok/transfer, pembelian/retur, penjualan/retur/shift, pembayaran/Buku Kas dan
posisi stok laporan mendapat kontrol seragam: ukuran, rentang/total, awal,
sebelumnya, nomor halaman dengan ellipsis, berikutnya dan akhir.

Di luar scope: isi transaksi/editor, struk, katalog/keranjang tertahan POS,
ringkasan dashboard, perubahan ledger/transaksi, dependency atau runner OS.
Total/summary laporan dan saldo Buku Kas tidak dibatasi per halaman.

Public boundary Platform/OperationalSettings: TablePreferences (default toko)
dan TablePagination (ukuran read request). Adapter request memvalidasi override,
adapter repository daftar mengonsumsi contract melalui constructor. Tidak ada
Application/Domain yang mengimpor HTTP atau adapter privat module lain.
Preferensi publik aman dibagikan ke user authenticated, bukan secret backup.

Migration hanya menambah table_per_page pada singleton; tanpa reset/seeder
data toko. Demo tidak memerlukan dataset baru. Old-docs dan LSP tidak disentuh.

## Gap Regresi Existing

Gate penuh awal: 834 lulus, 43 gagal, 6801 assertion. Sebagian assertion lama
masih mengasumsikan 25 baris; sudah disesuaikan menjadi default 10 atau override
eksplisit 25 dan diverifikasi ulang terfokus. Gate penuh tidak diklaim hijau.

Gap terpisah: lookup pembelian diterima untuk retur tidak menemukan sumber,
berdampak pada test retur/refund/lifecycle. Tiga ReturnPurchaseLookupTest juga
gagal saat repository PurchaseOrders/Payments/Stock dimuat dari baseline
`c8e64ca` (`git show`, proses PHPUnit terisolasi, tanpa mengganti file source).
Satu test filter tanggal InvoiceHistoryTest juga terulang gagal dengan reader
baseline. Bukan alasan mengubah aturan transaksi atau menghapus test.

User secara eksplisit mengizinkan gap retur dicatat terpisah dan pagination
dituntaskan terlebih dahulu. Tidak ada perbaikan alur retur/tanggal dalam
pekerjaan ini. Gap retur kemudian ditangani atas instruksi user pada
[perbaikan sumber retur pembelian](../purchase-return-source-fix/README.md):
penyebab konversi timezone posted_at/paid_at sudah diperbaiki dan seluruh
regresi retur/refund/lifecycle lulus. Gap filter tanggal InvoiceHistoryTest
tetap terbuka; angka gate awal di atas dipertahankan sebagai bukti saat itu.

## Verifikasi Fitur

- TablePaginationTest: 4 PASS/79 assertion, default/override/input invalid,
  izin/audit/stale version serta 18 endpoint JSON.
- Pemeriksaan pagination terkait sesudah koreksi: 16 PASS/265 assertion.
- Laporan/Buku Kas/preferensi: 6 PASS/134 assertion; summary tetap identik
  meskipun ukuran/halaman berbeda, termasuk halaman kosong dan uang besar.
- Test model pagination JS PASS (ukuran, first/middle/last dan ellipsis).
- Coverage panduan 26 halaman PASS. Typecheck dan ESLint PASS. Build PASS,
  2924 module. Pint scoped dan diff whitespace diperiksa sebelum commit.
- Chrome DevTools MCP BLOCKED: discovery tidak menyediakan tool pada sesi ini.
  Fallback Chrome/Playwright existing tanpa instalasi, SQLite/cache/view
  disposable. Mode QA_GLOBAL_PAGINATION=1 pada workspace-tables-charts-browser:
  tab/keyboard/isian lintas tab, preview cetak/konfirmasi backup (tanpa dump),
  default25 sementara10/50, reset/filter, semua daftar, boundary dan summary,
  desktop1440/tablet1024 light/dark PASS. Tidak ada console error dan fingerprint
  ledger identik. Bukti ignored: mvp-browser-cdd1b49d610c94d0/visual-report.json
  serta screenshot pada storage/framework/testing, sudah ditinjau visual.

## Aktivasi

`php artisan pos:backup --verify` PASS: restore disposable identik, tanpa
menimpa database toko. File backups/pos-20261030-043558-c9c13ce5.sql ignored.
`php scripts/qa/activate-table-pagination-local.php` PASS: migration batch33 Ran;
fingerprint seluruh kolom/data existing identik. Tanpa reset, seed, perubahan
role atau pemasangan pemicu OS. Status migration Operasional sudah diperiksa.
