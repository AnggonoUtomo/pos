<?php

namespace App\Modules\Sales\Parties\Application\Contracts;

use App\Modules\Sales\Parties\Application\DTOs\ItemPriceData;
use App\Modules\Sales\Parties\Application\DTOs\PriceLevelData;

interface ItemPriceCatalog
{
    /** @return list<PriceLevelData> */
    public function activeLevels(bool $lock = false): array;

    /** @param list<ItemPriceData> $prices */
    public function saveForItem(string $itemId, array $prices): void;

    /** @return array<string, list<ItemPriceData>> */
    public function forItems(array $itemIds): array;
}
