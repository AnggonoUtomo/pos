# Rencana Retur Pembelian

1. [Fondasi draft](work-items/draft-foundation/plan.md): selesai dan terverifikasi.
   Snapshot sumber, quantity, lifecycle/audit/permission/demo dan UI belum
   memutasi ledger.
2. [Posting retur pembelian](../../../work-items/purchase-return-posting/plan.md):
   implementasi, backend dan QA visual selesai. Seluruh draft diposting saat barang
   diserahkan; hanya sisa layer penerimaan pembelian asal yang boleh keluar.
3. [Penerimaan uang supplier](../../../work-items/purchase-return-refund/plan.md):
   implementasi/QA/aktivasi lokal PASS; delivery pada work item. Hanya pembelian
   lunas, nominal otomatis snapshot exact6,
   diterima penuh sekali. Kredit supplier/utang/koreksi tetap terpisah.

Langkah 2 tidak membuat mutasi Finance. Langkah 3 mengikuti ADR-018 dan work item
tersendiri dengan checklist sebelum/sesudah coding.
Checklist: [tasks.md](tasks.md).
