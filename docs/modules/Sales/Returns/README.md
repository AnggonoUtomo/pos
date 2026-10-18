# Retur Penjualan

Module `Sales/Returns` memiliki draft dan posting retur barang layak jual dari
invoice penjualan posted.
Lihat [Spesifikasi](specification.md), [Rencana](plan.md), dan
[Checklist](tasks.md). Pekerjaan: [Fondasi draft](work-items/draft-foundation/README.md)
dan [Posting retur](../../../work-items/sale-return-posting/README.md).

Dependency publik: `Sales/POS/Application/Contracts/ReturnInvoiceLookup` untuk
snapshot invoice asal dan `Inventory/Stock/Application/Contracts/SaleReturnStockWriter`
untuk penerimaan stok, bukan model/repository privat module lain.

Status: Active untuk draft dan posting fisik; refund/karantina/tukar belum tersedia.
URL `/sales/returns`, permission `sales.returns.view`, `sales.returns.manage`,
serta `sales.returns.post` terpisah. Posted readonly, replay aman, FIFO biaya
asal/residual exact, batas kumulatif dan audit atomic sudah diuji.
Migration draft Ran batch20, posting Ran batch21; aktivasi additive tanpa
demo/grant/reset akun atau posting otomatis.
