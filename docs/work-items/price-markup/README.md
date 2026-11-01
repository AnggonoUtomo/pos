# Bantuan Hitung Harga Dengan Persen

## Keputusan User

Penghitungan otomatis, keputusan harga tetap pada operator. Biaya acuan adalah
harga beli terakhir yang sudah diterima dari seluruh gudang, bukan HPP layer
FIFO yang sedang dikeluarkan. Belum ada pembelian: tetap isi harga manual.
Level harga mengikuti master level pelanggan aktif, bukan tiga nama tetap.

## Scope

- Purchasing/PurchaseOrders menerbitkan lookup baca-saja biaya beli snapshot
  terakhir (posted_at lalu ID), hanya pembelian posted dengan bukti receipt.
- Inventory/Items menyediakan preview berizin inventory.manage. Input persen
  markup per level aktif dan konversi satuan pada editor; hasil dalam rupiah
  dua desimal HALF_UP. Perhitungan exact tanpa membulatkan biaya dasar dahulu.
- Sales/Parties memiliki aturan markup/validasi harga dan matriks dinamis.
- UI: isi persen -> Hitung usulan -> review -> Terapkan ke form -> Simpan barang.
  Menghitung/menerapkan ke form bukan penyimpanan. Nilai persen hanya alat bantu
  sesi editor, bukan aturan auto-repricing yang disimpan. Persen kosong melewati
  level tersebut; persen 0 sah. Harga akhir tetap positif dan bisa diedit manual.
- Preview berubah/ditutup saat item, satuan, level atau persen berubah; response
  lama/abort tidak boleh mengisi hasil baru. Request tidak mengubah ledger/audit.
- Label/guide generik; demo CCTV tetap opsional dan tidak mensyaratkan tepat tiga
  level. Tidak menghapus/rename level, harga atau akun existing.

Non-scope: margin target, markup nominal baru, pembulatan komersial, perubahan
FIFO, auto-repricing saat pembelian/POS, default pelanggan POS yang sudah
disepakati, perubahan role/permission, schema baru, dependency dan data toko.

## Status

Selesai: implementasi, QA, aktivasi lokal dan delivery ke origin/main.
[Checklist/bukti](tasks.md), [rencana](plan.md).

Gunakan Edit pada https://pos.test/inventory/items untuk barang dengan pembelian
diterima. Persen level kosong dilewati; Terapkan ke form tidak menyimpan, Simpan
barang tetap keputusan operator. Sumber/biaya dan tabel usulan bisa dilihat
dengan scroll dialog. Installer existing tidak dihapus: ubah nama melalui master
level bila sesuai kebijakan toko. Tidak ada perubahan data, akun, role/permission,
stok/FIFO, scheduler, .env atau Old-docs. LSP user dikecualikan dari commit.
PDF operator lama belum diregenerasi, panduan baru tersedia Markdown dan F1.
