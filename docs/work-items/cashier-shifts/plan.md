# Rencana

1. Published CashDrawerPosition/DTOs Finance, schema shift dan invoice nullable,
   action/repository/reader POS. Test lifecycle, kepemilikan, eksklusivitas,
   saldo, selisih, review, audit rollback dan replay sebelum coding implementasi.
2. Pasang invariant checkout baru dan tunai; pertahankan recovery invoice lama.
   Adaptasi fixture/demo secara eksplisit, bukan menonaktifkan guard saat test.
3. UI POS buka/tutup serta halaman riwayat/review; shortcut, Sonner, permission,
   retry dan panduan. Test terfokus, satu gate akhir dan QA desktop/tablet serta
   native MySQL/concurrency. Aktivasi additive setelah backup, commit/push.
