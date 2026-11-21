<?php

namespace App\Modules\Inventory\Items\Application\DTOs;

final readonly class ItemLabelData
{
    public function __construct(public string $itemId, public string $unitId, public string $sku,
        public string $name, public string $unitName, public string $barcode) {}

    public function toArray(): array
    {
        return ['item_id' => $this->itemId, 'unit_id' => $this->unitId, 'sku' => $this->sku,
            'name' => $this->name, 'unit_name' => $this->unitName, 'barcode' => $this->barcode];
    }
}
