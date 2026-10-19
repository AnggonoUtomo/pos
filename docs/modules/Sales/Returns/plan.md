# Rencana Retur Penjualan

1. [Fondasi draft](work-items/draft-foundation/plan.md): kontrak invoice,
   persistence, validasi, audit, permission, UI dan QA.
2. [Posting barang layak jual](../../../work-items/sale-return-posting/README.md):
   selesai diverifikasi dan aktif lokal sesuai ADR-015. Layer baru, biaya FIFO
   asal/biaya nol turunan, batas kumulatif, Stock atomic/replay dan readonly;
   refund, nominal refund dan Finance tidak termasuk.
3. [Refund lunas](../../../work-items/sale-return-refund/README.md): scope
   disetujui, nominal otomatis scale2/residual, metode aktif boleh berbeda,
   penyelesaian nol tanpa ledger. Increment 2 menambahkan Domain valuasi dan
   ADR-016. Increment 3 writer Finance dan Increment 4 backend quote/settlement
   sudah diverifikasi; UI/history Increment 5 tersedia. Aktivasi lokal dan delivery
   keseluruhan tetap Increment 6.
4. Karantina/tukar/reversal masih pending persetujuan pada pekerjaan terpisah.

Tahap posting memakai checklist pekerjaan sendiri, bukan checklist draft.
