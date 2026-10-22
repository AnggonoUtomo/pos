# Spesifikasi: Stok Awal, Penerimaan Dan FIFO

## Retur Penjualan Layak Jual

`SaleReturnStockWriter::receive(SaleReturnStockRequest)` dikonsumsi
`Sales/Returns/PostReturnDraft` dalam shared transaction default. DTO membawa
warehouseId/sourceId/sourceNumber/invoiceId, actorId nullable dan lines
{source_line_id,sale_line_id,base_quantity}. Tidak menerima biaya bebas dan tidak
mengambil model/tabel privat POS atau Returns. Stock mencari stock_issues sale
melalui invoice source ID dan stock_issue_lines melalui source line asal.

Gudang tujuan wajib asal dan aktif pada first posting; unit/item aktif dan
base unit sama. Warehouse lock mendahului source issue/consumption, master
terurut serta inventory current read. Receipt source_type sale_return + hash
canonical/unique source menjaga replay; source_line unik per receipt. Existing
purchase writer/contract publik tidak berubah.

`stock_return_allocations` menyimpan FK receipt_line_id/fifo_consumption_id
restrict, unique pasangan, quantity20,6/value30,6. Sisa consumption asal dihitung
exact dari original dikurangi seluruh trace current read (tanpa SQL float sum).
Quantity/value asal harus sesuai issue line. ReturnAllocation mengalokasikan
consumption dalam urutan FIFO asal, partial round down6 dan full drain residual.
Barang sama di dua satuan penjualan tetap dibedakan menurut source line.

Receipt menambah movement positif bertipe sale_return, saldo dan layer baru
received_at saat penerimaan. Layer/consumption/issue asal tidak ditulis ulang.
Quantity positif dengan value nol hasil alokasi sah dan dapat dikeluarkan lagi;
quantity nol wajib value nol, negatif/overflow tetap ditolak. ReceiptBalance
added value0 hanya diizinkan writer retur melalui flag eksplisit. Harga pokok
input stok awal/pembelian tetap positif. Audit Stock/Returns atomic; posted
ledger/trace tidak soft delete, migration down menolak histori posted.

Selesai diverifikasi: full525 test/4057 assertion, native MySQL dua proses
bersaing, biaya nol/resale, replay, rollback audit dan FIFO exact. Schema lokal
Ran batch21, tidak ada demo/posting/grant otomatis. Detail bukti:
[pekerjaan posting retur](../../../work-items/sale-return-posting/tasks.md).

## Status Dan Persetujuan

Disetujui untuk scope awal pada 12 Oktober 2026. Draft/posting, query/UI dan
fondasi FIFO sudah diimplementasikan; verifikasi aktual ada pada tasks.md.
User menyetujui harga pokok per satuan pilihan wajib positif, satu barang satu
baris dokumen, dan stok awal hanya sekali per pasangan barang + gudang.

## Alur Dan Invariant

1. Admin memilih satu gudang, lalu barang, satuan, kuantitas dan harga pokok.
2. Simpan draft; belum mempengaruhi saldo, movement atau FIFO.
3. Draft dapat diedit, soft delete dan restore. Posted tidak boleh diedit,
   dihapus, atau dipulihkan sebagai draft.
4. Posting memvalidasi master aktif/tidak terhapus dan konversi terkini. Rasio
   draft yang berubah wajib disimpan ulang; bukan diam-diam dihitung ulang.
5. Posting pertama membuat saldo, satu movement masuk per baris dan satu layer
   FIFO per baris. Posting ulang dokumen yang sama tidak menggandakan stok/audit.
6. Pasangan barang + gudang yang sudah mempunyai saldo/histori tidak boleh
   menerima stok awal kedua. Unique constraint dan lock master melindungi race.
7. Kuantitas/harga pokok positif, maksimum 100 baris, item tidak berulang.
8. Urutan layer berdasarkan received_at lalu ULID, per item + warehouse.
   Pengeluaran internal tersedia melalui IssueStock pada work item
   [checkout lunas](../../../work-items/pos-paid-checkout/README.md), belum
   endpoint pengeluaran bebas. Consumer checkout backend tersedia sejak Increment 4.

## Presisi Dan Snapshot

- Decimal string, bukan float: quantity/ratio/cost maksimal enam desimal.
- Quantity/base_quantity decimal(20,6); total nilai decimal(30,6).
- SQLite disposable memakai string untuk kolom decimal Stock agar NUMERIC
  affinity tidak membuang digit. MySQL tetap DECIMAL; domain memvalidasi scale/range.
