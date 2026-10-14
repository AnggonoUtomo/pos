# Tasks: Data POS Dan Keranjang Nyata

## Sebelum Mulai

- [x] AGENTS, README, arsitektur, struktur dan dokumen module relevan dibaca.
- [x] Scope/non-scope disetujui; target Sales/POS diinformasikan.
- [x] Generator dry-run Sales POS sesuai struktur canonical.
- [x] Dampak route, permission, UI, seeder/audit, stok/harga dicatat.
- [x] Tidak ada schema, dependency atau grant baru; no posted/payment scope.
- [x] Test backend, lint/TS/build, DevTools/fallback desktop/tablet direncanakan.

## Increment 1: Read Boundary

- [x] Scaffold module dan tambahkan public read contract/DTO pemilik.
  - Acceptance: tidak ada private cross-module import, master active/non-trashed,
    no contacts/HPP, batch bounded dan precision decimal string.
  - Verification: test terarah lookup/pagination/search/barcode/stock filtering.
- [x] Demo/access provisioning jelas dan commit save point.
  - Acceptance: demo POS tidak membuat transaksi; permission tanpa grant.
  - Verification: test production refusal/idempotency, diff check/Pint.

## Increment 2: Backend Keranjang

- [x] Domain exact quantity/subtotal dan use case catalog/quote.
  - Acceptance: harga authoritative, stok lintas satuan agregat, no rounding silent,
    no fallback, no mutation/reservation/audit business.
  - Verification: positive/negative/tamper/stale/noop tests.
- [x] HTTP permission/form request/controller dan commit.
  - Acceptance: auth/403/422, super-system tetap invariant, route /pos canonical.
  - Verification: focused HTTP/regression/route tests.

## Increment 3: UI Kasir

- [x] Ganti mock ke context/catalog/quote backend.
  - Acceptance: format Indonesia, zero/missing-price disabled, context change
    menghapus quote stale, loading/error/race guard, tidak ada payment sukses palsu.
  - Verification: lint/TS/build dan QA browser interaksi desktop/tablet.
- [x] Shortcut/permission link dan commit.
  - Acceptance: dropdown shortcut operasional dan semua entry POS mengikuti
    permission; theme tidak diubah.
  - Verification: browser cashier/outsider, keyboard/palette/visual.

## Increment 4: Penutupan

- [x] Full backend, lint/TS/build, route/migration dan MySQL read-only QA.
  - Acceptance: gates PASS dan fingerprint master/stock/ledger/audit tidak berubah.
  - Verification: command/ringkasan aktual dicatat.
- [x] DevTools dicoba; fallback browser jika unavailable.
  - Acceptance: desktop/tablet light/dark, console tanpa error; mobile ditunda.
  - Verification: report/screenshot ignored, bukan credential user.
- [x] Local activation dan docs/checklist sesuai hasil aktual.
  - Acceptance: pos.test memakai build baru, data/akun/grant tidak direset.
  - Verification: fingerprints dan scoped diff.
- [x] Commit penutupan, push dan remote verification.
  - Acceptance: seluruh perubahan scope terkirim, tanpa artefak user.
  - Verification: staged diff/check, push dan ls-remote.

## Hasil

Increment 1: test boundary RED sebelum contract ada, lalu focused lookup,
UnitLookup/Items HTTP/Stock read PASS. Generator memakai Sales/POS canonical.
Private model tetap di adapter pemilik; DTO hanya metadata penjualan dan saldo.
Tidak ada tabel/dokumen POS pada increment ini. Bukti akhir diperbarui saat QA.

Increment 2: PosCartTest RED 404 sebelum endpoint ada, lalu POS/ItemPrice
focused 26 tests/154 assertions PASS. Tiga route POS dimiliki module, route
closure lama dihapus tanpa mengganti URL/name. Price catalog mendapat filter
level opsional backward-compatible agar kasir tidak memuat semua level harga.
Test mencakup fresh price/stock, tamper, aggregate stock, missing price,
inactive/non-sale, permission/super-system, rounding dan fingerprint read-only.

