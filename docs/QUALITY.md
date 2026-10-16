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

## QA Master Supplier

Sesudah build, jalankan `php artisan test --filter=Suppliers` dan
`php scripts/qa/suppliers-mysql.php` untuk database MySQL disposable (guard
local/mysql, nama random, dihapus pada finally). Native QA menguji unique
case-insensitive termasuk trashed, locked current read, audit veto rollback,
demo idempotent dan migration up/down. Tidak mengklaim seluruh race sudah diuji.

Chrome DevTools dicoba dahulu. Untuk alur authenticated tanpa credential user,
gunakan Playwright existing dengan `QA_PLAYWRIGHT_MODULE` lalu
`node scripts/qa/suppliers-browser.mjs`. Fixture di
`tests/Browser/suppliers-fixture.php` hanya menerima SQLite disposable di lokasi
testing dengan prefix guarded. Credential random via stdin; browser/server
ditutup pada finally. Memverifikasi CRUD/reload/restore nonaktif, error duplikat,
filter/empty, shortcut/palette, permission UX/backend, persistence dan audit
redacted. Screenshot Light/Dark desktop/tablet expanded/collapsed dan report
disimpan ignored di storage/framework/testing, tidak ikut commit.

Aktivasi lokal eksplisit: `php scripts/qa/apply-suppliers-local.php`.
Hanya schema module/katalog permission/demo baru; fingerprint seluruh tabel
existing (kecuali cache/cache_locks/migrations) dan setiap baris lama pada
suppliers/permissions/activity_log harus identik. Script tidak memberi grant,
reset akun, mengubah master lama atau membuat transaksi. Jalankan ulang untuk
memastikan additive/idempotent. Migration DDL terpisah dari transaksi seed;
seeder gagal tidak otomatis menurunkan schema.

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

## QA Pengeluaran Stock

`php artisan test --filter=StockIssue` mencakup schema legacy-preserving,
no-negative/agregat, replay/payload conflict, source line unique, multigudang,
snapshot signed ledger, FIFO order/residual, inactive master, corruption guard,
rollback persistence/audit exception/veto dan larangan down berisi posted data.

`php scripts/qa/stock-issues-mysql.php` memakai database generated disposable
pos_stock_issues_qa dan dua koneksi RR. Memeriksa stale/current balance dan replay
header/lines, native migration up/down, FK RESTRICT, transaction boundary dan
worker proses lain yang terbukti LOCK WAIT lalu ditolak saat stok terakhir
habis. Perlu akses metadata innodb_trx pada MySQL local. Cleanup finally;
gagal menghasilkan exit1 tanpa menampilkan payload/credential. Bukan load test
seluruh interleaving atau bukti checkout/payment end-to-end.

`node scripts/qa/stock-quantity-format.mjs` memeriksa 11 kasus angka Indonesia,
termasuk -0,5/-0,000001 dan nilai besar tanpa float. Node24 existing mendukung
import erasable TypeScript source; tidak memasang runner/dependency baru.

`node scripts/qa/stock-issues-browser.mjs` dengan QA_PLAYWRIGHT_MODULE existing
memakai fixture stock-fixture --issues pada SQLite/profile terisolasi.
Demo kabel CCTV keluar0,5m hanya di disposable, memeriksa signed ledger,
saldo9,5, filter dokumen, readonly, desktop/tablet Light/Dark dan console.
Screenshot/report ignored. DevTools tetap dicoba; smoke redirect/login bukan
klaim authenticated CRUD. Mobile tidak diuji, theme tidak diubah.

`php scripts/qa/apply-stock-issues-local.php` hanya MySQL local: migration
additive tanpa seeder/grant/reset atau pengeluaran pengguna. Fingerprint semua
kolom yang sudah ada sebelum migration dipertahankan; kolom snapshot nullable
baru tidak dianggap mutation historis. Tabel issue baru harus kosong.

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

## QA Posting POS Atomic

`php artisan test --filter=PosCheckoutTest --compact` memeriksa net multi-payment,
snapshot/no private fields, retry canonical/unique collision, quote stale,
super-system invariant, multi-satuan, rollback sesudah Stock/Finance/invoice
persist gagal, audit veto, ownership/permission, CSRF419 dan posted immutable.
InvoiceSchemaTest memeriksa empty up/down; CheckoutLockBoundaryTest memastikan
lock/read dan published writers memerlukan transaction default caller.

`php scripts/qa/pos-checkout-mysql.php` hanya database generated
pos_checkout_qa_[16hex], dengan fixture CCTV/kelistrikan. Mencakup native DDL,
RR stale harga/customer/unit/method/flag jual (field error spesifik), dua worker
LOCK WAIT untuk replay sumber sama dan perebutan stok terakhir, replay snapshot
peer dan FK. Database/proses dibersihkan. Bukan load test seluruh interleaving;
lock level konservatif dapat menserialisasi checkout lintas gudang.

