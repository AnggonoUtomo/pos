<?php

namespace App\Modules\Inventory\Items\Application\Contracts;

use App\Modules\Inventory\Items\Application\DTOs\ItemPricingData;

interface ItemPricingLookup
{
    public function findIdBySku(string $sku): ?string;

    /** Requires shared transaction; locks Units, item and relations in canonical order. */
    public function lock(string $itemId): ?ItemPricingData;
}
