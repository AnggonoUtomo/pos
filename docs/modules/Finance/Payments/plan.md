# Rencana Finance/Payments

1. Fondasi kas/bank dan metode: work item masters-foundation.
2. Payment record untuk checkout POS lunas disetujui pada
   [pos-paid-checkout](../../../work-items/pos-paid-checkout/README.md).
   Increment 1..3 selesai: Domain settlement, penerimaan/snapshot/replay dan
   guard histori internal. Increment 4 memasang consumer backend PostInvoice;
   UI checkout/recovery/receipt Sales/POS selesai pada Increment 5;
   penutupan/delivery keseluruhan selesai pada Increment 6.
3. [Pengeluaran pembelian lunas](../../../work-items/purchase-paid-settlement/plan.md):
   exact6, ledger terpisah/replay, consumer Purchasing, tanpa mutation Stock.
4. [Riwayat pembayaran](work-items/payment-history/plan.md): baca dua arah,
   filter/snapshot/permission khusus, tanpa mutation/aggregate saldo.
5. [Refund retur lunas](../../../work-items/sale-return-refund/plan.md): scope
   disetujui; rencana writer pengeluaran khusus refund, snapshot/replay/audit,
   metode aktif bebas dari split asal dan integrasi riwayat. Desain ledger
   khusus decimal30,2 dikunci pada ADR-016; writer/DTOs/choices, schema dan guard
   histori sudah dibuat di Increment 3. Orkestrasi Returns tersedia Increment 4;
   UI/history tersedia Increment 5; aktivasi lokal Increment 6 Ran batch22
   dua kali, tanpa payout/grant/demo; status delivery pada work item;
   penyelesaian nol milik Returns tanpa ledger uang. Bukan pembayaran purchase.
6. Reversal, piutang/utang dan mutasi/saldo umum memerlukan pekerjaan terpisah.

Jangan membuat saldo/payment record dari checklist fondasi master ini.
