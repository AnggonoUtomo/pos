# Inventory/Units

## Status

Active: fondasi backend selesai dan terverifikasi pada Increment 3
[Inventory Foundation](../../../work-items/inventory-foundation/tasks.md).
Endpoint HTTP, UI CRUD, dan consumer Items belum tersedia.

## Tujuan Dan Boundary

Memiliki master satuan dengan kode, nama, simbol, dan status aktif. Base unit
dan conversion ratio adalah milik barang pada Inventory/Items sesuai ADR-009.
Unit yang sama dapat dipakai banyak barang dengan ratio berbeda.

## Public Boundary

Belum ada contract, DTOs, event publik, atau endpoint HTTP. Model Infrastructure
bersifat privat. Contract lookup satuan dibuat pada Increment 4 ketika Items
menjadi consumer nyata. Routes/web.php disiapkan oleh generator.

## Dependency Dan Permission

Laravel Eloquent, Spatie Permission, Spatie Activitylog. Permission awal
`inventory.view` dan `inventory.manage` mengikuti work item aktif. Access
seeder hanya memastikan permission tersedia; tidak memberi akses kepada user.

## Data Dan Demo Seeder

- Tabel `units`: ULID, kode unik, nama, simbol, status aktif, dan soft delete.
- Migration berada pada `Database/Migrations/` milik module.
- `Database/Seeders/UnitsAccessSeeder.php` menyiapkan permission.
- `Database/Seeders/UnitsDemoSeeder.php` mengisi PCS, PACK, BOX, KG, G, L, ML
  secara idempotent pada local/testing tanpa mengubah existing row.
- Perubahan model diaudit sejak awal memakai log `inventory.units`.

## Operasi Dan Verifikasi

```powershell
php artisan migrate
php artisan db:seed --class="App\Modules\Inventory\Units\Database\Seeders\UnitsAccessSeeder"
php artisan db:seed --class="App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder"
php artisan test --filter=Units
```

UI CRUD dan permission HTTP menunggu Increment 6. Detail tersedia pada
[specification](specification.md), [plan](plan.md), dan [tasks](tasks.md).
