# Tasks: POS Skala Menengah

## Sebelum Mulai

- [x] Baca AGENTS.md, docs/README.md, ARCHITECTURE.md dan FOLDER-STRUCTURE.md.
- [x] Periksa backlog, dokumen Items, barcode/katalog existing dan status Git.
- [x] User mengaktifkan tiga tahap dan mengizinkan instalasi Media Library.
- [x] Batasi perubahan awal ke persiapan package dan peta capability.
- [x] Pertahankan file LSP user dan jangan menyentuh Old-docs.

## Increment 1: Persiapan Barang Dan Peta Pengembangan

- [x] Susun peta capability, dependency dan urutan tiga tahap.
  - Acceptance: semua area permintaan tercatat; keputusan bisnis terbuka tidak
    diperlakukan sebagai persetujuan implementasi rinci.
  - Verification: review roadmap dan tautan relatif.
- [x] Install Spatie Laravel Media Library versi kompatibel.
  - Acceptance: package masuk lockfile tanpa mengganti major stack atau
    mengabaikan advisory keamanan.
  - Verification: Composer require sukses dan tidak menemukan advisory.
- [x] Verifikasi discovery package dan regresi barang existing.
  - Acceptance: package terdaftar dan perilaku barcode/master tetap lulus;
    database toko tidak direset atau di-seed ulang.
  - Verification: package:discover, focused Items test, diff check.
- [x] Commit persiapan dengan scope eksplisit.
  - Acceptance: file LSP user tidak masuk commit.
  - Verification: staged diff dan git show --stat.

## Increment 2: Identifikasi Dan Gambar Barang

- [x] Tetapkan satu gambar utama atau galeri berdasarkan jawaban user.
- [x] Buat work item Items beserta acceptance dan checklist sebelum coding.
  - Pilihan user: satu gambar utama, bisa diganti/dihapus.
  - Detail: [gambar barang](../../modules/Inventory/Items/work-items/item-image/README.md).
- [x] Implementasikan persistence/adapter media, validasi, permission dan audit.
- [x] Integrasikan form, tampilan master dan katalog POS lewat public DTO.
- [x] Verifikasi upload/ganti/hapus, akses ditolak, file invalid, barcode dan
  harga multi-satuan, rollback, serta soft delete/restore.
- [x] Verifikasi schema disposable, frontend dan browser terfokus; aktifkan
  migration additive secara aman, catat hasil dan commit.

## Increment 3: Keranjang Tertahan

- [x] Tetapkan perilaku ownership/pemanggilan dan dokumentasikan work item.
  - Own-only, tanpa reservasi/pengalihan; [detail](../../modules/Sales/POS/work-items/held-carts/README.md).
- [x] Selesaikan alur tahan/panggil/batalkan, validasi ulang harga/stok, UI,
  permission, audit, test terfokus dan panduan.

## Increment 4: Buku Kas Dan Shift

- [ ] Sepakati awal saldo, kas/bank, laci, modal dan selisih shift.
- [ ] Buat work item buku kas dan selesaikan ledger/saldo operasional.
- [ ] Buat work item shift dan selesaikan buka/tutup/hitung/review.
- [ ] Verifikasi transaksi/refund/setoran tidak dihitung ganda.

## Increment 5: Stok Dan Purnajual

- [ ] Tetapkan workflow opname dan perubahan stok selama sesi hitung.
- [ ] Selesaikan opname serta stok minimum per gudang.
- [ ] Tetapkan serial/garansi/klaim lalu selesaikan alur lintas module-nya.

## Increment 6: Grosir Dan Proyek

- [ ] Tetapkan aturan tempo dan alokasi pembayaran lalu implementasikan.
- [ ] Tetapkan penawaran/konversi/reservasi lalu implementasikan.
- [ ] Tetapkan paket komponen/jasa dan retur lalu implementasikan.
- [ ] Tetapkan penerimaan parsial/tagihan/bayar lalu implementasikan.

## Penutupan Setiap Paket

- [ ] Checklist capability terkait diisi berdasarkan hasil nyata.
- [ ] Gate akhir paket lulus; pending operasional lama tidak ditandai selesai.
- [ ] Panduan operator diperbarui sesuai alur yang sudah tersedia.
- [ ] Push setelah work item selesai; verifikasi local/remote.

## Catatan Verifikasi

- Composer require: PASS, spatie/laravel-medialibrary 11.23.11, enam package
  baru dan tidak ada package existing yang diperbarui. Audit Composer bersih.
- Percobaan awal constraint caret terpotong oleh wrapper Composer Windows;
  resolver menolak versi 11.0.0 yang tidak aman dan otomatis memulihkan manifest.
  Instalasi memakai constraint 11.*; tidak ada advisory yang diabaikan.
- Belum ada perubahan UI, schema, data atau pengaturan produksi pada persiapan.
- `php artisan package:discover --ansi`: PASS.
- `composer validate --no-check-publish`: PASS.
- Focused `php artisan test --compact tests/Feature/Modules/Inventory/Items`:
  PASS, 42 tests / 242 assertions pada SQLite disposable. APP_ENV dan cache
  testing diisolasi; .env.testing sementara dihapus setelah test. File .env
  produksi tidak diubah. Percobaan awal tanpa APP_ENV testing gagal memuat
  dotenv produksi existing; tidak sampai menjalankan test atau mutation.
- `git diff --check`: PASS.
- Build/browser/migration: SKIPPED karena belum ada perubahan UI atau schema;
  verifikasi tersebut wajib saat increment gambar diimplementasikan.
- Commit persiapan diverifikasi: hanya delapan file roadmap/package, tanpa
  file LSP user. Belum push karena work item pengembangan masih berjalan.
- Increment gambar/barcode selesai: detail gate dan aktivasi pada work item
  gambar. Commit/push capability mengikuti checklist-nya, bukan klaim tiga
  tahap skala menengah sudah selesai.
- Keranjang tertahan selesai; bukti verifikasi/aktivasi/delivery di work item
  Sales/POS terkait. Berikutnya buku kas/shift setelah aturan bisnis disepakati.
