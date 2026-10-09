<?php

namespace App\Modules\Inventory\Items\Application\DTOs;

final readonly class ItemUnitData
{
    public function __construct(
        public string $unitId,
        public string $conversionRatio,
        public ?string $barcode = null,
        public bool $isSaleUnit = true,
        public bool $isPurchaseUnit = true,
    ) {}
}
