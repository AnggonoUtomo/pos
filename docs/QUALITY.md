# Quality Dan Verifikasi

Mulai dari test paling spesifik dan naik ke gate yang lebih luas sesuai risiko.
Jangan mengklaim selesai jika command gagal atau belum dijalankan.

## Matriks Verifikasi

| Perubahan | Verifikasi minimum |
| --- | --- |
| Dokumentasi | Link relatif dicek manual sesuai scope dan `git diff --check` |
| Backend behavior | Focused feature/unit test |
| Boundary atau module | Dry-run generator bila relevan, focused test, `route:list`, dan migration/status check bila schema berubah |
| Frontend behavior | `npx eslint resources/js`, `npx tsc --noEmit` dan `npm run build` bila menyentuh React/TS/CSS |
| UI/alur pengguna | QA browser via Chrome DevTools MCP atau catatan `SKIPPED/BLOCKED` beserta fallback |
| Migration | `php artisan migrate:status` dan migrate pada database disposable bila migration baru berisiko |
| API contract | Focused API test dan pembaruan `API.md` |
| Transaksi stok/payment | Focused test untuk no-negative-stock, FIFO, locking, snapshot, dan rollback |

## Command Project

Backend focused test:

```powershell
php artisan test --filter=NamaTestAtauPattern
```

Backend full test:

```powershell
php artisan test
```

Formatter PHP:

```powershell
vendor\bin\pint --dirty
```

Frontend lint:

```powershell
npx eslint resources/js
```

Gunakan lint nonmutating dengan scope source aktif. Script existing
`npm run lint` memakai `eslint . --fix`, sehingga dapat menyentuh arsip
`Old-docs/` atau artefak user `SampleUI/`; jangan gunakan untuk gate pekerjaan
terfokus. Formatter dijalankan hanya pada file yang memang diubah.

Frontend typecheck:

```powershell
npx tsc --noEmit
```

Frontend build:

```powershell
npm run build
```

Route inspection:

```powershell
php artisan route:list --except-vendor
```

Migration status:

```powershell
php artisan migrate:status
```

Module generator dry-run:

```powershell
php artisan module:make {Domain} {Module} --dry-run
```

Module generator dengan test:

```powershell
php artisan module:make {Domain} {Module} --with-tests
```

Full quality gate sebelum handoff besar:

```powershell
vendor\bin\pint --dirty
php artisan test
npx eslint resources/js
npx tsc --noEmit
npm run build
php artisan route:list --except-vendor
php artisan migrate:status
git diff --check
```

Full gate digunakan untuk perubahan lintas area, risiko tinggi, atau sebelum
release. Jangan menjalankannya berulang tanpa perubahan source.

## QA Browser Dengan Chrome DevTools MCP

Untuk perubahan UI/browser, agent wajib mencoba Chrome DevTools MCP lebih dulu.
Jika tool tidak tersedia atau gagal setelah percobaan wajar, catat status
`BLOCKED` dan gunakan fallback yang tersedia.

## Catatan Vite Hot File Lokal

Laravel Vite akan membaca asset dari dev server bila `public/hot` ada. Jika
`http://pos.test` memuat URL seperti `http://127.0.0.1:5173/@vite/client`,
`http://[::1]:5173/@vite/client`, atau `@react-refresh` sementara
`npm run dev` tidak berjalan, hapus `public/hot` atau jalankan kembali
`npm run dev`. Aplikasi juga memiliki guard lokal yang menghapus `public/hot`
stale saat dev server tidak reachable. File `public/hot` bersifat runtime lokal
dan sudah di-ignore oleh git, sehingga tidak boleh menjadi acuan deployment.

Urutan akses eksplisit:

1. Cari tool DevTools melalui `tool_search` dengan kata kunci Chrome DevTools
   atau browser testing.
2. Load skill `browser-testing-with-devtools` bila pekerjaan UI membutuhkan QA
   browser.
3. Buka halaman target pada browser yang dikontrol tool.
4. Cek console error, network error penting, DOM state, permission visibility,
   loading state, dan empty/error state.
5. Ambil screenshot atau catatan visual bila halaman berubah signifikan.
6. Untuk layout admin, cek sidebar expanded/collapsed pada desktop dan tablet
   landscape/portrait. QA mobile ditunda sesuai scope project saat ini.
