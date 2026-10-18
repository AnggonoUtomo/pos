# Rencana Retur Penjualan

1. [Fondasi draft](work-items/draft-foundation/plan.md): kontrak invoice,
   persistence, validasi, audit, permission, UI dan QA.
2. [Posting barang layak jual](../../../work-items/sale-return-posting/README.md):
   selesai diverifikasi dan aktif lokal sesuai ADR-015. Layer baru, biaya FIFO
   asal/biaya nol turunan, batas kumulatif, Stock atomic/replay dan readonly;
   refund, nominal refund dan Finance tidak termasuk.
3. Pending persetujuan: refund/karantina/tukar/reversal pada pekerjaan terpisah.

Tahap posting memakai checklist pekerjaan sendiri, bukan checklist draft.
