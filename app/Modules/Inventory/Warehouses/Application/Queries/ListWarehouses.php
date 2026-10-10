<?php

namespace App\Modules\Inventory\Warehouses\Application\Queries;

use App\Modules\Inventory\Warehouses\Application\Contracts\WarehouseRepository;

class ListWarehouses
{
    public function __construct(private WarehouseRepository $warehouses) {}

    /** @return array{data: list<array<string, mixed>>, meta: array<string, int>} */
    public function handle(int $page = 1, array $filters = []): array
    {
        return $this->warehouses->paginate($page, $filters);
    }
}
