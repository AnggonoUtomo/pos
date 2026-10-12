# Tasks: Demo CCTV Dan Kelistrikan

## Sebelum Mulai

- [x] AGENTS.md, indeks docs, arsitektur, struktur dan dokumen Inventory dibaca.
- [x] Scope toko CCTV/kelistrikan dan langkah berikutnya disetujui user.
- [x] Data user, Old-docs, theme dan posted history tidak disentuh.

## Increment 1: Seeder Relasional

- [x] Ubah dataset Units dan Items, berikut regresi barcode/konversi.
  - Acceptance: 8 satuan, 9 barang, 18 relasi; dus/rol berbeda per barang.
  - Verification: UnitsSeederTest, ItemsSeederTest, InventoryDemoIntegrationTest.
- [x] Ubah demo stok melalui action create/post existing.
  - Acceptance: lima layer FIFO, biaya satuan pilihan tepat; repeat aman.
  - Verification: StockSeederTest, pengaman marker lama dan posted history.
- [x] Jalankan regresi Inventory dan PHP serta commit increment.
  - Acceptance: akses user tidak bertambah, data/audit atomic, suite lulus.
  - Verification: php artisan test, vendor/bin/pint --test.

## Increment 2: Dokumentasi Dan QA

Increment 1 Passed: RED 6 gagal/4 lulus pada test seed baru; sesudah implementasi
`php artisan test --compact`: 238 tests, 1421 assertions PASS.
`vendor/bin/pint --dirty`: PASS. Legacy posted dokumen/ledger tetap identik.

- [ ] Sesuaikan spesifikasi demo dan fixture QA browser/MySQL.
  - Acceptance: tidak ada consumer aktif yang masih bergantung SKU grocery.
  - Verification: pencarian terbatas source/tests/scripts, diff check.
- [ ] Jalankan QA disposable desktop/tablet dan MySQL.
  - Acceptance: posting, saldo dan FIFO tetap tepat; database user tidak direset.
  - Verification: scripts/qa/stock-browser.mjs, scripts/qa/stock-mysql.php.
- [ ] Catat bukti, commit dan push setelah seluruh pekerjaan selesai.
