# Rencana Sales/POS

1. Data dan keranjang nyata: work item pos-live-cart, tanpa posting.
2. Checkout lunas/multi-payment disetujui; pekerjaan berjalan pada
   [pos-paid-checkout](../../../work-items/pos-paid-checkout/README.md).
   Increment 1..4 selesai: backend posting atomic/idempotency/FIFO/payment.
   Berikutnya Increment 5 untuk UI checkout/receipt; tombol masih disabled.
3. Kredit, input diskon/pajak dan reversal memerlukan pekerjaan terpisah.

Jangan membuat implementasi tahap kedua dari checklist tahap pertama.