7. Untuk POS fullscreen, cek desktop/tablet viewport, fokus input cepat, cart,
   dialog payment, dan state stok/harga.

Catat hasil dengan format singkat:

```text
Chrome DevTools QA: PASS
- URL:
- Viewport:
- Console:
- Catatan:
```

atau:

```text
Chrome DevTools QA: BLOCKED
- Alasan:
- Fallback:
```

## Fallback QA Inventory

Jika Chrome DevTools MCP tidak tersedia, script
`scripts/qa/inventory-browser.mjs` dapat memakai Playwright Core dan Chromium
yang sudah tersedia. Jangan memasang dependency tanpa instruksi user.
Jalankan `npm run build` dahulu dan pastikan `public/hot` tidak mengarah ke
dev server yang mati. Contoh PowerShell, sesuaikan path package lokal:

```powershell
$env:QA_PLAYWRIGHT_MODULE = 'C:/path/to/node_modules/playwright-core'
node scripts/qa/inventory-browser.mjs
```

Script memakai PHP CLI dan `tests/Browser/inventory-fixture.php` untuk membuat
database SQLite disposable di `storage/framework/testing/`, server loopback
sementara, akun QA/credential random runtime, dan browser terisolasi. Tidak
memutasi MySQL pengguna, memerlukan akun produksi, atau mencetak credential.
Browser/server ditutup sesudah QA. Screenshot dan `report.json` lokal di-ignore
Git, bukan artefak yang ikut commit.

Coverage: CRUD/restore gudang, satuan dan barang, barcode, rasio exact/invalid,
base unit terkunci, guard referensi, readonly permission, search/empty, keyboard,
palette, profil authenticated, framing desktop/tablet, dark/default/clean serta
console. Opsi `--masters-only`, `--items-only`, `--guard-only` mempersempit QA;
jalankan tanpa opsi untuk penutupan work item. Fallback PASS tidak berarti
Chrome DevTools MCP PASS; catat kedua status dengan jujur.

## Fallback QA Identity

Dengan package Playwright Core/Chromium existing dan build terbaru, jalankan:

```powershell
$env:QA_PLAYWRIGHT_MODULE = 'C:/path/to/node_modules/playwright-core'
node scripts/qa/identity-browser.mjs
```

Fixture `tests/Browser/identity-fixture.php` hanya menerima SQLite disposable
berprefix identity-browser di storage/framework/testing. Akun manager, viewer,
delegate dan staff dibuat sementara dengan credential random melalui stdin,
bukan akun MySQL lokal. Server loopback/browser ditutup di finally.
Coverage: CRUD role, sync permission, assignment persisted/reload, role terpakai,
last-super, readonly/delegated manager, search/empty, Sonner, shortcut/palette,
desktop expanded/collapsed, tablet landscape/portrait dan dark/clean.
Script juga memverifikasi self-delete profil: password salah, last-super ditolak
dan tetap authenticated, password/error cleared, ordinary account deletion dan
logout, dialog light/dark/desktop/tablet serta Sonner/keyboard. Akun yang dihapus
hanya akun fixture disposable, tidak menggunakan credential/akun MySQL nyata.
Manajemen pengguna: tambah/edit, role tersimpan, duplicate email/konfirmasi
password, password cleared, status badge, inactive login ditolak, sesi lama
sesudah reaktivasi ditolak dan login baru berhasil, users-only navigation/palette
serta delegated operator. Remember recaller diverifikasi lewat UserStatusTest;
workflow browser tidak mengklaim kontrol remember UI starter kit sudah diperbaiki.
Screenshot/report.json lokal di-ignore. CSS font Bunny eksternal distub agar QA
tidak tergantung jaringan; font fallback dipakai dan CDN tidak termasuk gate.
Status fallback PASS tetap terpisah dari DevTools MCP BLOCKED.

## QA Stok Awal Dan FIFO Foundation

Backend: `php artisan test --filter=Stock` dan regresi Items/Warehouses. Domain
memakai decimal string exact; test SQLite memeriksa schema/up/down dan rollback,
bukan bukti lock MySQL. Untuk MySQL local, jalankan:

