<?php

namespace App\Modules\Inventory\Stock\Application\Contracts;

interface StockUsage
{
    /** Caller holds the corresponding master lock in the shared transaction. */
    public function hasItemHistory(string $itemId): bool;

    public function hasWarehouseHistory(string $warehouseId): bool;
}
