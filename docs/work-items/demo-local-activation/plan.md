# Rencana Aktivasi Demo

1. Increment 1: provisioning demo lokal berurutan dalam transaction, dengan
   fingerprint data existing. Verifikasi ulang idempotency dan migration status.
2. Increment 2: katalog POS khusus CCTV/kelistrikan, satuan dan level yang
   konsisten dengan demo; regresi data mock, lint/typecheck/build, browser
   desktop/tablet dan dokumentasi hasil aktual.

Commit otomatis tiap increment lulus, push setelah semua selesai. Data existing
tetap ada; penggantian/pembersihan data lama membutuhkan instruksi terpisah.
