# Rencana Increment

1. Generator dry-run, test RED, projection/query/permission dan regresi backend.
2. UI filter/tabel/detail, sidebar/palette, panduan in-app dan operator.
3. Gate akhir, QA browser terfokus, aktivasi catalog tanpa grant serta refresh
   route cache produksi, cek readonly/fingerprint, commit dan push.

Tidak menjalankan migration/seed demo/transaction di database toko. Catalog
permission hanya additive; existing role/grant tidak diubah. Demo seeder skip.
