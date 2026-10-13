<?php

namespace App\Modules\Inventory\Stock\Application\Contracts;

interface StockAvailability
{
    /** @return array<string, string> Base quantity, zero for missing balances; no reservation. */
    public function forItems(string $warehouseId, array $itemIds): array;
}
