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

- [ ] Full backend, lint/TS/build, route/migration dan MySQL read-only QA.
  - Acceptance: gates PASS dan fingerprint master/stock/ledger/audit tidak berubah.
  - Verification: command/ringkasan aktual dicatat.
- [ ] DevTools dicoba; fallback browser jika unavailable.
  - Acceptance: desktop/tablet light/dark, console tanpa error; mobile ditunda.
  - Verification: report/screenshot ignored, bukan credential user.
- [ ] Local activation, docs/checklist, commit/push dan remote verification.
  - Acceptance: pos.test memakai build baru, data/akun/grant tidak direset.
  - Verification: fingerprints, scoped staged diff, push dan ls-remote.

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
