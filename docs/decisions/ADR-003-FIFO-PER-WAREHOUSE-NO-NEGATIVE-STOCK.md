# ADR-003: FIFO Per Gudang Dan Stok Tidak Boleh Minus

## Status

Accepted

## Tanggal

2026-09-12

## Konteks

POS harus mendukung multi gudang, pembelian, penjualan, retur, adjustment, dan
perhitungan HPP. Stok tidak boleh negatif karena akan merusak FIFO, margin, dan
laporan operasional.

## Keputusan

Stok dihitung dalam base unit terkecil. FIFO layer dikelola per
`item + warehouse`. Transaksi yang membuat stok minus wajib ditolak. Posting
stok menggunakan database transaction dan locking yang sesuai.

## Konsekuensi

- Semua input multi satuan harus dikonversi ke base unit sebelum mempengaruhi
  stok.
- Sales, purchasing, return, void/reversal, dan adjustment harus menjaga FIFO
  layer dan stock movement.
- Satu transaksi POS memakai satu gudang untuk MVP.
- Koreksi stok dilakukan lewat dokumen koreksi yang audited, bukan edit bebas.

## Verifikasi

- Test menolak stok minus.
- Test FIFO mengambil layer tertua per `item + warehouse`.
- Test memastikan gudang berbeda tidak mencampur layer FIFO.
- Test memastikan kuantitas disimpan dalam base unit.
