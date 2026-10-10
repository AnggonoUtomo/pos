<?php

namespace App\Modules\Inventory\Units\Infrastructure\Persistence\Repositories;

use App\Modules\Inventory\Units\Application\Contracts\UnitRepository;
use App\Modules\Inventory\Units\Application\DTOs\CreateUnitData;
use App\Modules\Inventory\Units\Infrastructure\Persistence\Models\Unit;

class EloquentUnitRepository implements UnitRepository
{
    public function create(CreateUnitData $data): string
    {
        return Unit::query()->create([
            'code' => $data->code, 'name' => $data->name,
            'symbol' => $data->symbol,
            'is_active' => $data->isActive,
        ])->id;
    }

    public function paginate(int $page): array
    {
        $paginator = Unit::query()->orderBy('code')->orderBy('id')
            ->paginate(25, ['id', 'code', 'name', 'symbol', 'is_active'], page: $page);

        return [
            'data' => $paginator->getCollection()->toArray(),
            'meta' => [
                'current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(), 'total' => $paginator->total(),
            ],
        ];
    }
}
