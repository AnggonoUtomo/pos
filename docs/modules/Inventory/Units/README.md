# Inventory/Units

## Status

Active: fondasi backend selesai dan terverifikasi pada Increment 3
[Inventory Foundation](../../../work-items/inventory-foundation/tasks.md).
Consumer Items memakai lookup public sejak Increment 4. Endpoint internal
data/store tersedia sejak Increment 5A; UI CRUD desktop/tablet tersedia pada Increment 6.

## Tujuan Dan Boundary

Memiliki master satuan dengan kode, nama, simbol, dan status aktif. Base unit
dan conversion ratio adalah milik barang pada Inventory/Items sesuai ADR-009.
Unit yang sama dapat dipakai banyak barang dengan ratio berbeda.

## Public Boundary

`Application/Contracts/UnitLookup` menyediakan `findActiveById(id, lock)` dan
`findActiveByCode(code)`, dengan hasil `Application/DTOs/UnitData` atau null;
`listActive()` menyediakan maksimal 1000 pilihan untuk ListItems.
Lookup hanya mengembalikan satuan aktif yang tidak dihapus. Lock memerlukan
transaction pada connection default bersama; dipakai CreateItem untuk mutation.
Model Infrastructure tetap privat. Tidak ada event publik. Route data/store
memanggil CreateUnit/ListUnits melalui controller Presentation dengan can per
action. UnitRepository dan CreateUnitData adalah boundary internal module.

## Dependency Dan Permission

Laravel Eloquent, Spatie Permission, Spatie Activitylog. Permission awal
`inventory.view` dan `inventory.manage` mengikuti work item aktif. Access
seeder hanya memastikan permission tersedia; tidak memberi akses kepada user.

## Data Dan Demo Seeder

- Tabel `units`: ULID, kode unik, nama, simbol, status aktif, dan soft delete.
- Migration berada pada `Database/Migrations/` milik module.
- `Database/Seeders/UnitsAccessSeeder.php` menyiapkan permission.
- `Database/Seeders/UnitsDemoSeeder.php` mengisi PCS, UNIT, SET, PAIR, PACK, BOX, M, ROLL
  secara idempotent pada local/testing tanpa mengubah existing row.
- Perubahan model diaudit sejak awal memakai log `inventory.units`.

## Operasi Dan Verifikasi

```powershell
php artisan migrate
php artisan db:seed --class="App\Modules\Inventory\Units\Database\Seeders\UnitsAccessSeeder"
php artisan db:seed --class="App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder"
php artisan test --filter=Units
```

ManageUnit memanggil public ItemUnitUsage untuk menolak hapus/nonaktif satuan
terpakai, termasuk referensi terhapus. UI CRUD terverifikasi pada Increment 6.
Detail tersedia pada
[specification](specification.md), [plan](plan.md), dan [tasks](tasks.md).