```powershell
php scripts/qa/stock-mysql.php
```

Script membuat database baru bernama generated pos_stock_qa dengan suffix random
tervalidasi, menjalankan migration/demo di database itu, dan memakai dua koneksi
REPEATABLE READ untuk menginterleave perubahan rasio sesudah consistent read
draft. Memeriksa stale rejection, urutan lock base/selected unit, idempotency,
sekali per pasangan dan DECIMAL besar exact. Database disposable dibersihkan
di finally; bukan load test seluruh race, tidak memutasi database pengguna.

QA browser terisolasi:

```powershell
$env:QA_PLAYWRIGHT_MODULE = 'C:/path/to/node_modules/playwright-core'
node scripts/qa/stock-browser.mjs
```

Gunakan package existing, build terbaru dan fixture tests/Browser/stock-fixture.php.
SQLite berprefix stock-browser, server loopback, credential random via stdin,
timezone Asia/Jakarta; tanpa akun MySQL nyata. Coverage: draft CRUD/restore,
cost positif, multi-satuan exact, posting immutable, duplicate pair, saldo/kartu,
filter gudang, viewer/editor, shortcut/palette, sidebar collapse dan dark.
Desktop 1440x1000; tablet 1024x768/768x1024. Screenshot/report lokal ignored;
browser/server selalu ditutup. Chrome DevTools tetap dicoba dahulu; smoke login
bukan bukti seluruh workflow Stock. Catat cakupan masing-masing alat.

scripts/qa/apply-stock-local.php hanya untuk penerapan additive yang eksplisit:
migration/permission Stock, fingerprint akun/role/pivot/master sebelum/sesudah,
tanpa demo Stock. Jangan memakai migrate:fresh atau menjalankan demo tanpa arahan
pada MySQL pengguna.

## QA Pelanggan Dan Level

`php artisan test --filter=Parties` memeriksa CRUD, soft delete/restore,
permission/super-system, level aktif/terpakai, unique termasuk trashed,
rollback data/audit, no-op, privasi kontak dan demo idempotent.
`php scripts/qa/parties-mysql.php` memakai database generated pos_parties_qa
dengan suffix random tervalidasi, dua koneksi REPEATABLE READ, current read
sesudah snapshot stale, dirty-check status, FK restrict dan cleanup. Tidak
menguji seluruh interleaving/load concurrency produksi.

```powershell
$env:QA_PLAYWRIGHT_MODULE = 'C:/path/to/node_modules/playwright-core'
node scripts/qa/parties-browser.mjs
```

Fixture tests/Browser/parties-fixture.php hanya menerima database SQLite
disposable parties-browser di storage/framework/testing. Credential random
via stdin, server loopback sementara, browser/profile terisolasi, font CDN
distub dan cleanup server/browser di finally. Coverage: CRUD/persist/reload,
guard level termasuk pelanggan terhapus, filter/empty, readonly/outsider,
shortcut/palette, regresi route Inventory, expanded/collapsed desktop,
tablet 1024x768/768x1024, dark dan console. Screenshot/report lokal ignored.
Smoke DevTools redirect/login tidak diklaim sebagai QA authenticated CRUD.

scripts/qa/apply-parties-local.php hanya migration additive dan permission
catalog local, fingerprint data existing diperiksa. Tidak seed demo atau grant
otomatis, tidak migrate:fresh.

## QA POS Data Dan Keranjang

`php artisan test --filter='PosCartTest|PosReadBoundaryTest|PosRouteTest|LineAmountsTest'`
memeriksa metadata active/non-trashed, pagination/barcode, level harga nyata,
batch read, aggregate stock lintas satuan, precision/rounding/overflow, tamper,
permission/super-system dan fingerprint tanpa mutation. Tidak membuktikan posting
atau reservasi stok; quote read-only harus diperiksa ulang saat posting kelak.

`php scripts/qa/pos-live-local.php` hanya local MySQL, menyiapkan permission key
existing tanpa grant dan memeriksa katalog/quote demo existing. Tidak seed
master/harga/stock ulang. Fingerprint akun/grant/master/harga/stok/ledger/audit
harus identik; tidak mengklaim load/concurrency test. Demo CCTV/level/harga harus
sudah tersedia; script tidak membuat default transaksi.

