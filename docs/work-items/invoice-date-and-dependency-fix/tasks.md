# Tasks: Filter Tanggal dan Dependency

## Sebelum Mulai

- [x] Baca AGENTS, indeks docs, dokumen Sales/POS dan aturan QA relevan.
- [x] Reproduksi test gagal dan kedua advisory pada dependency terpasang.
- [x] Batasi scope; simpan perubahan LSP user tanpa disentuh.

## Increment 1: Regresi Tanggal WIB

- [x] Perbaiki fixture UTC eksplisit sesuai timezone penyimpanan aplikasi.
  - Acceptance: awal/akhir hari dan hari berikutnya tidak tertukar.
  - Verification: InvoiceHistoryTest dan coverage UTC/Asia/Jakarta.
- [x] Pastikan filter dan response tidak mengubah data transaksi.
  - Acceptance: ownership, snapshot, pagination, readonly tetap lulus.
  - Verification: focused suite dan review diff.
- [x] Commit save point setelah verifikasi increment lulus.

Bukti: reproduksi awal 1 FAIL/4 assertion; sesudah koreksi
`php artisan test --compact --filter=InvoiceHistoryTest`: 15 PASS/136 assertion.
`vendor/bin/pint --test tests/Feature/Modules/Sales/POS/InvoiceHistoryTest.php`
PASS. Tidak perlu mengubah query runtime atau timezone produksi.

## Increment 2: Patch Security

- [ ] Tambah reproduksi advisory dan kontrol normal tanpa jaringan/delivery.
  - Acceptance: input berbahaya gagal pada versi lama dan aman pada patch.
  - Verification: probe qs dan test MIME, sebelum/sesudah update.
- [ ] Update dependency terarah tanpa major/stack/dependency baru.
  - Acceptance: lockfile hanya package yang dibutuhkan; audit bersih.
  - Verification: diff, npm audit, composer audit --locked/validate.
- [ ] Commit save point setelah verifikasi increment lulus.

## Sesudah Coding

- [ ] Satu regresi backend lengkap dan gate frontend terdampak.
- [ ] QA browser filter desktop/tablet atau catat blocker dan fallback.
- [ ] Review final, git diff --check, perbarui catatan gap terkait.
- [ ] Commit penutupan, push dan verifikasi divergence remote.
