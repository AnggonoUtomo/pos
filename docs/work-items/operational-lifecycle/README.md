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

## Status Aktivasi: Pending Koneksi MySQL

Penggantian database aktif **belum dilakukan**. Fingerprint lama yang disetujui
dan dipatok helper: `6158fd7712840fb6d48f2254006992c0eadab342dca5de697c035f1a613e4c63`.
Preview: 12 barang, 5 invoice, 1 pembelian, 1 stok awal, 1 draft transfer;
seluruh data bisnis target berada pada allowlist 46 tabel.

QA MySQL pertama terinterupsi server has gone away dan cleanup tertahan.
Database disposable milik run ini: `pos_lifecycle_qa_5f8dda96fdcad100`;
bersihkan setelah server aktif dengan guard prefix dan target bukan database toko.
User sudah mencoba Start MySQL, tetapi pemeriksaan ulang belum menemukan
proses MySQL/port 3306-3308. Percobaan ulang koneksi tetap ditolak. Tidak ada
perubahan konfigurasi OS/server atau penghapusan data toko untuk mengatasi ini.

Lanjut setelah MySQL benar-benar Running:

1. Bersihkan hanya database QA orphan di atas, bukan folder data MySQL.
2. Ulang QA MySQL disposable yang terganggu; hasil SQLite/full suite tetap dipakai.
3. Pastikan fingerprint preview tetap cocok; bila berbeda, berhenti dan minta arahan.
4. Pastikan backup masih baru; ulang backup jika perlu, bukan gate aplikasi penuh.
5. Jalankan helper --apply-once setelah QA MySQL lulus, periksa data/report/protected.
6. Ubah status panduan menjadi aktif dan catat nomor dokumen, selesaikan checklist.

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
