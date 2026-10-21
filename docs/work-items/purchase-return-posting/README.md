# Posting Retur Pembelian Dari Layer Asal

Status: In Progress. Keputusan domain disetujui; implementasi dan QA belum
selesai.

## Tujuan Dan Batas

Operator memposting seluruh baris draft retur saat barang diserahkan kepada
supplier. Posting mengurangi saldo gudang asal dan sisa FIFO layer dari
penerimaan pembelian asal saja. Biaya stok keluar dihitung dari remaining value
layer tersebut; stok tidak boleh minus. Dokumen posted readonly dan posting
ulang revisi yang sama tidak menggandakan ledger atau audit.

Pengembalian uang/kredit supplier, utang, pengiriman parsial, resi, backdate,
void/reversal, dan pengeluaran barang dari layer lain tidak masuk pekerjaan ini.
Harga beli snapshot bukan jumlah kas yang diterima.

## Ownership

- Purchasing/Returns: action posting, validasi sumber/snapshot, status, version,
  permission, audit, route dan UI konfirmasi/detail.
- Inventory/Stock: published writer, trace penerimaan asal, pengurangan saldo,
  issue/movement/FIFO consumption dan audit stok.
- PurchaseOrders: published lookup sumber existing; pembelian/receipt asal tetap.
- Finance/Payments: fingerprint tidak berubah.

Keputusan biaya dan batas asal ada pada [ADR-017](../../decisions/ADR-017-PURCHASE-RETURN-ORIGINAL-RECEIPT-LAYER.md).
Rencana serta bukti kerja ada pada [plan](plan.md) dan [tasks](tasks.md).

## Acceptance

- [ ] Satu draft diposting penuh sekali, di gudang pembelian asal.
- [ ] Tiap baris hanya mengonsumsi sisa layer receipt pembelian asal; stok
  barang dari layer lain tidak membuat posting lolos.
- [ ] Quantity/value saldo, layer, movement dan consumption exact6 konsisten;
  full drain menjaga residual, termasuk biaya mikro nol.
- [ ] Dua draft bersaing tidak dapat mengeluarkan lebih dari sisa layer.
- [ ] Status/version/replay, master guard, permission dan audit lintas module
  atomik; posted tidak bisa edit/delete/restore.
- [ ] UI konfirmasi, status/filter/detail, shortcut, Sonner dan QA desktop/tablet
  bekerja tanpa menampilkan HPP sebagai uang kembali.
- [ ] Aktivasi migration/catalog additive tanpa grant/demo/posting; native MySQL,
  regresi dan remote delivery diverifikasi.

## Risiko

Barang fisik yang sudah terjual lalu masuk lagi melalui retur penjualan berada
pada layer baru dan tidak eligible untuk retur pembelian asal. Ini konsekuensi
yang disetujui dari batas sumber spesifik, bukan kekurangan saldo umum.
