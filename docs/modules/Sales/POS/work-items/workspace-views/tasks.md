# Tasks: Dua Tampilan Workspace POS

## Sebelum Mulai

- [x] AGENTS dan dokumentasi relevan dibaca; perubahan user dipertahankan.
- [x] Scope dua tampilan, default dan batas transaksi dicatat sebelum coding.
- [x] Pending hardware UAT dan advisory existing dicatat tanpa menambah scope.

## Increment 1: Default Dan Workspace

- [x] Tambahkan default unik Gudang Utama/Eceran dan test.
  - Acceptance: tidak memilih acak; recall/recovery tidak ditimpa default.
  - Verification: test helper dan browser disposable.
- [x] Tambahkan pilihan Klasik/Visual dan preferensi browser.
  - Acceptance: keranjang, context, diskon/pajak dan quote tidak hilang.
  - Verification: QA switch dan reload.
- [x] Rapikan katalog, keranjang dan toolbar desktop/tablet.
  - Acceptance: pencarian terlihat, scroll independen, tidak overlap/overflow.
  - Verification: screenshot dan ukuran DOM desktop/tablet.

## Increment 2: Verifikasi Dan Delivery

- [ ] QA scanner, hold/recall, quote dan dialog pembayaran yang terdampak.
  - Acceptance: operasi existing tetap berfungsi; tanpa transaksi di toko.
  - Verification: database disposable dan browser.
- [ ] Jalankan lint/typecheck/build terfokus dan review diff.
  - Acceptance: gate lulus, tidak menyertakan file LSP user.
  - Verification: hasil command dan git diff --check.
- [ ] Update panduan, commit dan push paket selesai.
  - Acceptance: dokumentasi sesuai hasil aktual dan remote terverifikasi.
  - Verification: git status dan divergence.
