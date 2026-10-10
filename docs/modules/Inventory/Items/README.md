# Inventory/Items

## Status

Active: fondasi backend Increment 4A/4B selesai dan terverifikasi pada
9 Oktober 2026; endpoint internal data/store tersedia sejak Increment 5B pada
10 Oktober 2026. UI CRUD lengkap dan integrasi transaksi belum tersedia.

## Tujuan Dan Boundary

Memiliki barang, SKU, base unit, satuan jual/beli, barcode per satuan barang,
dan rasio langsung ke base unit. Tidak memiliki stok, FIFO, harga level,
payment, kategori/brand, atau transaksi POS pada increment ini.

## Public Boundary Dan Dependency

CreateItem adalah use case internal. Data input berada di Application/DTOs;
port persistence berada di Application/Contracts. Model Eloquent tetap privat.
Consumer lintas module Items belum tersedia, sehingga tidak membuat event atau
contract publik transaksi hipotetis. Items memakai UnitLookup dan UnitData dari
public boundary Units untuk membaca satuan aktif, bukan model Eloquent Units.

Domain ConversionRatio bebas framework dan memakai brick/math yang sudah
tersedia melalui dependency Laravel. ServiceProvider menghubungkan repository
ke adapter serta memuat migration/route. Route data/store memanggil ListItems
dan CreateItem melalui Presentation; HasMiddleware memakai can per action.
UI belum dibuat; response/validation contract tersedia pada docs/API.md.

## Data, Permission, Dan Demo

- items dan item_units memiliki ULID dan soft delete sebagai master mutable.
- SKU dan barcode unik tetap direservasi setelah soft delete.
- Permission awal: inventory.view dan inventory.manage, guard web.
- Perubahan diaudit memakai inventory.items dan inventory.item_units.
- ItemsDemoSeeder memerlukan Units demo terlebih dahulu; memanggil CreateItem
  dan tidak menimpa barang existing, termasuk data terhapus.

## Operasi

```powershell
php artisan migrate
php artisan db:seed --class="App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder"
php artisan db:seed --class="App\Modules\Inventory\Items\Database\Seeders\ItemsAccessSeeder"
php artisan db:seed --class="App\Modules\Inventory\Items\Database\Seeders\ItemsDemoSeeder"
php artisan test --filter="Items|UnitLookup"
```

Detail tersedia pada [specification](specification.md), [plan](plan.md), dan
[tasks](tasks.md). Work item global masih In Progress sampai Increment 5-6 selesai.
