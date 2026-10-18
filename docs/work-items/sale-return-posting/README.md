# Posting Retur Penjualan Layak Jual

Status: Draft. Scope produk dan layer baru disetujui 18 Oktober 2026;
perlakuan layer biaya nol masih menunggu jawaban user sebelum mutation ledger.

## Tujuan Dan Scope

Operator memposting seluruh isi satu draft retur untuk barang layak jual ke
gudang invoice asal. Kuantitas retur posted kumulatif tidak boleh melebihi
kuantitas terjual pada masing-masing baris invoice. Biaya berasal dari alokasi
FIFO penjualan asal, bukan harga jual atau harga beli/master terbaru.

Status dokumen, saldo, movement, layer FIFO, jejak alokasi dan audit harus
berhasil dalam satu transaction. Posting ulang revisi yang sama tidak menambah
stok/audit; revisi berbeda ditolak. Dokumen posted readonly, tanpa soft delete,
edit, restore sebagai draft, atau endpoint koreksi bebas.

Tidak termasuk refund, pencatatan utang refund, perhitungan nominal refund,
barang rusak/karantina, tukar barang, gudang tujuan bebas, backdating, atau
reversal. Invoice, konsumsi FIFO penjualan dan payment asal tetap immutable.

## Ownership

- Sales/Returns: orchestration posting, version/replay, status dan otorisasi.
- Inventory/Stock: batas retur terhadap issue/consumption asal, valuasi,
  movement/saldo/layer dan audit stok.
- Sales/POS: published lookup snapshot sumber. Tidak memberikan model/repository
  privat atau menjadikan HPP sebagai props publik Returns.
- Finance/Payments: tidak dimutasi pada pekerjaan ini.

## Keputusan Dan Gap

User menyetujui layer baru pada waktu retur diterima dengan biaya asal.
Histori sale/consumption dan layer lama tetap utuh, tidak mengembalikan umur
FIFO lama. Rancangan lebih rinci ada pada
[ADR-015 proposed](../../decisions/ADR-015-SALE-RETURN-COST-AND-FIFO-LAYERS.md).

Gap presisi yang harus diselesaikan pada desain: ADR-012 mengizinkan alokasi
HPP partial bernilai nol pada scale6. FifoAllocation/ReceiptBalance existing
menolak layer berkuantitas positif dengan nilai nol. Pertanyaan user aktif:
dukung layer biaya nol hasil alokasi FIFO tanpa mengubah harga pokok input
stok awal/pembelian, atau tolak posting tersebut? Rekomendasi: dukung biaya nol
turunan dan uji jalur pengeluaran berikutnya, tanpa menaikkan HPP/membuang
residual. Pilihan urutan FIFO tidak otomatis memutuskan perubahan invariant ini.

## Acuan

- [Rencana](plan.md), [Checklist](tasks.md).
- [Returns](../../modules/Sales/Returns/specification.md).
- [Stock](../../modules/Inventory/Stock/specification.md).
- [ADR-012](../../decisions/ADR-012-PAID-CHECKOUT-AND-FIFO-RESIDUAL.md).

Old-docs, SampleUI, artefak user, dependency, theme, serta akun/role existing
tidak termasuk scope. Tidak ada migration atau transaksi lokal pada baseline ini.
