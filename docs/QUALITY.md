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
