# Tasks: Shift Kasir

## Sebelum Mulai

- [x] AGENTS, README, arsitektur, struktur, POS dan Payments dibaca.
- [x] Empat keputusan bisnis utama dikonfirmasi sebelum coding.
- [x] Scope, dependency dan increment dicatat; tidak mengaktifkan fitur lain.

## Increment 1: Lifecycle Dan Boundary Saldo

- [x] Schema ULID dan constraint satu kasir/laci aktif; invoice nullable shift.
  - Acceptance: additive, invoice lama tetap utuh, shift tidak soft delete.
  - Verification: schema disposable dan status migration.
- [x] Contract/DTOs saldo Finance dan lifecycle buka/tutup/review POS.
  - Acceptance: tidak menggandakan uang; alasan selisih, review dan snapshot
    tutup audited/idempotent, ownership tidak dilewati super-system.
  - Verification: feature tests dan audit rollback.

## Increment 2: Checkout Dan Fixture

Hasil Increment 1: `CashierShiftTest` 5 PASS / 54 assertions, schema SQLite
disposable; gabungan POS dan history 37 PASS / 340 assertions setelah pengaman
checkout ditambahkan. MySQL/concurrency dan aktivasi dijalankan pada gate akhir.

- [x] Wajib shift untuk checkout baru, metode tunai sesuai laci.
  - Acceptance: invoice terhubung atomic, replay lama setelah tutup tetap aman,
    mismatch/shift tutup ditolak tanpa stock/payment mutation.
  - Verification: focused POS tests dan concurrency checkout/close.
- [x] Sesuaikan fixture/demo terkait tanpa guard testing palsu atau reset toko.
  - Acceptance: kasus stok/retur/refund existing tetap relevan.
  - Verification: test terfokus dan satu gate regresi akhir.
- [x] Commit backend setelah gate relevan lulus.

Hasil Increment 2: focused POS/history 37 PASS/340 assertions; setelah test
checkout/replay/mismatch ditambah, gabungan lifecycle/POS/shift 34 PASS/322.
Gate lengkap 872 test: 870 PASS, dua FAIL fixture ownership tertahan dan jumlah
catalog permission lama. Keduanya diperbaiki, ditambah test akun aktif; rerun
terdampak `CashierShiftTest|HeldCartTest|PosReadBoundaryTest`: 19 PASS/188.
Tidak mengulang seluruh suite setelah perubahan hanya pada fixture/test.
Native MySQL disposable PASS: eksklusivitas dua worker, checkout/replay setelah
tutup, benturan checkout/close dan snapshot saldo, decimal38,6; cleanup PASS.
Dataset latihan kosong kini membuka Buku Kas/shift nyata dan menutupnya, tanpa
bypass. Reset legacy tetap menolak SEMUA data Buku Kas/shift existing, dan
tidak dijalankan pada database toko. Simulasi legacy hanya pada test disposable.

## Increment 3: UI, Aktivasi Dan Delivery

- [ ] Buka/tutup di POS, status shift, riwayat/review, navigasi dan shortcut.
  - Acceptance: kedua view tetap bekerja; keranjang tidak hilang saat dialog
    shift; pending checkout tetap dipulihkan tanpa mengganti shift source.
  - Verification: lint/type/build dan desktop/tablet disposable.
- [ ] MySQL schema/locking/replay dan gate akhir, review patch.
- [ ] Panduan, backup verified, migration/catalog additive tanpa modal/shift
  produksi otomatis, update checklist, commit/push dan remote verification.
