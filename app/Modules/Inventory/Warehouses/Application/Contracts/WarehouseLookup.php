<?php

namespace App\Modules\Inventory\Warehouses\Application\Contracts;

use App\Modules\Inventory\Warehouses\Application\DTOs\WarehouseData;

interface WarehouseLookup
{
    public function findActive(string $id, bool $lock = false): ?WarehouseData;

    /** @return list<WarehouseData> At most 1000 input choices. */
    public function choices(): array;
}
