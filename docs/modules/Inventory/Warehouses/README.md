# Inventory/Warehouses

## Status

Active: fondasi backend selesai dan terverifikasi pada
[Increment 2 Inventory Foundation](../../../work-items/inventory-foundation/tasks.md).
Endpoint internal data/store tersedia sejak Increment 5A. UI CRUD dan integrasi
POS belum tersedia.

## Tujuan Dan Boundary

Memiliki master gudang untuk single company dan multi gudang. Tidak memiliki
saldo stok, FIFO, lokasi rak, transfer, atau transaksi POS.

## Public Boundary

WarehouseRepository/CreateWarehouseData dipakai oleh CreateWarehouse dan
ListWarehouses di dalam module. Route data/store memanggil controller pada
Presentation, dengan permission per action. Tidak ada contract/event lintas
module tanpa consumer. Model Infrastructure bersifat privat module.

## Dependency

Laravel Eloquent, Spatie Permission, dan Spatie Activitylog. CompanySettings
belum diperlukan untuk master gudang single company tanpa konfigurasi tambahan.

## Permission

`inventory.view` dan `inventory.manage` mengikuti work item aktif. Seeder
permission tidak memberikan role atau akses otomatis kepada user.

## Data Dan Seeder

- Tabel `warehouses`: ULID, kode unik, nama, status aktif, timestamp, soft delete.
- Migration milik module berada pada `Database/Migrations/`.
- `Database/Seeders/WarehousesAccessSeeder.php` menyiapkan permission.
- `Database/Seeders/WarehousesDemoSeeder.php` menyiapkan gudang demo hanya pada
  environment local/testing, secara idempotent dan tanpa menimpa perubahan user.
- Tidak memanggil seeder Identity atau mengubah akun saat seed gudang.

## Operasi Dan Verifikasi

```powershell
php artisan migrate
php artisan db:seed --class="App\Modules\Inventory\Warehouses\Database\Seeders\WarehousesAccessSeeder"
php artisan db:seed --class="App\Modules\Inventory\Warehouses\Database\Seeders\WarehousesDemoSeeder"
php artisan test --filter=Warehouses
```

UI CRUD lengkap dan pemilihan gudang POS masih menunggu increment berikutnya.
Detail ada pada [specification](specification.md), [plan](plan.md),
dan [tasks](tasks.md).
