<?php

namespace App\Modules\Inventory\Units\Infrastructure\Persistence\Repositories;

use App\Modules\Inventory\Units\Application\Contracts\UnitRepository;
use App\Modules\Inventory\Units\Application\DTOs\CreateUnitData;
use App\Modules\Inventory\Units\Infrastructure\Persistence\Models\Unit;

class EloquentUnitRepository implements UnitRepository
{
    public function lock(string $id, bool $deleted = false): array
    {
        return ($deleted ? Unit::onlyTrashed() : Unit::query())->whereKey($id)->lockForUpdate()->firstOrFail()->attributesToArray();
    }

    public function update(string $id, CreateUnitData $data): void
    {
        Unit::findOrFail($id)->update([
            'code' => $data->code, 'name' => $data->name,
            'symbol' => $data->symbol,
            'is_active' => $data->isActive,
        ]);
    }

    public function delete(string $id): void
    {
        Unit::findOrFail($id)->delete();
    }

    public function restore(string $id): void
    {
        Unit::onlyTrashed()->findOrFail($id)->restore();
    }

    public function create(CreateUnitData $data): string
    {
        return Unit::query()->create([
            'code' => $data->code, 'name' => $data->name,
            'symbol' => $data->symbol,
            'is_active' => $data->isActive,
        ])->id;
    }

    public function paginate(int $page, array $filters = []): array
    {
        $query = ! empty($filters['trashed']) ? Unit::onlyTrashed() : Unit::query();
        $query->when($filters['search'] ?? '', fn ($query, $search) => $query->where(fn ($row) => $row
            ->where('code', 'like', '%'.$search.'%')->orWhere('name', 'like', '%'.$search.'%')));
        if (in_array($filters['status'] ?? 'all', ['active', 'inactive'], true)) {
            $query->where('is_active', $filters['status'] === 'active');
        }
        $columns = ['id', 'code', 'name', 'symbol', 'is_active'];
        if ($filters !== []) {
            $columns[] = 'deleted_at';
        }
        $paginator = $query->orderBy('code')->orderBy('id')->paginate(25, $columns, page: $page);

        return [
            'data' => $paginator->getCollection()->toArray(),
            'meta' => [
                'current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(), 'total' => $paginator->total(),
            ],
        ];
    }
}