`php scripts/qa/apply-pos-checkout-local.php` hanya schema/permission catalog
additive. Fingerprint seluruh data bisnis existing termasuk permission lama dan
grant harus identik; hanya tiga key POS guard web yang boleh ditambahkan, tidak
grant/reset atau seed invoice. Metadata migrations dan cache/cache_locks turunan
dikecualikan (Spatie menginvalidasi cache). Tabel invoice baru wajib kosong.

Regresi katalog/quote memakai pos-demo-browser.mjs existing, terpisah dari QA
UI pembayaran di bawah. Dropdown Shortcut harus selesai mengembalikan fokus setelah Escape
sebelum Alt+F diuji; script menunggu state, bukan mengganti shortcut aplikasi.
DevTools discovery tetap dicoba; jika tool tidak tersedia catat BLOCKED, tidak
mengklaim fallback sebagai DevTools PASS. Desktop/tablet Light/Dark, bukan mobile.

## QA UI Checkout POS

`node scripts/qa/pos-payment-ui.mjs` memeriksa parsing rupiah Indonesia/scale2,
BigInt tanpa float, aturan lunas/satu cash/unique, overflow, ULID crypto dan shape
recovery. Non-cash berlebih tidak boleh menampilkan kembalian palsu.

```powershell
$env:QA_PLAYWRIGHT_MODULE = 'C:/path/to/existing/playwright-core'
node scripts/qa/pos-checkout-browser.mjs
node scripts/qa/pos-demo-browser.mjs
```

Coba discovery Chrome DevTools MCP dahulu; jika tool tidak tersedia catat
BLOCKED terpisah dari hasil fallback. Browser memakai Playwright existing,
server loopback/profile terisolasi dan SQLite parties-browser-[16hex] pada
storage/framework/testing. Fixture memakai credential random via stdin, demo
master relasional CCTV/kelistrikan dan stok disposable; tidak membuat transaksi
di database pengguna. Font CDN distub, browser/server ditutup pada finally.
Verification helper menolak environment/database/path lain dan hanya mengeluarkan
counts; repricing untuk QA terbatas ke fixture. Artefak screenshot/report ignored.

Coverage checkout: multi-payment/kembalian, metode gagal/reload, noncash berlebih,
double submit, respons hilang setelah commit lalu pending reload/retry payload/key
identik, stok/payment tidak ganda, quote stale422/cart tetap, create-only tidak
bisa membayar, storage unavailable mencegah POST, receipt GET/reload/cetak.
Desktop1440x1000, tablet1024x768/768x1024, Light/Dark; screenshot diperiksa visual
dan overflow. Cetak memeriksa portal receipt saja, background putih dan scroll
lock dilepas pada media print termasuk Dark. Window.print distub: tidak mengklaim
driver/printer fisik atau pagination receipt panjang telah diuji. Mobile tidak diuji.

503/ERR_FAILED/422 yang diinjeksi dicatat terpisah; console/page error lain gagal.
Regresi katalog/quote/shortcut memakai pos-demo-browser, bukan mengganti QA
checkout. Bukti row lock/concurrency tetap melalui helper MySQL di atas, tidak
dianggap terbukti oleh SQLite/browser. Recovery hanya per user/tab; hilang tab/
storage memerlukan rekonsiliasi sebelum membuat pembayaran baru dengan key baru.

## QA Riwayat Penjualan

`php artisan test --filter=InvoiceHistoryTest --compact` memeriksa guest/outsider/
create-only, post own scope pada rows/count/options/detail, view dan super-system,
filter WIB tepat midnight/end-day, literal wildcard/SQL-like text, master changed/
deleted snapshot, pagination stable, nominal besar exact, whitelist/tamper dan
read tidak mengubah invoice/Stock/Finance/activity. Inertia backend test memakai
withoutVite; validasi asset nyata dilakukan pada build/browser terpisah.

`php scripts/qa/pos-checkout-mysql.php` juga memeriksa read history native:
grouped snapshot choices, scope, LIKE escape, batas tanggal WIB dan decimal string;
tetap hanya database generated/cleanup, bukan database bisnis pengguna.

```powershell
$env:QA_PLAYWRIGHT_MODULE = 'C:/path/to/existing/playwright-core'
node scripts/qa/invoice-history-browser.mjs
```

DevTools discovery dahulu; BLOCKED jika tool tidak tersedia, fallback existing
dicatat terpisah. Fixture parties-browser SQLite guarded memakai seed master
module CCTV/kelistrikan dan PostInvoice nyata:28 invoice, dua kasir/dua gudang.
tests/Browser/invoice-history-data.php hanya menerima path/environment disposable,
credential via stdin dan menolak seed jika invoice sudah ada. Fingerprint
invoice/Stock/Finance/activity sebelum/sesudah browser harus identik.
Browser: own/view/outsider/create-only, filters/URL/reload/pagination, shortcut/
palette, historical master, receipt error/reload/stale cancel/print dan readonly.
Desktop1440x1000, tablet1024x768/768x1024, Light/Dark, expanded/collapsed;
console unexpected kosong,503 yang diinjeksi terpisah. Browser/server ditutup
pada finally, report/screenshot ignored dan diperiksa visual. Bukan mobile,
printer fisik, load test atau rekonsiliasi otomatis transaksi ambigu.

