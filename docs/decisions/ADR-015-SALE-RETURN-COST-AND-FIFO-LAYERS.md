# ADR-015: Biaya Retur Penjualan Dan Layer FIFO Baru

## Status

Proposed. Scope posting dan layer baru disetujui user. Bagian dukungan layer
biaya nol masih menunggu jawaban eksplisit; jangan menganggap seluruh ADR accepted.

## Tanggal

2026-10-18

## Konteks

Draft Returns menyimpan satuan/harga asal, belum memiliki mutation Stock atau
refund. Posting barang layak jual harus menjaga batas jumlah terjual lintas
draft dan mengembalikan biaya dari fifo_consumptions penjualan asal.
Nominal harga jual bukan sumber valuasi persediaan. FIFO existing menggunakan
received_at lalu ULID dan partial rounding down scale6 dengan residual full drain.

## Disetujui User

- Gudang tujuan adalah gudang invoice asal; hanya barang layak jual.
- Layer baru bertanggal server saat retur diterima, bernilai biaya FIFO asal.
  Layer lama, movement penjualan, consumption asal, invoice dan payment tidak
  ditimpa. Barang rusak/karantina, tukar, refund dan reversal tidak termasuk.
- Kuantitas posted kumulatif tidak melebihi source line terjual.
- Stok/status/audit atomic; replay tidak menggandakan stok.

## Rancangan Untuk Implementasi

- Returns menjadi transaction boundary. Published writer/DTOs Stock memerlukan
  transaction default caller; tidak ada mutation event async.
- Stock menemukan issue sale melalui invoice source ID dan source line ID
  miliknya sendiri. Returns tidak mengirim HPP bebas atau mengimpor model privat
  Stock; Stock tidak membaca tabel privat Returns/POS untuk valuasi.
- Trace retur menghubungkan receipt line baru ke consumption asal. Hitung sisa
  quantity/value consumption dari nilai asal dikurangi seluruh trace retur.
  Konsumsi sumber diurutkan mengikuti urutan FIFO asal, bukan harga master.
- Partial mengambil proporsi remaining_value x taken / remaining_quantity
  dengan rounding down scale6; full drain mengambil seluruh residual. Nilai
  kumulatif dipertahankan exact, tidak merekonstruksi unit cost rounded.
- Lock gudang asal sebelum source issue/consumption, lalu master/saldo/layer
  dalam urutan deterministik mengikuti pola Stock existing. Current read dan
  trace/constraint unik wajib diuji pada MySQL, bukan hanya SQLite.
- Receipt sumber sale_return harus terpisah dari purchase melalui source_type.
  Replay revisi posted yang sama dapat dibaca tanpa master aktif; first posting
  mengikuti guard master aktif/base unit dan overflow Stock existing.
- HPP tetap privat pada Stock. Response Returns hanya metadata posted/receipt,
  bukan harga pokok atau nominal refund. Posted tidak dapat dimutasi via CRUD.

## Keputusan Masih Terbuka: Biaya Nol

ADR-012 secara eksplisit mengakui partial HPP nol pada presisi mikro. Layer
retur baru bisa berquantity positif/value nol. FifoAllocation dan ReceiptBalance
existing saat ini menolak bentuk itu. Rekomendasi yang ditanyakan kepada user:

- Dukung biaya nol yang berasal dari alokasi FIFO; nol quantity tetap wajib
  bernilai nol dan biaya/quantity negatif tetap ditolak.
- Pastikan layer biaya nol dapat dikeluarkan lagi, termasuk FIFO campuran
  layer nol/positif dan partial/full drain.
- Input harga pokok stok awal/pembelian tetap positif. Harga jual tidak berubah.

Alternatif: menolak retur dengan nilai turunan nol. Tidak boleh membulatkan
biaya naik atau menganggap biaya nol sebagai harga jual gratis.

## Verifikasi Yang Wajib

Unit conservation/residual/nilai nol; feature race batas kumulatif, replay,
version dan posted guard; rollback audit Stock/Returns; privacy HPP; MySQL
locking/current read/constraint/migration; QA browser desktop/tablet. Kasus
dua unit jual barang sama harus tetap dibedakan berdasarkan source line.

Tetapkan keputusan biaya nol dan test plan sebelum mengubah runtime.

## Referensi

- [Pekerjaan](../work-items/sale-return-posting/README.md).
- [ADR-012](ADR-012-PAID-CHECKOUT-AND-FIFO-RESIDUAL.md).
- [Stock](../modules/Inventory/Stock/specification.md).
- [Returns](../modules/Sales/Returns/specification.md).
