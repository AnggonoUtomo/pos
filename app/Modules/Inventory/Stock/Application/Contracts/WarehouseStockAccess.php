<?php

namespace App\Modules\Inventory\Stock\Application\Contracts;

interface WarehouseStockAccess
{
    public function assertWritable(string $warehouseId, ?string $stockCountId = null): void;
}
