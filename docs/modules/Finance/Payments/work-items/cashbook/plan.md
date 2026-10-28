# Rencana Buku Kas

1. Schema additive module, reader ledger gabungan dan action opening/mutation/
   transfer/reversal dengan port-adapter, idempotency dan audit. Tulis test
   dahulu untuk cutoff, exact6, split payment/kembalian, rollback dan akses.
2. UI modular Buku Kas, dialog konfirmasi, format Indonesia, shortcut,
   navigasi/palette, permission dan feedback Sonner. Tidak mengubah checkout.
3. Jalankan test terfokus selama implementasi; satu gate regresi akhir,
   schema MySQL disposable dan browser desktop/tablet terfokus. Update panduan,
   aktivasi additive tanpa opening/seed operasional otomatis, commit dan push.