## QA Penerimaan Internal Finance

`php artisan test --filter=RecordSalePaymentTest --compact` mencakup net cash,
snapshot/privacy/actor, replay canonical/conflict, validasi master, history HTTP
guard termasuk super-system, assignment recheck, audit veto/rollback dan FK.
PaymentReceiptSchemaTest mencakup empty up/down additive. SQLite bukan bukti
row lock atau precision nominal besar native MySQL.

`php scripts/qa/payment-records-mysql.php` hanya membuat database generated
pos_payment_records_qa_[16hex], tidak menulis transaksi database pengguna.
Mencakup native migration, RR stale status/assignment, replay/current history,
nominal di atas integer aman JS dan batas decimal30,2, worker proses terpisah
terbukti LOCK WAIT lalu replay receipt yang sama, FK RESTRICT serta guard down.
Database generated dibersihkan, error teredaksi/exit1. Bukan load test seluruh
interleaving invoice+Stock+Finance sebelum integrasi POS selesai.

`php scripts/qa/apply-payment-records-local.php` hanya migration Finance local
additive, fingerprint seluruh kolom/tabel pada schema project aktif (kecuali
metadata migrations). Tidak seed/grant/reset atau membuat transaksi pengguna.
Tabel baru harus kosong. Jangan down migration yang sudah berisi penerimaan.
Browser regresi master memakai helper di bawah; guard histori baru diperiksa
HTTP/action/MySQL; QA UI checkout Sales/POS memakai helper terpisah di atas.

## QA Master Finance/Payments

`php artisan test --filter=Payments` mencakup ULID, permission tiap mutation,
CRUD/restore, unique termasuk trashed, kompatibilitas/akun aktif, guard referensi
termasuk metode terhapus, no-op, privasi audit, rollback, filter/choices dan demo
idempotent tanpa grant. Migration up/down memakai database test disposable.

`php scripts/qa/payments-mysql.php` membuat database generated
pos_payments_qa dengan suffix random tervalidasi. Dua koneksi REPEATABLE READ
menguji reference current read sesudah snapshot lama, akun terbaru saat
assignment, dirty check, assignment berubah saat restore, FK RESTRICT dan demo
idempotent. Cleanup database disposable pada finally; bukan load test atau
bukti semua interleaving concurrency produksi.

```powershell
$env:QA_PLAYWRIGHT_MODULE = 'C:/path/to/existing/playwright-core'
node scripts/qa/payments-browser.mjs
```

Coba discovery Chrome DevTools MCP dahulu. Jika tool tidak tersedia, catat
BLOCKED dan gunakan fallback existing di atas tanpa memasang dependency.
Fixture `tests/Browser/payments-fixture.php` hanya SQLite disposable
payments-browser pada storage/framework/testing. Credential random via stdin,
server loopback/profile terisolasi, font CDN distub, browser/server ditutup pada
finally. Screenshot/report ignored, bukan data pengguna.

Coverage browser: CRUD/restore dua master, relasi persist/reload, pilihan akun
sesuai jenis dan reset saat jenis berubah, metadata conditional/masked, kode
duplikat/error, guard akun terpakai termasuk metode terhapus, filter/search/empty,
readonly/outsider, shortcut/palette, sidebar expanded/collapsed, Light/Dark,
desktop1440x1000 dan tablet1024x768/768x1024. Mobile tidak diuji. Console/page
error tidak terduga harus gagal; screenshot diperiksa visual dan overflow.

`php scripts/qa/apply-payments-local.php` hanya MySQL local: migration Finance
additive, katalog permission tanpa grant dan demo dua akun/tiga metode yang
idempotent. Fingerprint data existing user/role/pivot/master/harga/stok/ledger
serta row Finance/audit/permission existing harus identik. Tidak migrate:fresh,
reset password, membuat saldo/payment record, atau mengaktifkan pembayaran POS.

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

## QA Patch HTTP Foundation

`php vendor/bin/phpunit tests/Unit/HttpFoundationHeaderTest.php` mereproduksi
Cache-Control whitespace space/tab/mixed melalui Request/HeaderBag dengan
pcre.backtrack_limit10000 yang dipulihkan pada finally. Directive max-age dan
no-cache tidak boleh hilang; header normal/quoted dan split separator space/tab
tetap kompatibel. RED7.4.18 dan GREEN7.4.21 dicatat pada
[work item patch](work-items/http-foundation-security-patch/README.md).
Jalankan composer audit --locked/validate --strict dan full backend/browser
setelah perubahan package HTTP. Test bounded in-memory, bukan DoS server hidup
atau klaim benchmark seluruh regex/input. Tidak patch vendor manual.

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
