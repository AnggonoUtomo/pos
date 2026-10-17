# Rencana PurchaseOrders

1. [Fondasi draft](work-items/draft-foundation/plan.md): selesai.
2. [Penerimaan penuh/FIFO](../../../work-items/purchase-full-receipt/plan.md):
   diimplementasikan; full sekali, atomic, replay aman, posted readonly.
3. [Pembayaran lunas supplier](../../../work-items/purchase-paid-settlement/plan.md):
   multi-metode exact, posted saja, snapshot dan pengeluaran atomic, tidak mutasi stok.
4. Utang/parsial/jatuh tempo/uang muka/reversal: pekerjaan terpisah.

Checklist ini bukan instruksi membuat utang, pembayaran/penerimaan parsial atau reversal.
