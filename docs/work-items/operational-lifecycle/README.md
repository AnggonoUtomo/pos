# Dataset Lifecycle Operasional

Persetujuan 27 Oktober 2026: seluruh master dan transaksi operasional lama pada
database aktif dihapus untuk diganti dataset pembelajaran CCTV/kelistrikan.
**Izin penggantian hanya sekali**, bukan fitur reset operasional umum.

Tetap dipertahankan: pengguna/password, role/permission/assignment, pengaturan
toko/operasional, backup/status backup, audit lama, session dan metadata framework.
Backup dan restore disposable wajib lulus sebelum penggantian. Tidak memakai
migrate:fresh, TRUNCATE atau mematikan foreign key.

Seeder baru tidak menghapus data: hanya mengisi database bisnis kosong secara
atomic dan memakai marker audit untuk rerun no-op. Penggantian satu kali lewat
helper terpisah yang terikat fingerprint dataset lama dan marker permanen audit.
Data posted baru dibuat lewat use case yang sama dengan aplikasi, bukan insert
ledger/saldo/FIFO secara manual. Root seeder hanya komposisi bootstrap lintas
module; master seeder tetap berada pada Database/Seeders module pemilik.

Seeder demo lama tetap sebagai fixture regression testing, tidak dipakai untuk
pengisian database ini. Semua data bisnis lama, termasuk prefix DEMO/QA, dihapus.
Old-docs, role produksi, UI dan file user tidak disentuh.

## Verifikasi Implementasi

- RED: 4 error (jenis diskon awal tidak sesuai contract dan helper replacement
  belum ada). Jenis diskon nominal dikoreksi menjadi fixed sesuai domain existing.
- Focused SQLite: 8 test / 85 assertion PASS, termasuk fixture lama populated
  dengan pembelian/penjualan/retur/refund/transfer, preservation, rollback audit,
  fingerprint mismatch, protected FK, seed no-op dan larangan reset kedua.
- Full backend: 835 test / 6754 assertion PASS, satu kali gate lengkap.
- Pint scoped: PASS untuk seeder, helper dan test terkait.
- `pos:backup --verify`: PASS restore disposable identik, file
  `backups/pos-20261027-072715-a4bd26a3.sql` (ignored).
- `.env` toko tetap tidak diubah. Test memakai .env.testing sementara berisi
  SQLite/array dan APP_KEY acak pada proses; file sementara sudah dibersihkan.

## Status Aktivasi: Aktif

Penggantian **satu kali selesai** pada production/MySQL, 27 Oktober 2026.
Fingerprint lama sebelum penggantian tetap cocok dengan persetujuan:
`6158fd7712840fb6d48f2254006992c0eadab342dca5de697c035f1a613e4c63`.
Seluruh 46 tabel bisnis lama dibersihkan transactional, lalu dataset baru dibuat.
Enam belas tabel protected termasuk row audit lama identik. Akun tetap 2 dan
role tetap 8; tidak mengganti password, assignment, pengaturan, backup atau OS.

Gangguan MySQL sebelumnya diselesaikan dengan Start oleh user. Database QA
orphan `pos_lifecycle_qa_5f8dda96fdcad100` sudah dibersihkan dengan guard nama
dan target bukan database toko. Seluruh database sementara run berikutnya
dibersihkan pada finally. Tidak ada perubahan server/.env untuk mengatasi gangguan.

## Verifikasi Lanjutan MySQL

- Pemeriksaan native menemukan Schema::getTables tanpa scope ikut menampilkan
  tabel proyek lain. Diperbaiki menggunakan getCurrentSchemaListing; regresi RED
  lalu GREEN terfokus **9 test / 86 assertion** PASS. Tidak membaca/mengubah row
  proyek lain. Full suite 835/6754 di atas adalah hasil sebelum perbaikan sempit
  ini; hanya gate yang terdampak diulang sesuai kebijakan project.
- Pembandingan hasil JSON pada harness dinormalisasi menggunakan Arr::sortRecursive
  karena MySQL dapat mengubah urutan key object. Fingerprint database tetap exact,
  termasuk audit; tidak melemahkan pemeriksaan row/no-op.
- `php scripts/qa/operational-lifecycle-mysql.php`: PASS schema disposable,
  replacement fixture populated ledger/FIFO/payment, protected, rekonsiliasi
  exact, rerun no-op dan reset kedua ditolak. Semua source final terkait Pint PASS.
