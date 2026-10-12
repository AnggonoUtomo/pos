<?php

namespace App\Modules\Inventory\Warehouses\Infrastructure\Persistence\Repositories;

use App\Modules\Inventory\Warehouses\Application\Contracts\WarehouseLookup;
use App\Modules\Inventory\Warehouses\Application\DTOs\WarehouseData;
use App\Modules\Inventory\Warehouses\Infrastructure\Persistence\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use LogicException;

class EloquentWarehouseLookup implements WarehouseLookup
{
    public function findActive(string $id, bool $lock = false): ?WarehouseData
    {
        if ($lock && DB::transactionLevel() === 0) {
            throw new LogicException('Lookup lock memerlukan transaction.');
        }
        $warehouse = Warehouse::where('is_active', true)->whereKey($id)
            ->when($lock, fn ($query) => $query->lockForUpdate())->first();

        return $warehouse ? new WarehouseData($warehouse->id, $warehouse->code, $warehouse->name) : null;
    }

    public function choices(): array
    {
        return Warehouse::where('is_active', true)->orderBy('code')->orderBy('id')->limit(1000)->get()
            ->map(fn ($row) => new WarehouseData($row->id, $row->code, $row->name))->all();
    }
}
