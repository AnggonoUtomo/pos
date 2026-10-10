<?php

namespace App\Modules\Inventory\Items\Application\Queries;

use App\Modules\Inventory\Items\Application\Contracts\ItemRepository;
use App\Modules\Inventory\Units\Application\Contracts\UnitLookup;
use App\Modules\Inventory\Units\Application\DTOs\UnitData;

class ListItems
{
    public function __construct(private ItemRepository $items, private UnitLookup $units) {}

    /** @return array{data: list<array<string, mixed>>, meta: array<string, int>} */
    public function handle(int $page = 1, array $filters = []): array
    {
        return $this->items->paginate($page, $filters);
    }

    public function unitOptions(): array
    {
        return array_map(fn (UnitData $unit): array => ['id' => $unit->id, 'code' => $unit->code, 'name' => $unit->name, 'symbol' => $unit->symbol], $this->units->listActive());
    }
}
