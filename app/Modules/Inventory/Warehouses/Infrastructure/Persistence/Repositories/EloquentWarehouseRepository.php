<?php

namespace App\Modules\Inventory\Warehouses\Infrastructure\Persistence\Repositories;

use App\Modules\Inventory\Warehouses\Application\Contracts\WarehouseRepository;
use App\Modules\Inventory\Warehouses\Application\DTOs\CreateWarehouseData;
use App\Modules\Inventory\Warehouses\Infrastructure\Persistence\Models\Warehouse;
use App\Modules\Platform\OperationalSettings\Application\Contracts\TablePagination;

class EloquentWarehouseRepository implements WarehouseRepository
{
    public function __construct(private TablePagination $pagination) {}

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

    public function paginate(int $page, array $filters = []): array
    {
        $query = ! empty($filters['trashed']) ? Warehouse::onlyTrashed() : Warehouse::query();
        $query->when($filters['search'] ?? '', fn ($query, $search) => $query->where(fn ($row) => $row
            ->where('code', 'like', '%'.$search.'%')->orWhere('name', 'like', '%'.$search.'%')));
        if (in_array($filters['status'] ?? 'all', ['active', 'inactive'], true)) {
            $query->where('is_active', $filters['status'] === 'active');
        }
        $columns = ['id', 'code', 'name', 'is_active'];
        if ($filters !== []) {
            $columns[] = 'deleted_at';
        }
        $paginator = $query->orderBy('code')->orderBy('id')->paginate($this->pagination->perPage(), $columns, page: $page);

        return [
            'data' => $paginator->getCollection()->toArray(),
            'meta' => [
                'current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(), 'total' => $paginator->total(),
            ],
        ];
    }
}
