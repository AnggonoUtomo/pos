# ADR-017: Retur Pembelian Dari Layer Penerimaan Asal

## Status

Accepted. User menyetujui batas layer asal, posting penuh satu kali, pemisahan
penyelesaian supplier, dan posting saat barang diserahkan pada 21 Oktober 2026.

## Konteks

Draft retur pembelian merujuk satu pembelian yang telah diterima dan menyimpan
kuantitas serta harga historis. Penerimaan pembelian membuat satu FIFO layer per
baris receipt. Penjualan mengonsumsi layer FIFO dan retur penjualan membuat
layer baru; saldo barang dari sumber lain tidak membuktikan barang pembelian
asal masih tersedia untuk dikembalikan ke supplier.

## Keputusan

- Posting mengeluarkan seluruh baris draft dalam satu transaksi ketika barang
  diserahkan kepada supplier. Tidak ada posting parsial atau backdate.
- Setiap baris hanya boleh mengambil sisa kuantitas dari FIFO layer yang dibuat
  oleh baris penerimaan pembelian asal. Bila layer asal tidak mencukupi, seluruh
  posting ditolak meskipun saldo barang dari layer lain masih positif.
- Stok dari pembelian lain dan layer baru hasil retur penjualan tidak menggantikan
  layer asal. Draft tetap dapat disunting sebelum posting; dua draft boleh ada,
  tetapi posting bersaing atas sisa layer yang sama.
- Biaya persediaan yang keluar mengikuti remaining value pada layer asal:
  partial memakai pembulatan turun enam desimal, full drain mengambil residual.
  Harga beli snapshot tetap ada untuk penyelesaian supplier nanti dan tidak
  disamakan dengan mutasi kas.
- `Purchasing/Returns` memiliki transaction boundary, status/version/replay,
  otorisasi dan audit dokumen. `Inventory/Stock` memiliki lookup layer asal,
  saldo, issue/movement/consumption, valuasi dan audit stok. Writer Stock publik
  hanya menerima identitas sumber/kuantitas dasar, tanpa harga pokok dari client.
- Stock issue dan movement memakai `source_type=purchase_return`; trace
  consumption mengarah ke FIFO layer penerimaan. Receipt, layer/consumption
  penjualan, pembelian asal, dan Finance tidak ditulis ulang.
- Posted immutable. Retry revisi posting yang sama mengembalikan hasil tanpa
  issue/audit tambahan; revisi berbeda ditolak. First posting mensyaratkan
  master/gudang aktif dan stok tidak minus. Audit kedua module wajib atomik.
- Pengembalian uang, kredit supplier, utang, nomor resi, status pengiriman,
  void/reversal dan adjustment memerlukan pekerjaan terpisah.

## Alternatif Yang Tidak Dipilih

Mengambil FIFO terlama yang masih ada dari barang yang sama akan membuat retur
berhasil walaupun layer pembelian asal sudah terjual. Itu memutus hubungan
antara sumber pembelian dan biaya stok yang dikeluarkan.

## Verifikasi

Uji partial/full residual, nilai mikro nol, stok lain yang tidak eligible,
penjualan yang telah mengonsumsi layer asal, dua draft bersaing, replay,
version guard, rollback audit Stock/Returns, no-negative dan konsistensi saldo
terhadap seluruh layer. MySQL native diperlukan untuk bukti current read/lock.

## Referensi

- [Work item](../work-items/purchase-return-posting/README.md).
- [ADR-012](ADR-012-PAID-CHECKOUT-AND-FIFO-RESIDUAL.md).
- [Stock](../modules/Inventory/Stock/specification.md).
- [Retur pembelian](../modules/Purchasing/Returns/specification.md).
