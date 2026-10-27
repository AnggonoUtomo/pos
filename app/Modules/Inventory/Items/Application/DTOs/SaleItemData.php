<?php

namespace App\Modules\Inventory\Items\Application\DTOs;

final readonly class SaleItemData
{
    /** @param list<SaleUnitData> $units */
    public function __construct(public string $id, public string $sku, public string $name,
        public string $baseUnitId, public string $baseUnitName, public array $units, public ?string $imageUrl = null) {}
}
