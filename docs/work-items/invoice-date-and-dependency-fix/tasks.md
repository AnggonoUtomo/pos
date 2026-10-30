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

- [x] Tambah reproduksi advisory dan kontrol normal tanpa jaringan/delivery.
  - Acceptance: input berbahaya gagal pada versi lama dan aman pada patch.
  - Verification: probe qs dan test MIME, sebelum/sesudah update.
- [x] Update dependency terarah tanpa major/stack/dependency baru.
  - Acceptance: lockfile hanya package yang dibutuhkan; audit bersih.
  - Verification: diff, npm audit, composer audit --locked/validate.
- [x] Commit save point setelah verifikasi increment lulus.

Bukti: qs 6.16.0 mereproduksi TypeError; MIME 7.4.18 gagal 32 input
invalid dengan 1 kontrol normal lulus. Setelah update qs 6.17.1/MIME 7.4.22:
probe parser PASS dan 33 test MIME/36 assertion PASS. Hanya dua package lock
berubah; constraint manifest dan stack tetap. Audit npm penuh/production serta
Composer locked bersih. composer validate --strict dan check-platform-reqs PASS.

## Sesudah Coding

- [x] Satu regresi backend lengkap dan gate frontend terdampak.
- [x] QA browser filter desktop/tablet atau catat blocker dan fallback.
- [ ] Review final, git diff --check, perbarui catatan gap terkait.
- [ ] Commit penutupan, push dan verifikasi divergence remote.
