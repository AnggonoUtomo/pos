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

- [x] Buka/tutup di POS, status shift, riwayat/review, navigasi dan shortcut.
  - Acceptance: kedua view tetap bekerja; keranjang tidak hilang saat dialog
    shift; pending checkout tetap dipulihkan tanpa mengganti shift source.
  - Verification: lint/type/build dan desktop/tablet disposable.
- [x] MySQL schema/locking/replay dan gate akhir, review patch.
- [ ] Panduan, backup verified, migration/catalog additive tanpa modal/shift
  produksi otomatis, update checklist, commit/push dan remote verification.

## Verifikasi Akhir, 29 Oktober 2026

- `npx tsc --noEmit`, lint frontend terfokus (0 warning), `npm run build`:
  PASS. Formatter hanya file terkait. Review correctness/boundary/security/
  lifecycle: PASS; bukan audit dependency atau fitur di luar scope.
- Chrome DevTools MCP: BLOCKED, tool tidak tersedia pada discovery. Fallback
  Playwright existing/channel Chrome tanpa instalasi: PASS desktop1440x1000,
  tablet1024x768; kedua view mempertahankan keranjang saat dialog shift,
  checkout nyata, tutup berselisih, supervisor review, console bersih,
  screenshot framing dan body overflow diperiksa.
- Browser menemukan validasi recovery ULID uppercase terlalu ketat;
  diperbaiki case-insensitive dan rerun alur terkait PASS. Retry setelah
  respons open committed hilang + reload memakai URL/payload sama, bukan shift
  baru. Artefak ignored: `storage/framework/testing/cashier-shifts-b2590e63aac9bc83`.
- Native MySQL `php scripts/qa/cashier-shifts-mysql.php`: PASS dua worker berebut
  laci, race checkout/close, replay invoice sesudah tutup dan decimal38,6;
  database disposable dibersihkan. QA tidak menulis database toko.
- Backup `php artisan pos:backup --verify`: PASS, restore disposable identik,
  `backups/pos-20261029-034112-5b82dfe4.sql` (ignored).
- Aktivasi `php scripts/qa/activate-cashier-shifts-local.php`: PASS fingerprint
  seluruh kolom data lama, user/role/grant/master/ledger identik. Schema
  `2026_10_28_000002_create_cashier_shifts` Ran batch32, delapan route tampil
  setelah route cache. Dua permission catalog ditambahkan, tidak auto-grant.
  Opening/shift produksi 0, invoice existing tidak di-backfill; login HTTPS200.
- Panduan baru `docs/user-guide/SHIFT-KASIR.md`; Markdown kasir dan indeks
  diperbarui. PDF global/per-role lama belum memuat tambahan Shift Kasir,
  dicatat eksplisit. UAT/printer/tablet LAN/backup terpisah lama tidak dibuka
  atau dinyatakan selesai. Old-docs, .env produksi dan file LSP tidak disentuh.
