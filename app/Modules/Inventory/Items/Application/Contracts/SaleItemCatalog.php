<?php

namespace App\Modules\Inventory\Items\Application\Contracts;

use App\Modules\Inventory\Items\Application\DTOs\SaleItemData;

interface SaleItemCatalog
{
    /** @return array{data: list<SaleItemData>, has_more: bool} */
    public function search(string $query, int $page): array;

    /** @return array<string, SaleItemData> At most 100 requested items. */
    public function findMany(array $ids, bool $lock = false): array;
}
