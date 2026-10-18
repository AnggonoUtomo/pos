# Sales/Returns

Owner draft/posting fisik dan backend quote/settlement refund retur penjualan.
Public dependency ReturnInvoiceLookup dari Sales/POS, SaleReturnStockWriter dari
Stock serta SaleRefundPaymentWriter/Methods dari Finance. Route/database/seeder
milik module dimuat ServiceProvider.

Dokumen: docs/modules/Sales/Returns/ dan docs/work-items/sale-return-posting/.
Posting memakai SaleReturnStockWriter: gudang asal, biaya FIFO asal, layer baru,
trace kumulatif, shared transaction/replay dan posted readonly. Refund memakai
scope invoice, alokasi exact/nol, audit dan replay tanpa mengubah Stock lagi.
UI/history dan aktivasi migration lokal menyusul. Dokumen canonical:
[Refund](../../../../docs/work-items/sale-return-refund/README.md).
