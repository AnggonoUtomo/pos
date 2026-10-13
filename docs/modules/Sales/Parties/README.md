# Module Sales/Parties

Status: Active; scope fondasi selesai diverifikasi 13 Oktober 2026.
Master pelanggan dan level pelanggan untuk toko CCTV dan
kelistrikan. Scope disetujui user 12 Oktober 2026.

CRUD level/pelanggan, status aktif, soft delete/restore, permission backend,
audit, demo relasional dan UI admin desktop/tablet. Level demo Eceran, Grosir,
Installer. Kontak/alamat opsional, level aktif wajib saat create/edit/restore.

Non-scope: pricing per barang/satuan, transaksi POS, supplier, kredit limit,
finance, nomor seri dan garansi. Tidak membuat contract/event lintas module
sebelum ada consumer. Tidak mengubah theme atau Old-docs.

Composition root: app/Modules/Sales/Parties/ServiceProvider.php.
Routes, migration dan seeder dimiliki module. Controller memakai Application;
repository mengisolasi Eloquent. Data dan audit commit/rollback bersama.

Demo hanya local/testing, menjaga edit/nonaktif/trashed dan tidak membuat akun,
role atau grant. Permission provisioning terpisah dari demo. Informasi kontak
pelanggan tidak disalin ke activity log.

## Operasi

Halaman `/sales/customers` dan `/sales/customer-levels`; permission
`sales.parties.view`/`sales.parties.manage`. Role super-system mengikuti
Gate existing, bukan bypass validasi. Detail ada pada [specification](specification.md)
dan bukti pada [tasks](tasks.md).

Penerapan additive local: `php scripts/qa/apply-parties-local.php`, hanya
migration module dan permission catalog, tanpa grant/demo/reset. Fingerprint
data existing diperiksa tanpa menampilkan data akun/kontak.

Demo opsional local/testing, dijalankan eksplisit:

```powershell
php artisan db:seed --class="App\Modules\Sales\Parties\Database\Seeders\PartiesDemoSeeder"
```

QA: `php artisan test --filter=Parties`, `php scripts/qa/parties-mysql.php`,
dan `node scripts/qa/parties-browser.mjs` dengan QA_PLAYWRIGHT_MODULE mengarah
ke package Playwright existing. Tidak memasang dependency baru.