- Konversi quantity × ratio dan quantity × harga pokok harus tepat pada enam
  desimal; overflow atau kebutuhan rounding ditolak sebelum persistence.
- Harga pokok input per satuan pilihan, bukan per satuan dasar. Contoh 2 dus,
  ratio 12, Rp120000/dus: 24 pcs dan total nilai Rp240000.
- Original/remaining quantity dan original/remaining value menjadi sumber FIFO.
  Nilai dibagi quantity hanya representasi biaya dasar; pembagian berulang
  tidak boleh membuang residual nilai. FifoAllocation membulatkan turun partial
  scale6 dan menguras seluruh remaining_value pada full drain sesuai ADR-012.
- Snapshot SKU/nama barang, satuan/base unit, rasio, nama/kode gudang dan harga
  pokok disimpan pada posting. Rename master tidak mengubah histori.
- Tanggal posting berasal dari server; backdating/nomor dokumen custom ditunda.
  Nomor awal memakai prefix `SA-` dan ULID dokumen, bukan sequence global.

## Data Dan Arsitektur

Module app/Modules/Inventory/Stock dengan ServiceProvider sebagai composition
root. Presentation memanggil Application; Domain valuasi bebas Laravel.
Application memakai port repository serta lookup publik; adapter DB milik Stock
tidak mengimpor model privat module lain.

Tabel ULID: stock_openings, stock_opening_lines, stock_balances,
stock_movements, fifo_layers, stock_issues, stock_issue_lines, fifo_consumptions,
stock_receipts, stock_receipt_lines dan stock_return_allocations. Hanya
stock_openings draft menggunakan soft delete.
Lines draft diganti dalam transaksi saat edit; lines posted, balance, movement,
layer tidak menyediakan delete bebas. FK restrict menjaga referensi master.
Audit created/updated/deleted/restored/posted atomic dengan mutation.
Lock order: dokumen existing, gudang, satuan terurut, barang terurut; posting
pasangan kosong diserialisasi melalui lock gudang. Guard master memeriksa
histori setelah lock master yang sama, bukan query tanpa lock.

IssueStock memvalidasi source_id/source_number dan 1..100 source_line_id unik,
item_id serta base_quantity decimal string positif. Konversi satuan authoritative
menjadi tanggung jawab consumer POS; tidak menerima harga atau rasio bebas.
Action mengimplementasikan public SaleStockWriter, dengan consumer PostInvoice
POS melalui DTOs SaleStockRequest/SaleStockResult. Writer wajib transaction
caller; result issue ID dan map source_line_id/total_cost scale6, bukan model.
Action internal handle tetap tersedia untuk workflow Stock. Action mengunci
gudang, base units terurut, items terurut, balance dan
layers current read. Gudang menserialisasi pengeluaran setempat. Quantity/value
saldo harus sama dengan sisa layer sebelum mengeluarkan setiap baris.

Satu source sale hanya sekali: payload normal sama replay tanpa data/audit baru,
beda ditolak. Source_line_id unique lintas issue mencegah pemakaian ulang baris.
Saldo nol tetap ada, original layer tidak diubah. Movement sale negatif,
issue line/consumption positif. Audit wajib tersimpan (termasuk menolak veto),
actor eksplisit/null anonymous; posted tanpa edit/soft delete. Down migration
menolak jika stock_issues berisi data, bukan shortcut menghapus histori.

Kartu stok menampilkan snapshot movement sale/purchase dan fallback legacy opening lewat
left join tabel Stock sendiri. Field quantity/value dapat negatif. Pembayaran,
otorisasi HTTP posting dan atomicity lintas POS/Finance dimiliki PostInvoice
POS, bukan endpoint Stock bebas. UI checkout sudah tersedia di POS.

## Penerimaan Pembelian Penuh

ReceivePurchaseStock mengimplementasikan public PurchaseStockWriter::receive
dengan consumer PostPurchaseOrder. PurchaseStockRequest berisi warehouseId,
sourceId/sourceNumber, lines dan actorId nullable. Setiap baris source_line_id,
item_id/base_unit_id, base_quantity/total_value canonical decimal string positif.
PurchaseStockResult hanya receipt ID dan postedAt, bukan model Eloquent.
Transaction default caller wajib; tidak ada endpoint receipt Stock bebas.

