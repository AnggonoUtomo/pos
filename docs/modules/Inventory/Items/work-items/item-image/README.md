# Gambar Utama Barang

Status: implementasi dan aktivasi lokal selesai; gate backend, MySQL, frontend
dan fallback browser lulus. Bukti serta status pengiriman di [tasks](tasks.md).

## Scope Dan Keputusan

User memilih satu gambar utama per barang pada 27 Oktober 2026. Gambar opsional,
dapat diganti/dihapus lewat form barang, dan tampil di master serta katalog POS.
Barcode tetap pada satuan barang existing; tidak membuat identitas stok baru,
barcode wajib, kamera scanner, label cetak atau kategori/merek dari pekerjaan ini.

## Contract

- Store/update barang menerima `image` berupa upload JPEG/PNG/WebP maksimal
  2 MB dan dimensi maksimal 4096 x 4096; SVG/GIF/file executable ditolak.
- `remove_image` boolean hanya boleh true tanpa upload baru. Tanpa keduanya,
  gambar existing dipertahankan. Form update dengan file memakai multipart POST
  dan `_method=put`, bukan native multipart PUT.
- Response daftar barang dan katalog POS menambahkan `image_url` nullable.
  SaleItemData menambahkan imageUrl nullable dengan default null.
- GET `inventory/items/{id}/image/{mediaId}` memerlukan auth, verified, dan
  salah satu inventory.view atau sales.pos.invoices.create. Barang/media harus cocok,
  aktif secara lifecycle (tidak terhapus), dan file ada. URL tidak mempublikasikan
  disk/path internal. Status aktif barang mengikuti master/catalog existing.
- Mutation tetap memakai inventory.manage dan audit inventory.items; role
  super-system hanya bypass permission, bukan validasi file atau aturan barang.

## Implementasi

Spatie Media Library v11 dengan model media ULID serta morph ULID. Migration
berada di Database/Migrations Items; provider menjadi composition root adapter.
Media dimiliki Items untuk consumer gambar barang yang nyata, bukan module
framework reusable baru. Application memakai port, bukan model/package konkret.

Collection single-file, disk local privat, nama file acak berdasarkan MIME
tervalidasi. Tidak menggunakan URL upload eksternal, optimizer biner, konversi
queue atau symlink public. Gambar ditampilkan object-contain dengan dimensi UI
stabil; barang tanpa gambar tetap dapat dijual.

DB master/harga/media/audit commit bersama. File baru dikompensasi jika transaksi
gagal; penghapusan file lama ditunda sampai commit. Soft delete mempertahankan
gambar untuk restore. Tidak mengubah ledger, FIFO, saldo atau snapshot invoice.
SaveItemCatalog dengan perubahan gambar harus memiliki transaction terluar;
pemanggilan di dalam transaction lain ditolak agar rollback caller tidak
meninggalkan file baru tanpa media. Pemanggilan tanpa perubahan gambar tetap
mendukung composition transaction existing.

## Batas Operasional

Backup SQL existing tidak memuat binary gambar. Direktori privat gambar perlu
ikut salinan file saat backup/restore lengkap; pekerjaan ini tidak memasang
penjadwal OS atau diam-diam mengubah format backup existing. Demo gambar remote
tidak diunduh; test/browser menggunakan gambar fixture disposable, bukan gambar
produk rekaan pada data toko. Seeder master existing tidak diubah/reset.

Referensi package: [media collections](https://spatie.be/docs/laravel-medialibrary/v11/working-with-media-collections/defining-media-collections)
dan [custom model](https://spatie.be/docs/laravel-medialibrary/v11/advanced-usage/using-your-own-model).
