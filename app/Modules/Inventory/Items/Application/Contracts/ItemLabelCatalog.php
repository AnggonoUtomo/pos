<?php

namespace App\Modules\Inventory\Items\Application\Contracts;

use App\Modules\Inventory\Items\Application\DTOs\ItemLabelData;

interface ItemLabelCatalog
{
    /** @return array{data: list<ItemLabelData>, has_more: bool} Active barcode units, including purchase-only units. */
    public function search(string $query, int $page): array;

    public function find(string $itemId, string $unitId): ?ItemLabelData;
}
