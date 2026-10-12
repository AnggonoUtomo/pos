<?php

namespace App\Modules\Inventory\Items\Application\Contracts;

use App\Modules\Inventory\Items\Application\DTOs\ItemStockData;

interface ItemStockLookup
{
    /** Caller locks selected Units first; lock requires the shared transaction. */
    public function findActive(string $id, bool $lock = false): ?ItemStockData;

    /** @return list<ItemStockData> Bounded choices for stock input, not reporting. */
    public function choices(): array;
}
