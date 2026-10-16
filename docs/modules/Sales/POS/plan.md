# Rencana Sales/POS

1. Data dan keranjang nyata: work item pos-live-cart, tanpa posting.
2. Checkout lunas/multi-payment disetujui; pekerjaan berjalan pada
   [pos-paid-checkout](../../../work-items/pos-paid-checkout/README.md).
   Increment 1..5 selesai: backend atomic/idempotency/FIFO/payment serta UI
   multi-payment/recovery/receipt. Berikutnya Increment 6 QA penutupan/delivery.
3. Kredit, input diskon/pajak dan reversal memerlukan pekerjaan terpisah.

Jangan membuat implementasi tahap kedua dari checklist tahap pertama.
