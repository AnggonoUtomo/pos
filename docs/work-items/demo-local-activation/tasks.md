# Tasks: Aktivasi Demo Lokal Dan POS

## Sebelum Mulai

- [x] AGENTS.md, docs/README.md dan dokumen arsitektur/struktur relevan dibaca.
- [x] Migrate:status menunjukkan customer masters Ran; demo baru masih kosong.
- [x] User mengonfirmasi halaman master dan POS sama-sama perlu diperbarui.
- [x] Scope additive, preservasi data user dan mock-only POS ditetapkan.

## Increment 1: Demo Pada Database Lokal

- [x] Jalankan seeder demo relasional melalui pengaman local/MySQL.
  - Acceptance: 9 produk CCTV/kelistrikan, relasi satuan, stok awal FIFO dan
    3 level/pelanggan tersedia; seluruh row existing dan akun/grant tetap sama.
  - Verification: apply-demo-local.php dan fingerprint sebelum/sesudah.
- [x] Ulangi provisioning dan commit increment.
  - Acceptance: tidak ada duplikasi, ledger/audit baru tidak bertambah saat repeat.
  - Verification: count/snapshot dan migrate:status.

## Increment 2: Mock POS

Increment 1 Passed: apply-demo-local.php menambah 5 satuan (3 kode sudah ada),
9 barang, 18 relasi, 1 dokumen stok posted/5 baris, 5 saldo/movement/FIFO layer,
3 level dan 3 pelanggan. Fingerprint semua row existing dan akun/grant identik.
Pemanggilan kedua seluruh delta 0, termasuk activity_log. Tidak migrate:fresh.

- [x] Ganti mock sembako dengan demo CCTV/kelistrikan.
  - Acceptance: 9 SKU dan rasio satuan sesuai dataset seeder; level Eceran,
    Grosir, Installer; 2 gudang demo; harga/stok tetap fiktif, bukan engine live.
  - Verification: regresi dataset mock dan route POS.
- [x] Jalankan lint/typecheck/build dan QA browser desktop/tablet.
  - Acceptance: produk baru terlihat, pencarian/cart/satuan bekerja, framing
    tidak overflow, tidak ada error console; theme tidak berubah.
  - Verification: Chrome DevTools MCP bila tersedia, fallback Playwright existing.
- [x] Perbarui bukti, commit dan push.
  - Acceptance: status sesuai realita, tidak mengklaim posting POS backend.
  - Verification: diff check, git status, remote HEAD.

## Bukti Penutupan

Increment 2 Passed. node scripts/qa/pos-demo.mjs RED pada 5 SKU sembako lama,
lalu PASS untuk 9 SKU/18 rasio, level dan gudang demo baru.
php artisan test --compact --filter='PosRouteTest|ItemsSeederTest|UnitsSeederTest|StockSeederTest|PartiesSeederTest':
16 tests/87 assertions PASS. npx eslint resources/js, npx tsc --noEmit,
npm run build (2694 modules), Pint QA PHP dan git diff --check PASS.

Chrome DevTools MCP BLOCKED: tool tidak tersedia pada sesi ini. Fallback
node scripts/qa/pos-demo-browser.mjs PASS; fixture SQLite disposable dengan
demo master/stok, akun ephemeral random via stdin, tanpa akun MySQL user.
Verifikasi master 9 barang, Meter/Rol, pelanggan installer, 5 saldo stok;
POS 9 produk tanpa sembako, pencarian, cart, Rol NYM/Dus kamera, level Installer,
reset dan dark. Desktop 1440x1000, tablet 1024x768/768x1024; tidak ada overflow
halaman atau console/page error. Font CDN distub. Mobile tidak diuji.
6 screenshot/report lokal ignored:
storage/framework/testing/parties-browser-3733c885fdba03bd.

Kontrol theme di tablet masih memakai pola existing dengan label tersembunyi;
QA memilih Dark lewat desktop lalu memeriksa portrait. Tidak mengubah theme
atau menutup gap aksesibilitas di luar scope ini. POS tetap mock, bukan transaksi
backend atau sumber saldo/harga aktual; mock memiliki nilai stok/harga fiktif.

Migrate:status semua Ran, termasuk customer masters batch 8. Query lokal:
7 produk CCTV + 2 kelistrikan aktif, level Eceran/Grosir/Installer,
marker cctv-electrical-opening-v1 berstatus posted. Aktivasi ulang seluruh delta
0, termasuk audit; seluruh row existing/password/role/grant tetap identik.
