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
5. Reversal, piutang/utang dan mutasi/saldo umum memerlukan pekerjaan terpisah.

Jangan membuat saldo/payment record dari checklist fondasi master ini.