Increment 3: mock-data, pengali level dan fake payment handler telah dihapus;
consumer script QA juga dialihkan. Format POS exact via BigInt/string (subtotal
tetap authoritative backend). Lint/TS/build PASS. Browser terakhir PASS:
storage/framework/testing/parties-browser-794f975027289899/pos-demo-report.json.
Barcode, CSRF quote nyata, per-gudang, ganti pelanggan/harga, invalid stock,
response lama sesudah edit, controlled 503/retry, empty, keyboard/reset, cashier
only, desktop/tablet Light/Dark terverifikasi. Screenshot stabil diperiksa.
Expected console HTTP422/controlled503 disaring; unexpected console/page errors
kosong. Tidak mengklaim DevTools PASS: namespace DevTools tidak tersedia pada
sesi ini (BLOCKED), fallback Playwright Core existing dipakai tanpa install.

## Verifikasi Akhir 14 Oktober 2026

- `php artisan test --compact`: 282 tests / 1748 assertions PASS.
- `npx tsc --noEmit`, `npx eslint resources/js`, `npm run build`: PASS.
- `vendor/bin/pint --dirty --test`: PASS.
- `node scripts/qa/pos-demo.mjs`: PASS, local number/maximum exact dan tidak
  ada mock import, price modifier atau fake payment handler.
- `php scripts/qa/pos-live-local.php`: PASS dua kali, MySQL asli local read;
  price/level/base quantity nyata, seluruh fingerprint existing identik.
- `php artisan route:list --path=pos`: tiga route POS canonical tersedia
  (command juga mencocokkan route Stock post existing).
- `php artisan migrate:status`: seluruh migration Ran, tidak ada schema baru.
- `pos.test/login`: 200, memakai built assets dan tidak memuat Vite dev client.
- QA authenticated memakai fixture disposable dan credential random runtime,
  bukan akun MySQL pengguna. Screenshot desktop/tablet sudah dilihat manual.
- Regression `inventory-browser.mjs --items-only`: PASS, report ignored
  storage/framework/testing/inventory-browser-7b472291b35a604b/report.json;
  CRUD/matrix harga, sidebar expanded/collapsed, Light/Dark/Clean, tablet,
  readonly, shortcut/palette dan console bersih.

## Penutupan Delivery 15 Oktober 2026

- Implementasi/QA pada commit c7abee7 telah terkirim ke origin/main.
  `git ls-remote origin refs/heads/main` cocok dengan HEAD lokal,
  divergence 0/0. Kendala DNS pada percobaan pertama sudah pulih.
- Verifikasi ulang POS focused: 14 tests / 79 assertions PASS.
- Verifikasi ulang full suite: 282 tests / 1748 assertions PASS.
- Smoke `php scripts/qa/pos-live-local.php`: PASS, fingerprint existing identik.
- Report browser POS dan regresi Inventory pada path di atas diperiksa kembali:
  keduanya PASS. Tidak mengklaim browser QA dijalankan ulang pada sesi penutupan.
- Tidak ada source runtime yang diubah pada penutupan ini. SampleUI dan empat
  artefak LSP user tetap untracked, tidak ikut commit. Old-docs tidak disentuh.
- Status/checklist ditutup melalui commit dokumentasi sesudah konfirmasi remote.

## Batas Dan Risiko

Quote read-only, bukan reservasi/harga terkunci atau dokumen penjualan. Data
ditampilkan dari transaksi baca saat request; stok/harga dapat berubah sesudah
itu. Posting/FIFO keluar, rounding transaksi, diskon/pajak/payment/reversal belum
dibuat. Memilih context membatalkan quote tetapi mempertahankan input cart;
backend wajib memeriksa ulang. Header/dashboard hanya diubah pada guard link
POS, bukan mengubah dashboard mock lain. Theme/Old-docs/SampleUI tidak disentuh.
