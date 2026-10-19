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
menggandakan ledger. Posted readonly; pembayaran melalui operasi terpisah setelah
diterima. Multi-metode harus tepat tanpa kembalian, snapshot readonly dan retry
tidak menambah pengeluaran. Data lama tetap Belum dicatat, bukan otomatis lunas.
Parsial, utang/jatuh tempo, running balance kas/bank dan retur/reversal belum
dibuat. QA/aktivasi: [Quality](../../../QUALITY.md#qa-pembayaran-pembelian-lunas).
