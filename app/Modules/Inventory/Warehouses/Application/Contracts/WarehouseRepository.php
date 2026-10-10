<?php

namespace App\Modules\Inventory\Warehouses\Application\Contracts;

use App\Modules\Inventory\Warehouses\Application\DTOs\CreateWarehouseData;

interface WarehouseRepository
{
    public function create(CreateWarehouseData $data): string;

    /** @return array{data: list<array<string, mixed>>, meta: array<string, int>} */
    public function paginate(int $page): array;
}
