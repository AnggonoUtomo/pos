# Rencana Finance/Payments

1. Fondasi kas/bank dan metode: work item masters-foundation.
2. Payment record untuk checkout POS lunas disetujui pada
   [pos-paid-checkout](../../../work-items/pos-paid-checkout/README.md).
   Increment 1..3 selesai: Domain settlement, penerimaan/snapshot/replay dan
   guard histori internal. Checkout publik menunggu orkestrasi Increment 4..5.
3. Reversal, piutang dan mutasi/saldo umum memerlukan pekerjaan terpisah.

Jangan membuat saldo/payment record dari checklist fondasi master ini.
