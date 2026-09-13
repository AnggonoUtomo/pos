# ADR-0003: FIFO Per Gudang dan Stok Tidak Boleh Minus

## Status

Diterima

## Tanggal

2026-09-12

## Konteks

POS retail/grosir membutuhkan HPP dan laba yang dapat dipercaya. Sistem juga harus mencegah stok negatif karena stok negatif merusak FIFO layer, laporan laba, dan audit persediaan.

## Keputusan

- FIFO berjalan per `item + warehouse`.
- Semua kuantitas stok disimpan dalam base unit.
- Stok tidak boleh minus.
- Posting transaksi yang mengurangi stok harus memvalidasi saldo dan mengunci FIFO layer.
- Penjualan, retur pembelian, transfer keluar, item keluar, dan adjustment keluar ditolak jika stok tidak cukup.

## Alternatif Yang Dipertimbangkan

### Average Cost

- Kelebihan: lebih sederhana.
- Kekurangan: tidak sesuai keputusan produk awal yang membutuhkan FIFO.
- Ditolak.

### FIFO Global Per Item

- Kelebihan: lebih sederhana dibanding per gudang.
- Kekurangan: salah untuk multi gudang karena cost dan stok tiap gudang berbeda.
- Ditolak.

### AlRendah Negative Stock

- Kelebihan: kasir tidak terhambat.
- Kekurangan: merusak FIFO, HPP, dan audit.
- Ditolak.

## Konsekuensi

- Inventory menjadi modul kritis.
- Stock-affecting use case harus memakai database transaction dan row lock.
- Retur penjualan harus mengacu ke alokasi FIFO transaksi asal.
- Transaksi posted tidak boleh diedit bebas.
