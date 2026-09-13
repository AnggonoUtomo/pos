# Quality Dan Verifikasi

Mulai dari test paling spesifik dan naik ke gate yang lebih luas sesuai risiko.
Jangan mengklaim selesai jika command gagal atau belum dijalankan.

## Matriks Verifikasi

| Perubahan | Verifikasi minimum |
| --- | --- |
| Dokumentasi | Link relatif dicek manual sesuai scope dan `git diff --check` |
| Backend behavior | Focused feature/unit test |
| Boundary atau module | Dry-run generator bila relevan, focused test, `route:list`, dan migration/status check bila schema berubah |
| Frontend behavior | `npm run lint` dan `npm run build` bila menyentuh React/TS/CSS |
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
npm run lint
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
npm run lint
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

Urutan akses eksplisit:

1. Cari tool DevTools melalui `tool_search` dengan kata kunci Chrome DevTools
   atau browser testing.
2. Load skill `browser-testing-with-devtools` bila pekerjaan UI membutuhkan QA
   browser.
3. Buka halaman target pada browser yang dikontrol tool.
4. Cek console error, network error penting, DOM state, permission visibility,
   loading state, dan empty/error state.
5. Ambil screenshot atau catatan visual bila halaman berubah signifikan.
6. Untuk layout admin, cek sidebar expanded, collapsed, dan mobile drawer bila
   relevan.
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
