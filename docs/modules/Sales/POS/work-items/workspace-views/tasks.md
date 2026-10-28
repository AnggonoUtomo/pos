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

- [x] QA scanner, hold/recall, quote dan dialog pembayaran yang terdampak.
  - Acceptance: operasi existing tetap berfungsi; tanpa transaksi di toko.
  - Verification: database disposable dan browser.
- [x] Jalankan lint/typecheck/build terfokus dan review diff.
  - Acceptance: gate lulus, tidak menyertakan file LSP user.
  - Verification: hasil command dan git diff --check.
- [x] Update panduan, commit dan push paket selesai.
  - Acceptance: dokumentasi sesuai hasil aktual dan remote terverifikasi.
  - Verification: git status dan divergence.

## Hasil Verifikasi 28 Oktober 2026

- PASS `node scripts/qa/pos-workspace-test.mjs`: default unik, kosong, ambigu,
  bukan Gudang Utama/Grosir, serta preferensi valid/invalid.
- PASS `npx eslint resources/js/pages/sales/pos`, `npx tsc --noEmit`,
  `npm run build` dan `git diff --check`.
- PASS `node scripts/qa/pos-workspace-browser.mjs` dengan
  `QA_PLAYWRIGHT_MODULE` existing, Chrome terpasang, SQLite/cache/storage
  disposable. Tidak memasang dependency/browser/server OS.
- Klasik/Visual PASS pada 1440x900, 1024x768, 768x1024. Tinggi area scroll
  katalog Visual 595/463/719 px; Klasik 485/353/609 px. Pencarian terlihat,
  area keranjang tersedia, dan tidak ada overflow horizontal.
- PASS default, scan berurutan, switch mempertahankan jumlah/diskon/pajak/
  quote, persistensi preferensi setelah reload, hold/recall, dialog pembayaran,
  penggantian gudang/pelanggan manual dan panel sempit 640x960 termasuk Alt+F.
- Console/page errors kosong. Hash ledger stok/finance sama sebelum/sesudah.
  Tidak menekan posting pembayaran pada QA ini.
- Bukti ignored: `storage/framework/testing/pos-workspace-a60debc24e9ab19a/`
  berisi `report.json` dan screenshot. Screenshot juga direview visual.
- Chrome DevTools MCP tersedia; akses app produksi beralih ke login, sehingga
  alur authenticated diuji dengan Playwright pada fixture terisolasi, bukan
  meminjam credential produksi. MCP membuka dan memeriksa screenshot tablet
  hasil render browser; ini bukti visual, bukan uji interaksi live lewat MCP.
- Review diff: pemilik state transaksi tetap PosIndex; view hanya presentasi,
  default hanya initializer; handler recall/recovery/posting tidak diubah.
- Tidak menjalankan ulang regresi backend penuh/migration karena tidak ada
  perubahan backend/schema. Tidak mengubah data toko, role, package atau tema.
- Save point implementasi: `30bbdfd`. Delivery hanya file pekerjaan ini;
  file LSP yang sudah staged/untracked milik user tetap tidak disertakan.

UAT scanner/printer fisik dan advisory qs tetap pending seperti README.

## Increment 3: Autocomplete Pelanggan

- [x] Scope dibatasi pada pencarian pelanggan di Klasik/Visual; tanpa endpoint,
  schema, seed, dependency baru atau perubahan aturan harga.
- [x] Ganti select pelanggan dengan autocomplete nama/kode/level.
  - Acceptance: hanya pilihan pelanggan aktif existing; ketikan bukan pelanggan
    baru, batal tidak mengubah pilihan, nama/kode/level terlihat pada hasil.
  - Verification: QA browser pencarian, keyboard, hasil kosong dan Escape.
- [x] Verifikasi context/harga dan keranjang tetap aman.
  - Acceptance: pemilihan mengganti level harga dan membatalkan quote lama,
    bukan menghapus baris; mengetik/batal tidak mengganti pelanggan.
  - Verification: test terfokus desktop/tablet pada kedua view, lint/type/build.
- [x] Update panduan dan commit/push setelah lulus.
  - Verification: diff scope dan status remote.

### Hasil Increment 3

- RED terkonfirmasi pada UI sebelumnya: inputValue gagal karena pelanggan
  masih tombol Select, belum input autocomplete.
- PASS QA terfokus dengan `QA_POS_WORKSPACE_FOCUS=customer` dan
  `QA_PLAYWRIGHT_MODULE` existing: `node scripts/qa/pos-workspace-browser.mjs`.
  Klasik 1024x768 dan Visual 1440x900: pencarian kode tidak peka huruf besar
  dan trim spasi, pencarian nama/level, hasil kosong, Escape, keyboard/click,
  framing dropdown, pembatalan quote lama, refresh level Eceran, serta baris
  keranjang tetap ada. Console/page errors kosong dan hash ledger tidak berubah.
- PASS lint POS, typecheck global, build, git diff --check dan review scope.
  Tidak mengulang regresi backend/QA seluruh workspace: backend/schema dan
  operasi posting tidak berubah. Seed hanya fixture SQLite disposable.
- Screenshot/report ignored:
  `storage/framework/testing/pos-workspace-4c8a29c20b7fbcf5/`.
  Screenshot desktop/tablet direview. MCP tersedia; produksi beralih ke login,
  sehingga interaksi diuji Chrome via Playwright terisolasi. MCP membuka bukti
  screenshot, bukan mengklaim interaksi authenticated melalui MCP.
- Panduan tampilan diperbarui; tidak menambah role, package, endpoint atau
  perubahan data produksi. Default dan handler pemilihan existing dipertahankan.