```powershell
$env:QA_PLAYWRIGHT_MODULE = 'C:/path/to/existing/playwright-core'
node scripts/qa/pos-demo.mjs
node scripts/qa/pos-demo-browser.mjs
```

Nama script pos-demo dipertahankan untuk command QA existing, tetapi sekarang
menguji integrasi data nyata pada fixture SQLite disposable, bukan mock catalog.
Coverage: barcode unit jual, matrix level, backend quote/CSRF, stock error,
context change, subtotal invalidation, delayed real response, controlled 503
dan retry, empty, reset confirmation, shortcuts, cashier-only permission dan
Inventory admin regression. Desktop 1440x1000/tablet 1024x768 Light, tablet
768x1024 Light/Dark. Known console422 validation dan controlled503 diabaikan;
unexpected console/page errors gagal. Screenshot setelah toast habis/animasi
nonaktif, report ignored. Server/browser ditutup; mobile tidak diuji.
Chrome DevTools tetap dicoba dahulu sesuai urutan di atas.

## QA Matrix Harga Barang

`php artisan test --filter=ItemPrice` meliputi exact amount, matrix mandatory,
invalid/duplicate/stale, atomic workflow/audit, no-op, lifecycle ID dan demo
preservation. `php scripts/qa/item-prices-mysql.php` memakai database generated
pos_prices_qa yang tervalidasi, dua koneksi REPEATABLE READ, harga maksimum
DECIMAL(20,2) exact, current read level/unit, rollback dan cleanup. Tidak
memutasi database user atau mengklaim load/concurrency lengkap.

Fallback `node scripts/qa/inventory-browser.mjs --items-only` memakai fixture
Inventory dengan tiga level dan demo harga. Memeriksa required/zero, input
rupiah Indonesia, persist/reload, unit flag, permission readonly, desktop/tablet
Light/Dark serta console. Screenshot finite animation dinonaktifkan agar bukan
capture setengah transisi; dialog harus muat viewport. Dependency existing saja.

Penerapan lokal eksplisit: `php scripts/qa/apply-prices-local.php`, hanya MySQL
local, migration harga additive dan demo sembilan SKU yang dikenal. Fingerprint
akun/grant/master/stok/ledger dan seluruh harga existing diperiksa; seed diulang
tidak menimpa harga edit/trashed. Tidak ada migrate:fresh atau grant otomatis.

## QA Dependency Security

Untuk pembaruan dependency, gunakan update terarah tanpa audit fix --force.
Periksa diff lockfile/constraint/engine dan audit production maupun dev:

```powershell
npm audit
npm audit --omit=dev
composer audit --locked --no-interaction
node scripts/qa/dependency-security.mjs
php artisan test --filter='MarkdownDependencyTest|NotificationMarkdownTest'
```

Script parser berjalan dalam worker 10 detik tanpa eksekusi shell; memeriksa
line terminator sesudah comment, chain parse/quote, offset invalid/nested dan
kontrol normal concurrently/PostCSS. Test Markdown memeriksa escaping tag
terlarang, table/paragraf bounded, serta HTML/text email reset/verifikasi tanpa
delivery. Tidak mengklaim simulasi DoS produksi atau uji lintas-shell penuh.

## Checklist Invariant Transaksi

Saat menyentuh Sales POS, Purchasing, Inventory Stock, Returns, atau Finance
Payments, verifikasi hal berikut sesuai scope:

- [ ] Gudang dipilih dan tervalidasi.
- [ ] Stok tidak bisa minus.
- [ ] FIFO per `item + warehouse`.
- [ ] Semua kuantitas disimpan dalam base unit.
- [ ] Harga mengikuti customer level dan satuan.
- [ ] Diskon persen dan nominal tersimpan sebagai snapshot.
- [ ] Pajak tersimpan sebagai snapshot.
- [ ] Multi payment tidak membuat total bayar tidak konsisten.
- [ ] Posted transaction tidak diedit bebas.
- [ ] Retur, void/reversal, atau adjustment audited.
- [ ] Activity log tidak menggantikan ledger stok/payment.
