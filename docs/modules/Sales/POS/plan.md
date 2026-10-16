# Rencana Sales/POS

1. Data dan keranjang nyata: work item pos-live-cart, tanpa posting.
2. Checkout lunas/multi-payment selesai dan delivery terverifikasi pada
   [pos-paid-checkout](../../../work-items/pos-paid-checkout/README.md).
   Increment 1..6 selesai: backend atomic/idempotency/FIFO/payment, UI
   multi-payment/recovery/receipt serta QA penutupan/delivery.
3. Riwayat readonly/filter/detail/cetak ulang pada
   [invoice-history](work-items/invoice-history/README.md); implementasi,
   QA akhir/delivery selesai, termasuk patch dependency terpisah yang disetujui.
4. Kredit, input diskon/pajak dan reversal memerlukan pekerjaan terpisah.

Jangan membuat implementasi tahap kedua dari checklist tahap pertama.
