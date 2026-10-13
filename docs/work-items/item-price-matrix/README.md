# Harga Jual Langsung Pada Form Barang

Status: In Progress. Owner: Inventory/Items (workflow barang/UI) dan
Sales/Parties (harga per level pelanggan). Disetujui user 13 Oktober 2026:
semua kombinasi satuan jual dan level aktif wajib terisi sebelum simpan barang.

Scope: harga jual nyata per barang/satuan/level, form matrix, validation,
permission existing inventory.view/manage, audit atomic dan demo relasional.
Harga positif, rupiah dengan maksimum dua desimal, format UI Indonesia.
Tidak memakai fallback Eceran atau persentase otomatis untuk harga user.
Harga pokok/HPP tetap Stock/FIFO. Posting POS/pembelian tetap di luar scope;
POS existing masih mock dan belum menggunakan matrix sebagai engine transaksi.

Barang existing tanpa harga tidak diisi harga rekaan secara otomatis. Harga
demo fiktif hanya diisi pada SKU demo CCTV/kelistrikan yang dikenal, tanpa
menimpa harga existing. Barang legacy harus dilengkapi saat create/edit UI.
Internal CreateItem tetap penyusunan katalog untuk fixture/provisioning;
workflow publik save memakai action atomic yang menyertakan matrix harga.
