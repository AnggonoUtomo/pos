# Tasks: Checkout POS Lunas Dan Multi-Payment

## Sebelum Mulai

- [x] AGENTS, docs/README, ARCHITECTURE dan FOLDER-STRUCTURE dibaca.
- [x] Dokumen/source POS, Stock, Payments dan pola nilai exact dipelajari.
- [x] Scope lunas/multi-payment dan satu cash/kembalian disetujui user.
- [x] Target tiga module dan batas Increment 1 diinformasikan sebelum coding.
- [x] Dampak schema/route/permission/UI/seeder/audit/ledger terpetakan di README.
- [x] QA unit/full/regresi, MySQL dan desktop/tablet direncanakan.
- [x] Gap movement/query opening-only dan read boundary non-locking dicatat.

## Increment 1: Aturan Dan Presisi

- [x] Tulis PRD/work item/ADR dan tautan roadmap tiga module.
  - Acceptance: aturan lunas, cash bersih, presisi/residual, scope/non-scope
    dan status implementasi dibedakan; checklist increment berikutnya tersedia.
  - Verification: review source/dokumen, link lokal dan git diff --check.
- [x] Implementasikan PaymentSettlement pada Domain Finance melalui test RED.
  - Acceptance: money scale2 exact, satu cash, unique metode, kurang bayar/
    non-cash berlebih/tunai tanpa kontribusi ditolak, output net benar dan
    urutan input tidak mengubah hasil. Belum membaca/menulis payment master.
  - Verification: PaymentSettlementTest, termasuk overflow/invalid input.
- [ ] Implementasikan FifoAllocation pada Domain Stock melalui test RED.
  - Acceptance: lapisan oldest-first yang sudah disiapkan caller dialokasi,
    quantity/value scale6, sisa tidak minus, residual terkuras tepat, layer
    invalid/duplikat/stok kurang/overflow ditolak. Tidak mengklaim lock DB.
  - Verification: FifoAllocationTest, beberapa layer dan pengurasan berulang.
- [ ] Jalankan regresi/full suite, review, update status dan commit.
  - Acceptance: Domain bebas framework/layer luar, runtime existing tetap,
    tidak ada port/event/schema/route/UI/database lokal berubah.
  - Verification: focused/full test, Pint scoped, import/diff/staging review.

## Increment 2: Pengeluaran Stock

- [ ] Migration additive movement sumber sale dan alokasi layer.
  - Acceptance: opening existing tidak diubah; posted tidak soft delete,
    traceability sumber dan uniqueness mencegah consumption ganda.
  - Verification: migration up/down disposable, persistence/FK tests.
- [ ] Mutation pengeluaran, kartu/saldo dan audit atomic.
  - Acceptance: saldo serta layer current-read terlock per item/gudang, HPP
    residual benar, query menampilkan sale dan opening tanpa model privat POS.
  - Verification: action/query/rollback tests, MySQL current-read/concurrency.
- [ ] Verifikasi dan commit.
  - Acceptance: tidak ada port publik tanpa consumer yang terpasang.
  - Verification: boundary review, focused/regresi dan diff check.

## Increment 3: Payment Record

- [ ] Schema/action penerimaan penjualan, snapshot dan guard master.
  - Acceptance: applied_amount net, tendered/change tersimpan terpisah; record
    immutable, metode/akun aktif/type cocok dan histori tidak terputus.
  - Verification: action/HTTP bila tersedia, migration/rollback/privacy tests.
- [ ] Verifikasi dan commit.
  - Acceptance: belum membuat saldo awal bank, grant atau payment gateway.
  - Verification: focused/regresi/MySQL dan diff check.

## Increment 4: Posting POS Atomic

- [ ] Invoice/snapshot dan idempotency dengan fingerprint payload.
  - Acceptance: retry identik satu invoice; payload beda ditolak; snapshot
    harga/rasio/HPP/payment tetap dan diskon/pajak eksplisit nol.
  - Verification: feature retry/tamper/stale/master-change/overflow tests.
- [ ] Pasang consumer contract Stock/Finance dan locking metadata/pricing.
  - Acceptance: published boundary saja, lock order diaudit, semua mutation
    commit/rollback bersama, super-system tetap tunduk invariant.
  - Verification: injection gagal di setiap tahap, MySQL dua koneksi/race.
- [ ] HTTP posting/detail dan permission.
  - Acceptance: create dan post dibedakan; cashier tidak perlu CRUD Finance;
    auth/CSRF, whitelist payload, tidak bocor rekening/credential/HPP ke role
    yang tidak memerlukannya. Tidak menjalankan IdentityAccessSeeder ulang.
  - Verification: route:list, HTTP matrix guest/view/cashier/super dan rollback.
- [ ] Verifikasi dan commit.
  - Acceptance: route quote/read lama tetap kompatibel, posted tidak edit/delete.
  - Verification: focused/full suite dan diff review.

## Increment 5: UI Checkout

- [ ] Dialog multi-payment dan receipt snapshot.
  - Acceptance: angka Indonesia, satu cash, metode/account nyata, sisa/kembalian,
    Sonner, loading/error/retry, double submit terjaga, quote stale invalidated.
  - Verification: typecheck/lint/build dan browser desktop/tablet Light/Dark.
- [ ] Shortcut/permission dan commit.
  - Acceptance: success sesudah commit saja; receipt reload dari snapshot,
    keranjang tidak hilang saat gagal; tidak mengubah theme.
  - Verification: DevTools discovery/fallback, console/network/screenshot.

## Increment 6: Penutupan

- [ ] Full QA, demo relasional disposable dan aktivasi lokal additive.
  - Acceptance: transaksi demo tidak menulis stok/payment database user;
    fingerprint existing terjaga; migration/status actual dan no reset/grant.
  - Verification: full tests/Pint/TS/lint/build, MySQL/browser/report, fingerprint.
- [ ] Selaraskan API/MODULES/QUALITY/dokumen module dan commit.
  - Acceptance: capability aktual, permission/contract/risiko jelas, tidak
    mengklaim piutang/diskon/pajak/void/full accounting selesai.
  - Verification: review, git diff --check, staging/secret scope check.
- [ ] Push dan cocokkan remote.
  - Acceptance: hanya file scope; Old-docs/SampleUI/LSP tidak ikut.
  - Verification: git push, ls-remote dan divergence0/0.

## Sesudah Increment 1

- [ ] Seluruh task Increment 1 lulus dan status diperbarui.
- [ ] Focused/full test dan Pint scope lulus, runtime/schema unchanged.
- [ ] UI/build/migration/DevTools dinyatakan tidak relevan dengan alasan.
- [ ] Risiko integrasi tertulis; commit otomatis dibuat, belum push.

## Hasil Verifikasi

Review source/schema/quote dan dokumen PASS. Pemeriksaan tautan lokal pada
sembilan dokumen scope serta git diff --check PASS. Domain tests masih pending.

PaymentSettlementTest: RED 34 tests karena class belum ada, lalu GREEN
34 tests/44 assertions. Contoh multi-payment/kembalian, urutan cash, decimal
besar, 10 metode, duplicate/invalid/kurang bayar/non-cash berlebih dan cash
tanpa kontribusi terverifikasi. Pint dua file PASS. Domain belum menjadi
permission authority atau lookup master; consumer transaksi belum dipasang.