- `php scripts/qa/replace-operational-learning-once.php --apply-once`: PASS satu
  kali pada database toko. Laporan ignored:
  storage/framework/testing/operational-lifecycle/activation.json.
- Seeder dijalankan ulang pada database aktual: fingerprint 46 tabel bisnis dan
  jumlah audit tetap identik. Percobaan helper --apply-once kedua ditolak dengan
  LogicException/exit 1 sebelum penghapusan; ini hasil pengaman yang diharapkan.
- Readonly saldo/report: Kamera Utama 3 Unit, Cadangan 2 Unit; MCB 24 Unit;
  NVR 4 Unit; NYM 97,5 Meter; RJ45 100 Buah; UTP 305 Meter. SKU bukan LAT- = 0.
- Total penjualan Rp1.138.650, nilai stok Rp8.210.000, selisih arus uang keluar
  bersih Rp1.411.350. Semua sama dengan expected; bukan saldo kas/laba.
- Tidak mengulang build/browser/tema atau migration database toko: tidak ada
  perubahan UI/runtime/schema. Data lifecycle diperiksa lewat use case laporan
  dan query readonly setelah aktivasi. UAT operator/printer tetap terpisah.

## Nomor Dokumen Aktual

Gunakan pencarian nomor pada halaman module, jangan mem-posting ulang dokumen
yang sudah selesai. Empat draft sengaja disisakan untuk latihan lanjutan.

| Tahap | Nomor Dokumen | Keadaan |
| --- | --- | --- |
| Stok awal | SA-01M5Y55YEMN6DRT11YGA8PF6GD | Posted |
| Pembelian kamera | PO-01M5Y55YG9TFJTJFCJ262BQ74M | Diterima dan lunas |
| Jual Installer | POS-01M5Y55YKA0BB18N2H96MX37P5 | Lunas Rp900.000 |
| Jual Eceran | POS-01M5Y55YN0MJPQ4WWQKF7S6XSR | Lunas Rp38.850 |
| Jual Grosir | POS-01M5Y55YPD8ZTVNJA5A8VBYN26 | Lunas Rp199.800 |
| Retur pelanggan | SR-01M5Y55YQFT7EG988MV0C4DH6H | Barang diterima, refund Rp450.000 selesai |
| Retur supplier | PR-01M5Y55YVSWGAE6QNCQQW7ZF65 | Barang diserahkan, uang Rp300.000 diterima |
| Transfer kamera | TR-01M5Y55Z075EBNG3C4WHV7JZRD | Posted |
| Koreksi kamera | AD-01M5Y55Z1T6ACYKXB70RQPKW85 | Posted keluar 1 Unit |
| Draft pembelian MCB | PO-01M5Y55Z2HYTP7Y7MCXFJ9M1F5 | Draft |
| Draft retur pelanggan | SR-01M5Y55Z2RQ8T5G6RXQSX5X0FD | Draft |
| Draft retur supplier | PR-01M5Y55Z2ZBCG6VP58APT1HBYV | Draft |
| Draft transfer UTP | TR-01M5Y55Z395KVPZR7XM7JVEVBD | Draft |

Panduan langkah dan angka lengkap: [lifecycle latihan](../../user-guide/LIFECYCLE-LATIHAN.md).

## Pengaman Penggantian

Class replacement memakai DELETE transactional dengan foreign key tetap aktif.
Satu link nullable stock_receipt_lines.origin_fifo_layer_id dikosongkan sebelum
penghapusan untuk memutus siklus provenance FIFO -> movement -> receipt line;
semuanya rollback jika gagal. Tabel di luar allowlist yang merujuk bisnis
menyebabkan penolakan. Seluruh row protected termasuk audit lama dibandingkan
fingerprint sesudah pengisian. Tidak ada TRUNCATE atau reset akun.

Seeder root adalah bootstrap composition; master tetap dibuat oleh seeder
module masing-masing. Tidak menambahkan dependency konkret lintas module pada
runtime Application/Domain. Seeder lama tetap fixture regression, bukan pengisian
database toko baru. Tidak membuat endpoint reset atau pemicu startup/scheduler.

## Pengiriman

Implementasi awal tersimpan pada e0c1deb, perbaikan scope schema/QA MySQL pada
6407eec, dan bukti aktivasi/panduan aktual pada 65a60a3. Push origin/main berhasil;
SHA remote diverifikasi sama dengan HEAD dan divergence 0/0 sebelum pencatatan
penutupan ini. File LSP user tetap staged/untracked dan tidak masuk commit.
