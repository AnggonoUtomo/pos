# Finance/Payments

Master kas/bank/metode serta penerimaan penjualan immutable melalui consumer POS.
Menyimpan applied bersih, tendered/change dan snapshot; bukan running balance
atau full accounting.

Writer internal `SaleRefundPaymentWriter` menyediakan pengeluaran refund
decimal30,2 dengan ledger terpisah, snapshot/replay/audit dan guard histori.
Consumer settlement Returns/endpoint/UI dan riwayat refund tersedia; migration
lokal Ran batch22 tanpa payout/demo/grant otomatis.
Dokumen canonical: [Finance/Payments](../../../../docs/modules/Finance/Payments/README.md).
