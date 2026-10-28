# Purchasing/PurchaseOrders

Status: Active. Draft, penerimaan penuh dan pembayaran lunas, 17 Oktober 2026.
Harga beli wajib positif per satuan pilihan sejak draft disimpan.

Source app/Modules/Purchasing/PurchaseOrders, UI purchasing/purchase-orders.
Owner draft/header/baris, posting/status dan snapshot; bukan owner ledger stok atau finance.
Public dependency: Suppliers/SupplierLookup, Warehouses/WarehouseLookup,
Items/ItemStockLookup, Units/UnitLookup, Stock/PurchaseStockWriter.
Finance/Payments menyediakan PurchasePaymentWriter dan PurchasePaymentMethods.
Tidak mengambil model privat module lain.
`ReturnPurchaseLookup`/`ReturnPurchaseData` mempublikasikan projection aman
pembelian received untuk consumer Purchasing/Returns; adapter membaca snapshot
order/baris dan mencocokkan metadata receipt, tanpa payment/actor/notes.
`refundBasis` pada lookup yang sama menerbitkan ReturnPurchaseRefundBasisData
khusus nominal retur, sesudah pointer/nomor/total/waktu/snapshot pembayaran asal
cocok dengan published PurchasePaymentProofLookup Finance. Source unpaid atau
proof tidak cocok ditolak; tidak mengekspor akun/bank/actor/notes atau mengubah PO.

Lihat [spesifikasi](specification.md), [rencana](plan.md), [checklist](tasks.md)
dan [fondasi draft](work-items/draft-foundation/README.md), serta
[penerimaan penuh](../../../work-items/purchase-full-receipt/README.md), serta
[pembayaran lunas](../../../work-items/purchase-paid-settlement/README.md).

Aktif lokal: [Order pembelian](http://pos.test/purchasing/purchase-orders),
migration draft batch15, receipt Stock batch16 dan posting PurchaseOrders batch17
Ran. Pengeluaran Finance batch18 dan pointer pembayaran batch19 Ran.
Permission view/manage/post/pay terpisah, catalog tanpa auto-grant. Aktivasi
tidak menambah draft demo, mem-posting draft user atau mengubah data lama.
Demo module hanya eksplisit pada local/testing dan QA disposable. Penerimaan
sekali penuh menambah saldo/movement/FIFO atomic; replay versi asal tidak
menggandakan ledger. Posted readonly. Alur utama MVP memakai Terima dan bayar:
receipt/FIFO, status dan pelunasan commit bersama melalui ReceiveAndPayPurchase.
Endpoint receive-and-pay memerlukan post dan pay sekaligus, menerima version
draft/payments; replay memakai versi draft yang sama, gagal payment rollback
receipt. Jalur pembayaran terpisah dipertahankan untuk dokumen lama yang sudah
diterima. Multi-metode harus tepat tanpa kembalian, snapshot readonly dan retry
tidak menambah pengeluaran. Data lama tetap Belum dicatat, bukan otomatis lunas.
Parsial, utang/jatuh tempo, running balance kas/bank dan reversal belum dibuat.
Retur barang/penerimaan uang dimiliki [Purchasing/Returns](../Returns/README.md),
bukan mutation source PO. QA: [Quality](../../../QUALITY.md#qa-pembayaran-pembelian-lunas).

PurchaseReportReader menerbitkan ringkasan posted readonly untuk Reporting.
Checklist MVP: [empat paket](../../../work-items/mvp-ready/tasks.md).

Draft mendukung [barcode scanner](../../../work-items/barcode-scanner/README.md):
metadata dari ItemStockLookup, Alt+B/Enter, pilih barang/satuan beli dan qty1,
scan ulang satuan sama menambah1 tanpa mengubah harga. Beda satuan pada barang
yang sudah ada ditolak (invariant satu barang satu baris tetap). Enter scanner
tidak submit; biaya harus diisi dan stok hanya bertambah setelah posting.
