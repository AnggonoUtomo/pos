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
- [x] Generate field kosong, label barang tersimpan, jumlah/satuan/ukuran/cetak.
  - Acceptance: barcode lama tetap, save audited, label Code128 hitam/putih dengan
    quiet zone dan ukuran minimum; kode invalid/tidak muat tidak dicetak.
  - Verification: helper test, build/typecheck serta browser akhir paket.
- [x] Commit save point terfokus setelah verifikasi relevan.

## Increment 2: Scanner Transaksi

- [x] POS antrean scan, tambah/akumulasi satuan, stok agregat, error dan focus.
  - Acceptance: scan cepat tidak hilang; tanpa stale response setelah ganti konteks;
    checkout/hold menunggu antrean selesai; server tetap revalidasi saat posting.
  - Verification: helper exact precision dan browser burst scanner.
- [x] Pembelian scan draft, qty bertambah, beda satuan ditolak, harga manual tetap.
  - Acceptance: Enter scanner tidak submit; readonly tidak bisa scan; nol ledger.
  - Verification: helper + browser dan feature metadata.
- [x] Shortcut Alt+B/Enter dan Sonner sesuai halaman/dialog.
- [x] Commit save point setelah QA terkait lulus.

## Increment 3: Penutupan

- [x] Satu gate backend lengkap; frontend lint/typecheck/build; audit npm.
- [x] DevTools discovery atau BLOCKED+fallback Chrome; screenshot diperiksa.
- [x] Route cache/status; data toko tidak diubah; tanpa migration baru.
- [x] Panduan operator/API/module/roadmap dan hasil command diperbarui.
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
  ditutup oleh gate berikut.
- Gate backend setelah interupsi: PASS 857 tests / 6982 assertions. Hasil akhir
  proses sebelum interupsi tidak tersedia; tidak dianggap lulus dari output dots.
- Typecheck, ESLint scope, build final dan helper unit: PASS. Setelah guard
  select pembelian diperbaiki, ulang hanya lint/build/typecheck serta browser
  pembelian yang terdampak; tidak mengulang full backend/runtime yang tetap.
- Browser core: PASS generate+save, barcode existing tidak ditimpa otomatis,
  manufacturer leading zero, Code128 hitam/putih terbukti pixel, lebar minimum,
  A4 print tiga label tanpa workspace, desktop1440/tablet1024 framing. Scan
  cepat x3 via Enter, kode unknown dan stok agregat unit+dus, quote tanpa ledger
  lulus. Artifact ignored: barcode-browser-57487ea4fe9ef809.
- Browser menemukan select satuan pembelian terkadang mengirim nilai kosong
  saat options berganti. Handler mengabaikan empty selection yang bukan opsi
  valid. Rerun QA_BARCODE_FOCUS=purchase: PASS Enter tidak submit, qty bertambah,
  harga tetap, beda satuan ditolak, draft tersimpan/no-ledger. Artifact:
  barcode-browser-746a9128ea2af47d; viewport1440 (nama screenshot lama memuat
  tablet, tetapi viewport sebenarnya desktop). Harness kini memakai nama netral.
- Chrome DevTools MCP tersedia setelah resume. Pemeriksaan artifact label HTML
  aktual (hanya label fiktif/CSS, tanpa session/auth) pada isolated context: PASS
  90x40mm, fill hitam/background putih, batang ada, tanpa overflow/console error.
  Screenshot inline diperiksa. Penyimpanan screenshot ke path workspace ditolak
  konfigurasi root MCP; evidence memakai screenshot inline. Interaksi aplikasi
  diuji harness Chrome.
- Native MySQL readonly SaleItemCatalog.scan: PASS; route:cache dan route:list
  pos/scan: PASS; HTTPS login200. Tidak ada schema/migration/seed/grant/reset.
  Baseline data lokal: items6/invoices3/orders2/payment_receipts3/roles8/users2.
- .env.testing sementara dihapus sesudah QA. Pending hardware scanner/printer
  fisik bukan PASS. Advisory qs moderate existing tetap di luar scope, tidak
  diklaim npm audit bersih. Old-docs dan staged/untracked LSP tetap dipertahankan.
- Review correctness/architecture/security/readability/performance selesai:
  readonly contract, permission backend, bounds queue/print/lookup, exact decimal,
  reserved barcode, print isolation dan cleanup disposable tidak memiliki blocker.
- Save point backend/package `6c1a7b1`; label `e3f1c0e`; scanner+QA `82042c9`.
  Seluruh commit memakai path eksplisit/--only; file LSP user tidak disertakan.
