<?php

namespace App\Modules\Inventory\Items\Application\Contracts;

use App\Modules\Inventory\Items\Application\DTOs\CreateItemData;

interface ItemRepository
{
    public function create(CreateItemData $data): string;

    public function findIdBySku(string $sku): ?string;

    /** @return array{data: list<array<string, mixed>>, meta: array<string, int>} */
    public function paginate(int $page): array;
}
