# Work Item: Penerimaan Pembelian Penuh

Status In Progress, 17 Oktober 2026. Baseline71b9ca5. User menyetujui terima penuh
satu kali per draft; partial receiving, utang dan pembayaran tidak termasuk.
Owner Purchasing/PurchaseOrders (dokumen/status/snapshot) dan Inventory/Stock
(receipt ledger, saldo, movement, FIFO). [PRD](PRD.md), [plan](plan.md), [tasks](tasks.md).

## Contract

POST /purchasing/purchase-orders/{id}/post, payload version saja (CSRF diizinkan),
permission purchasing.purchase-orders.post, auth/verified/web/CSRF. Belum memberi
view/manage otomatis. JSON200 id/status/version/stock_receipt_id/posted_at;
web redirect dengan Sonner. Input invalid/stale/invariant422, id404, forbidden403.

PostPurchaseOrder mengunci dokumen+baris, membandingkan revision, revalidasi master
aktif melalui PreparePurchaseOrder dan public lookup. Ratio/base unit berubah
atau total draft tidak konsisten wajib edit/simpan ulang; kuantitas/harga tidak
diubah oleh POST. Snapshot nama/SKU diperbarui ketika posting, histori kemudian
immutable. Setelah posted, edit/delete/restore ditolak termasuk super-system.
posted_from_version menyimpan version request pertama; replay version yang sama
mengembalikan hasil lama sebelum lookup master, tanpa menambah stok/audit/revision.
Version lain ditolak. Transaksi caller mencakup status/baris/audit Purchasing dan
seluruh receipt/saldo/movement/layer/audit Stock, retry deadlock3.

PurchaseStockWriter::receive(PurchaseStockRequest): PurchaseStockResult merupakan
public boundary Stock dengan consumer PostPurchaseOrder, bukan repository privat.
DTO request warehouseId/sourceId/sourceNumber/lines/actorId. Lines berisi
source_line_id/item_id/base_unit_id/base_quantity/total_value canonical string.
Writer memerlukan transaction default caller; memvalidasi payload, lock gudang,
source, base units/items sorted, saldo/layer. Source_type purchase fixed;
unique(source_type,source_id), source_line_id unique. Hash payload canonical
melindungi replay beda isi. Saldo lama harus sama dengan jumlah sisa FIFO;
saldo baru hanya jika tidak ada layer orphan. Quantity/value positif exact,
penambahan saldo overflow ditolak. FIFO receipt memakai waktu server, per item+
warehouse, satu movement purchase dan satu layer per baris. Audit wajib tersimpan.

stock_receipts/stock_receipt_lines adalah immutable ledger tanpa soft delete;
tidak memiliki endpoint mutation bebas. stock_balances.opening_line_id menjadi
nullable agar saldo dapat bermula dari purchase tanpa opening palsu; metadata
snapshot saldo ditambah untuk fallback query legacy. Movement menambah receipt_line_id
FK/unique. Query saldo memakai left join opening + snapshot fallback, bukan inner
join yang menyembunyikan saldo purchase-only. Guard item/gudang existing via
StockUsage tetap berlaku karena movement purchase nyata. Supplier dapat nonaktif/
soft-delete setelah transaksi, seperti master pihak dengan snapshot; histori
tetap FK/snapshot dan tidak hilang. Posting baru membutuhkan supplier aktif.

Migration Stock000003 lebih dulu dari PurchaseOrders000004. FK receipt pointer
di PurchaseOrders restrict. Rollback kedua migration menolak bila ada receipt/
purchase posted; tidak menghapus histori sebagai rollback. Migration additive
tanpa backfill data bisnis. Detail keputusan [ADR013](../../decisions/ADR-013-PURCHASE-RECEIPT-LEDGER.md).

## Non-Scope Dan Aktivasi

Tidak ada payment/utang, pajak/diskon, retur/void/reversal, penerimaan parsial,
tanggal manual, nomor invoice supplier, cetak, theme/dependency/auth baru.
Demo posting hanya QA/disposable atau seeder eksplisit local/testing; tidak
otomatis mem-posting draft user atau mengubah stok saat aktivasi lokal.
Aktivasi hanya schema dan permission catalog, fingerprint seluruh data lama
identik. Old-docs/SampleUI/empat LSP/credential tidak disentuh.
