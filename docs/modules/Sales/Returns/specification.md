# Spesifikasi Retur Penjualan

## Scope Disetujui

- Draft berdasarkan satu invoice posted, sebagian baris dan kuantitas.
- Satuan, rasio konversi, barang, gudang, pelanggan, dan harga berasal dari
  snapshot invoice, bukan master yang dapat berubah.
- Kuantitas positif, maksimum kuantitas terjual pada baris asal, presisi enam
  desimal. Konversi base unit harus tepat tanpa pembulatan tersembunyi.
- Satu baris invoice hanya sekali per draft; maksimum 100 baris.
- Invoice asal tidak dapat diganti ketika mengedit draft.
- Tambah/edit/soft delete/restore dengan optimistic version, row lock,
  transaction, permission backend, dan activity log atomik.
- `sales.returns.view` membaca semua draft. `sales.returns.manage` melakukan
  mutation dan mencari snapshot invoice sumber. Tidak otomatis memberikan
  permission kepada role/user existing. Super-system hanya bypass authorization.
- Catatan opsional maksimum 500 karakter; tidak disalin ke activity log.

## Batas Tegas

Draft belum memposting retur, mereservasi stok/kuantitas retur, mengembalikan
stok/FIFO, mencatat refund, atau mengubah invoice/pembayaran asal. Beberapa
draft boleh menunjuk invoice sama; batas kumulatif retur posted wajib dirancang
sebelum posting tersedia. Draft hanya menyimpan kuantitas permintaan dan harga
asal; nominal refund dan kebijakan pembulatannya belum ditetapkan.

Tidak ada endpoint posting, edit invoice, hapus permanen, atau perubahan ledger.
Snapshot publik tidak memuat HPP, pembayaran, request hash, maupun credential.
Nomor draft dibuat server dan immutable. Draft terhapus hanya dapat dipulihkan,
bukan diedit; operasi stale ditolak, termasuk dari super-system.

## Demo Dan UI

Demo CCTV/kelistrikan hanya berjalan eksplisit pada local/testing, memakai
invoice demo posted yang sudah ada. Bila belum ada sumber relevan, skip tanpa
membuat penjualan/stock/payment otomatis. Demo idempotent dan tidak menimpa
draft edited/trashed. Aktivasi lokal tidak menjalankan demo.

Halaman admin memakai shadcn/ui, Sonner, format angka Indonesia, shortcut
dropdown, navigasi sidebar/palette, desktop/tablet Light/Dark.
