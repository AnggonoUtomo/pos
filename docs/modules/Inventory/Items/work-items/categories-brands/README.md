# Kategori Dan Merek Barang

## Scope Disepakati

User memilih kategori/merek dahulu; impor/harga massal, laporan HPP dan
serial/garansi tidak masuk pekerjaan ini. Maksimal satu kategori dan satu merek
per barang, opsional, kategori datar tanpa subkategori. Master yang masih
dirujuk barang (termasuk soft-deleted) tidak boleh dihapus/dinonaktifkan.

## Ownership Dan Batas

- Inventory/Items memiliki item_categories, item_brands dan referensi nullable
  pada items. Bukan bounded context baru; tidak menambah generator/module kosong.
- Master memakai ULID, kode unik termasuk tombstone, aktif/nonaktif,
  soft delete/restore, audit dan permission inventory.view/manage existing.
- Halaman kategori/merek terpisah, komponen dekat page Items; shared workspace,
  pagination, Sonner, shortcut/F1 dan theme existing tetap digunakan.
- Barang memakai autocomplete opsional; filter admin/POS mengikuti kategori
  dan merek. Consumer POS/PurchaseOrders hanya memakai contract publik Items.
- Harga/quote/checkout/posted/snapshot/stok/FIFO/Finance tidak berubah.
  Mengubah klasifikasi bukan repricing atau pemindahan persediaan.
- Migration additive: barang lama tetap null, tanpa klasifikasi/seed otomatis.
  Demo CCTV/kelistrikan hanya eksplisit local/testing, idempotent dan tidak
  menimpa barang/master existing. Data toko dan file LSP user dipertahankan.
- Update API yang tidak menyertakan field klasifikasi mempertahankan nilai
  existing; null eksplisit mengosongkan pilihan.

## Status

Dalam implementasi. [Rencana](plan.md), [checklist](tasks.md).
