# Rencana PurchaseOrders

1. [Fondasi draft](work-items/draft-foundation/plan.md): selesai.
2. [Penerimaan penuh/FIFO](../../../work-items/purchase-full-receipt/plan.md):
   diimplementasikan; full sekali, atomic, replay aman, posted readonly.
3. [Pembayaran lunas supplier](../../../work-items/purchase-paid-settlement/plan.md):
   multi-metode exact, posted saja, snapshot dan pengeluaran atomic, tidak mutasi stok.
4. Utang/parsial/jatuh tempo/uang muka/reversal: pekerjaan terpisah.
5. Dukungan [draft retur pembelian](../Returns/work-items/draft-foundation/README.md):
   lookup/DTOs telah diimplementasikan. Published read lookup/DTOs snapshot
   posted untuk consumer Returns, tanpa ekspor model/repository/payment metadata.
   Penerimaan penuh menjadikan quantity sumber sebagai jumlah diterima; pembayaran
   tidak menulis ulang baris. Tidak menambah mutation stok/Finance pada draft retur.
6. [Basis penerimaan uang retur](../../../work-items/purchase-return-refund/plan.md):
   refundBasis memakai proof pembayaran Finance untuk published snapshot paid
   authoritative exact6; no mutation source. Consumer settlement tetap Returns.

Checklist ini bukan instruksi membuat utang, pembayaran/penerimaan parsial atau reversal.
