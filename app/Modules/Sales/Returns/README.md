# Sales/Returns

Owner draft retur penjualan. Public dependency hanya ReturnInvoiceLookup dari
Sales/POS. Route/database/seeder milik module dimuat ServiceProvider.

Dokumen: docs/modules/Sales/Returns/ dan docs/work-items/sale-return-posting/.
Posting memakai SaleReturnStockWriter: gudang asal, biaya FIFO asal, layer baru,
trace kumulatif, shared transaction/replay dan posted readonly. Refund belum ada.
