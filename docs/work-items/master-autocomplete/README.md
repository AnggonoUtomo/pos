# Autocomplete Pilihan Master

## Scope User

Pilihan supplier/barang pada draft pembelian menjadi search autocomplete.
Tambah supplier/barang dari draft bila belum tersedia, menjaga isian draft dan
memilih master baru sesudah berhasil. Terapkan pencarian pada pilihan master
sejenis di form lain, bukan mengganti enum status/jenis menjadi input bebas.

## Batas Dan Pendekatan

- Shared Autocomplete memakai Headless UI yang sudah terpasang dan Input/Button
  shadcn, bukan dependency/design system baru. Pilihan harus berasal dari daftar;
  mengetik bebas tidak pernah menjadi ID master atau membuat data otomatis.
- Pilihan master dinamis pada editor dan filter yang sejenis disamakan: barang,
  supplier, gudang, satuan, level pelanggan, akun/metode pembayaran dan sumber
  dokumen. POS pelanggan sudah autocomplete, tidak perlu dibuat ulang.
- Snapshot pilihan tidak aktif tetap tampil tetapi tidak dapat dipilih kembali;
  transaksi posted tetap readonly, barcode, reset unit dan guard duplikat tetap.
- Quick-create hanya pada pembelian: reuse form Supplier/Barang, permission
  terpisah dan endpoint existing. Harga lengkap/gambar/barcode/validasi master
  tetap berlaku. Cancel/error tidak mengubah draft. Master berhasil dibuat tidak
  otomatis dibatalkan ketika draft kemudian dibatalkan; jelaskan pada panduan.
- Tidak mengubah schema, stok/FIFO, Finance, role/grant, default POS, dataset toko,
  scheduler atau Old-docs. Pilihan lookup existing tetap bounded; perluasan
  pencarian server seluruh katalog besar bukan scope otomatis pekerjaan ini.
- Demo baru tidak diperlukan untuk shared UI; QA memakai master/transaksi fiktif
  disposable dan master yang dibuat eksplisit oleh test saja.

## Status

Implementasi dan QA selesai. Inventaris AST menemukan 44 deklarasi pilihan dinamis
di 34 file TSX; satu deklarasi dalam loop dapat menghasilkan beberapa field.
Ini bukan 44 form terpisah. Editor dan filter berbagi komponen yang sama.

| Kelompok | Deklarasi | Contoh pilihan |
| --- | ---: | --- |
| Barang | 3 | Satuan dasar/tambahan dan label barcode |
| Stok | 7 | Gudang/barang/satuan stok awal, koreksi dan filter |
| Stock opname | 3 | Gudang sesi/filter dan barang tambahan |
| Stok minimum | 3 | Gudang dan barang form/filter |
| Transfer | 4 | Asal/tujuan, barang, satuan dan filter |
| Pembelian | 5 | Supplier/gudang/barang/satuan, metode dan filter |
| Retur pembelian | 1 | Metode penerimaan uang |
| Pelanggan | 2 | Level pada form/filter |
| POS | 4 | Gudang, satuan katalog/keranjang dan metode |
| Riwayat penjualan | 1 | Gudang/pelanggan filter |
| Shift | 1 | Akun laci |
| Retur penjualan | 2 | Invoice asal dan metode refund |
| Kas/bank dan metode | 2 | Akun pada form/filter metode |
| Buku Kas | 3 | Akun utama/tujuan dan pilihan akun workspace |
| Riwayat pembayaran | 1 | Akun/metode filter |
| Laporan | 1 | Gudang |
| Log Aktivitas | 1 | Modul/event/pelaku filter |

Pengecualian: status/jenis/diskon Rp-persen/format kertas/jumlah baris tetap
Select; role/permission tetap checklist. Pelanggan POS dan pembelian asal
retur sudah memiliki pencarian, tidak diduplikasi. Lookup awal existing
sebagian dibatasi 1.000; dropdown baru menampilkan 100 hasil sesudah filter,
bukan memperluas daftar server. Quick-create diambil lewat ID secara eksplisit.
[Rencana](plan.md), [checklist](tasks.md).
