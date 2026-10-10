<?php

namespace App\Modules\Inventory\Warehouses\Infrastructure\Persistence\Repositories;

use App\Modules\Inventory\Warehouses\Application\Contracts\WarehouseRepository;
use App\Modules\Inventory\Warehouses\Application\DTOs\CreateWarehouseData;
use App\Modules\Inventory\Warehouses\Infrastructure\Persistence\Models\Warehouse;

class EloquentWarehouseRepository implements WarehouseRepository
{
    public function create(CreateWarehouseData $data): string
    {
        return Warehouse::query()->create([
            'code' => $data->code, 'name' => $data->name,
            'is_active' => $data->isActive,
        ])->id;
    }

    public function paginate(int $page): array
    {
        $paginator = Warehouse::query()->orderBy('code')->orderBy('id')
            ->paginate(25, ['id', 'code', 'name', 'is_active'], page: $page);

        return [
            'data' => $paginator->getCollection()->toArray(),
            'meta' => [
                'current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(), 'total' => $paginator->total(),
            ],
        ];
    }
}
