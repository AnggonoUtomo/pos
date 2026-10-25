# Rencana Implementasi

1. Fondasi module dan behavior: generator canonical, migration singleton/runs,
   permission, read/update/version/audit, jadwal due/claim/retry dan adapter
   pos:backup existing. Test terfokus sebelum implementasi.
2. UI dan cetak: form shadcn/ui, shortcut/Sonner, sidebar/palette, shared
   PrintingPreferences, receipt format A4/58/80 dan preview non-transaksional.
3. Scheduler dan aktivasi: admin deployment memanggil schedule:run setiap menit
   melalui cron Linux atau penjadwal OS yang sesuai, tanpa installer otomatis.
   Migration/seeder tanpa reset/grant/demo aktif,
   QA config/print desktop/tablet dan test scheduler dengan executor fake.

Tidak mengulang suite MVP yang tidak berubah. Gate akhir terfokus meliputi
backend module/middleware/receipt, Pint, lint/typecheck/build dan browser satu
alur konfigurasi. Backup dump nyata hanya sekali untuk runner bila diperlukan,
bukan mengulang seluruh transaksi. UAT manusia tetap pending.
