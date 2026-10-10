<?php

namespace App\Modules\Inventory\Warehouses\Infrastructure\Persistence\Repositories;

use App\Modules\Inventory\Warehouses\Application\Contracts\WarehouseRepository;
use App\Modules\Inventory\Warehouses\Application\DTOs\CreateWarehouseData;
use App\Modules\Inventory\Warehouses\Infrastructure\Persistence\Models\Warehouse;

class EloquentWarehouseRepository implements WarehouseRepository
{
    public function lock(string $id, bool $deleted = false): array
    {
        return ($deleted ? Warehouse::onlyTrashed() : Warehouse::query())->whereKey($id)->lockForUpdate()->firstOrFail()->attributesToArray();
    }

    public function update(string $id, CreateWarehouseData $data): void
    {
        Warehouse::findOrFail($id)->update([
            'code' => $data->code, 'name' => $data->name,
            'is_active' => $data->isActive,
        ]);
    }

    public function delete(string $id): void
    {
        Warehouse::findOrFail($id)->delete();
    }

    public function restore(string $id): void
    {
        Warehouse::onlyTrashed()->findOrFail($id)->restore();
    }

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
