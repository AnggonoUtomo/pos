# Tasks

- [x] User menyetujui perbaikan terpisah tanpa perubahan data lama.
- [x] Telusuri ISO UTC dari Stock dan penyimpanan datetime header retur.
- [x] Regresi RED/GREEN pada dua timezone dan batas eksklusif hari berikutnya.
- [x] Patch dua reader count; settlement dan filter gudang tetap.
- [x] Regresi dashboard/laporan/retur, review dan commit terpisah.
- [x] Push bersama penutupan paket opname.

Reproduksi: 2 FAIL/6 PASS dari 8 kasus posting nyata, UTC/WIB dan pergantian
hari/bulan. Sesudah patch, paket terdampak dashboard, laporan, retur kedua
arah dan lifecycle latihan: 104 PASS/981 assertion. Fingerprint raw return,
ledger dan audit identik sebelum/sesudah query; tidak ada migrasi data lama.
