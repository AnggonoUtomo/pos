<?php

namespace App\Modules\Inventory\Items\Application\DTOs;

final readonly class ItemStockData
{
    /** @param list<ItemUnitData> $units */
    public function __construct(public string $id, public string $sku, public string $name, public string $baseUnitId, public array $units) {}
}
