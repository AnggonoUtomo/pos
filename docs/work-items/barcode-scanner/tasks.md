# Tasks: Barcode Scanner

## Sebelum Mulai

- [x] Baca AGENTS, README, architecture/struktur dan module Items/POS/PurchaseOrders.
- [x] User menyetujui barcode pabrikan + internal/label, serta JsBarcode.
- [x] Informasikan module target dan batas scan bukan posting/ubah stok.
- [x] Pertahankan Old-docs, data toko, staged/untracked LSP dan role existing.

## Increment 1: Fondasi Dan Label

- [x] Install JsBarcode dan periksa lock/audit tanpa perubahan dependency lain.
- [x] RED exact scan/permission/status/leading zero/no-ledger dan barcode purchase.
  - Acceptance: scan tidak bergantung pagination/debounce; lookup sesuai satuan.
  - Verification: focused feature test disposable.
- [x] Public scan DTO/contract/adapter dan endpoint readonly POS.
- [ ] Generate field kosong, label barang tersimpan, jumlah/satuan/ukuran/cetak.
  - Acceptance: barcode lama tetap, save audited, label Code128 hitam/putih dengan
    quiet zone dan ukuran minimum; kode invalid/tidak muat tidak dicetak.
  - Verification: helper test, build/typecheck serta browser akhir paket.
- [ ] Commit save point terfokus setelah verifikasi relevan.

## Increment 2: Scanner Transaksi

- [ ] POS antrean scan, tambah/akumulasi satuan, stok agregat, error dan focus.
  - Acceptance: scan cepat tidak hilang; tanpa stale response setelah ganti konteks;
    checkout/hold menunggu antrean selesai; server tetap revalidasi saat posting.
  - Verification: helper exact precision dan browser burst scanner.
- [ ] Pembelian scan draft, qty bertambah, beda satuan ditolak, harga manual tetap.
  - Acceptance: Enter scanner tidak submit; readonly tidak bisa scan; nol ledger.
  - Verification: helper + browser dan feature metadata.
- [ ] Shortcut Alt+B/Enter dan Sonner sesuai halaman/dialog.
- [ ] Commit save point setelah QA terkait lulus.

## Increment 3: Penutupan

- [ ] Satu gate backend lengkap; frontend lint/typecheck/build; audit npm.
- [ ] DevTools discovery atau BLOCKED+fallback Chrome; screenshot diperiksa.
- [ ] Route cache/status; data toko tidak diubah; tanpa migration baru.
- [ ] Panduan operator/API/module/roadmap dan hasil command diperbarui.
- [ ] Commit/push scope eksplisit, verify remote dan divergence.

## Bukti

- JsBarcode3.12.3: hanya satu package baru; tidak ada dependency existing diupdate.
  Audit npm menemukan satu advisory moderate existing pada qs (GHSA-6pq8-jhm2-2vcw),
  bukan JsBarcode. Dicatat di luar scope; tidak menjalankan npm audit fix otomatis.
- RED: dua scan endpoint404 dan metadata purchase belum memiliki barcode.
- Focused PosCartTest + PurchaseDraftTest: PASS 14 tests / 101 assertions.
  Test prioritas barcode/reservasi kode tambahan masuk gate akhir.
- `node scripts/qa/barcode-unit.mjs`: PASS encoding Code128, nol di depan,
  presisi BigInt, scan pembelian mempertahankan harga/beda satuan ditolak, serta
  agregasi stok POS lintas satuan. Helper belum ada saat RED (module not found).
- Pint scope, typecheck dan ESLint scope awal: PASS. Source akhir dan browser
  masih menunggu gate penutupan.
