<?php

namespace App\Modules\Inventory\Items\Application\DTOs;

final readonly class CreateItemData
{
    /** @param list<ItemUnitData> $units */
    public function __construct(
        public string $sku,
        public string $name,
        public string $baseUnitId,
        public array $units = [],
        public bool $isActive = true,
    ) {}
}
