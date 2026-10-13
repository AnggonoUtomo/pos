<?php

namespace App\Modules\Inventory\Items\Application\Queries;

use App\Modules\Inventory\Items\Application\Contracts\ItemRepository;
use App\Modules\Inventory\Units\Application\Contracts\UnitLookup;
use App\Modules\Inventory\Units\Application\DTOs\UnitData;
use App\Modules\Sales\Parties\Application\Contracts\ItemPriceCatalog;
use App\Modules\Sales\Parties\Application\DTOs\ItemPriceData;
use App\Modules\Sales\Parties\Application\DTOs\PriceLevelData;

class ListItems
{
    public function __construct(private ItemRepository $items, private UnitLookup $units, private ItemPriceCatalog $prices) {}

    /** @return array{data: list<array<string, mixed>>, meta: array<string, int>} */
    public function handle(int $page = 1, array $filters = []): array
    {
        $result = $this->items->paginate($page, $filters);
        $prices = $this->prices->forItems(array_column($result['data'], 'id'));
        foreach ($result['data'] as &$row) {
            $row['prices'] = array_map(fn (ItemPriceData $price) => ['unit_id' => $price->unitId,
                'customer_level_id' => $price->customerLevelId, 'amount' => $price->amount], $prices[$row['id']] ?? []);
        }
        unset($row);

        return $result;
    }

    public function unitOptions(): array
    {
        return array_map(fn (UnitData $unit): array => ['id' => $unit->id, 'code' => $unit->code, 'name' => $unit->name, 'symbol' => $unit->symbol], $this->units->listActive());
    }

    public function priceLevels(): array
    {
        return array_map(fn (PriceLevelData $level) => ['id' => $level->id, 'code' => $level->code, 'name' => $level->name], $this->prices->activeLevels());
    }
}
