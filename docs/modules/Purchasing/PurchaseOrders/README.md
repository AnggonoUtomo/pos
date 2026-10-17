# Purchasing/PurchaseOrders

Status: Active. Draft dan penerimaan penuh terverifikasi 17 Oktober 2026.
Harga beli wajib positif per satuan pilihan sejak draft disimpan.

Source app/Modules/Purchasing/PurchaseOrders, UI purchasing/purchase-orders.
Owner draft/header/baris, posting/status dan snapshot; bukan owner ledger stok atau finance.
Public dependency: Suppliers/SupplierLookup, Warehouses/WarehouseLookup,
Items/ItemStockLookup, Units/UnitLookup, Stock/PurchaseStockWriter.
Tidak mengambil model privat module lain.

Lihat [spesifikasi](specification.md), [rencana](plan.md), [checklist](tasks.md)
dan [fondasi draft](work-items/draft-foundation/README.md), serta
[penerimaan penuh](../../../work-items/purchase-full-receipt/README.md).

Aktif lokal: [Order pembelian](http://pos.test/purchasing/purchase-orders),
migration draft batch15, receipt Stock batch16 dan posting PurchaseOrders batch17
Ran. Permission view/manage/post terpisah, catalog tanpa auto-grant. Aktivasi
tidak menambah draft demo, mem-posting draft user atau mengubah data lama.
Demo module hanya eksplisit pada local/testing dan QA disposable. Penerimaan
sekali penuh menambah saldo/movement/FIFO atomic; replay versi asal tidak
menggandakan ledger. Posted readonly. Parsial, utang/pembayaran, retur/reversal
belum dibuat. QA/aktivasi: [Quality](../../../QUALITY.md#qa-penerimaan-pembelian-penuh).