Source purchase fixed; unique(source_type,source_id) dan source_line_id unique.
Hash canonical tanpa actor menjaga replay identik no-op; payload berbeda ditolak.
Master/unit aktif dan base unit cocok; lock gudang, base units/items sorted,
saldo dan layer current read, konsisten dengan writer sale. ReceiptBalance
memastikan saldo sama dengan jumlah sisa FIFO, tidak ada layer orphan, quantity/
value konsisten dan hasil penambahan tidak overflow. Satu receipt line menghasilkan
satu movement purchase positif dan layer FIFO, received_at waktu server.
Audit received wajib atomic; veto menggagalkan seluruh transaksi caller.

Saldo purchase-only tidak membutuhkan stok awal palsu: opening_line_id nullable,
snapshot sku/item_name/base_unit_name/warehouse_code/warehouse_name tersedia.
Query saldo left join opening dengan COALESCE metadata saldo/legacy, bukan inner
join yang menghilangkan saldo purchase-only. Movement receipt_line_id nullable
unique FK restrict. Receipt/line posted tidak memakai soft delete atau edit bebas.
Down migration receipt menolak histori receipt atau saldo tanpa opening;
rollback posting PurchaseOrders juga menolak dokumen posted/pointer receipt.
Guard histori Items/Warehouses tetap bekerja melalui movement purchase nyata.
Supplier lifecycle tetap dimiliki Purchasing dengan snapshot, bukan Stock.

Verifikasi dan aktivasi [penerimaan penuh](../../../work-items/purchase-full-receipt/README.md).
Tidak mencatat pembayaran/utang, menerima parsial atau menyediakan reversal.

## Guard Master Dan Public Boundary

Setelah barang punya histori stok, dasar tetap terkunci, satuan existing tidak
boleh dibuang atau rasionya diubah; penambahan satuan baru, barcode, flag dan
nama masih boleh diubah. Hapus/nonaktif barang atau gudang berhistori ditolak
pada scope awal yang belum menyediakan closing/reversal. Guard berlaku untuk
super-system. Tidak membaca model/repository privat lintas module.

## UI Dan Endpoint

Halaman resources/js/pages/inventory/stock dengan tab Stok awal, Saldo stok,
Kartu stok. Filter gudang/pencarian, pagination server dan detail dokumen.
Dialog draft, konfirmasi posting/lifecycle, Sonner, loading/empty/error,
shortcut dropdown dan navigasi command palette. Desktop/tablet; mobile ditunda.
View/manage/post adalah permission terpisah, tidak ada auto-grant.

## Seeder Dan QA

Permission-only seeder aman diulang. Demo terkait master demo menggunakan action
yang sama, idempotent tanpa mengubah posted data; hanya dijalankan eksplisit.
QA destructive memakai SQLite disposable. Migration lokal additive, tidak
menjalankan migrate:fresh pada MySQL user. Pengujian konkurensi MySQL perlu
dibuktikan terpisah; test SQLite tidak diklaim membuktikan row locking.

## Acceptance

- [x] Draft/lifecycle tidak mengubah saldo.
- [x] Posting exact, snapshot, atomic dan idempotent; stok awal kedua ditolak.
- [x] Guard master dan permission backend teruji termasuk super-system.
- [x] Saldo/kartu stok menggunakan ledger, bukan activity log.
- [x] UI desktop/tablet diverifikasi; gate dependency terpisah dicatat di tasks.md.
- [x] Tidak ada consumer FIFO pengeluaran palsu atau koreksi posted bebas.

## Posting Retur Pembelian

`PurchaseReturnStockWriter` adalah published boundary yang dipakai
Purchasing/Returns dalam transaksi bersama. Writer mengunci gudang, saldo dan
layer current read, mencari layer dari baris receipt pembelian asal, lalu
mengonsumsi hanya sisa layer tersebut. Saldo tetap dicek terhadap seluruh
layer barang/gudang. Issue, movement negatif, FIFO consumption dan audit
Stock berhasil bersama perubahan status/audit Returns atau semuanya rollback.
Biaya FIFO memakai remaining value layer asal, bukan harga beli snapshot atau
nilai refund supplier. Detail keputusan ada pada
[ADR-017](../../../decisions/ADR-017-PURCHASE-RETURN-ORIGINAL-RECEIPT-LAYER.md).
