<?php

namespace App\Modules\Inventory\Stock\Application\Queries;

use App\Modules\Inventory\Items\Application\Contracts\ItemStockLookup;
use App\Modules\Inventory\Stock\Application\Contracts\StockRepository;
use App\Modules\Inventory\Units\Application\Contracts\UnitLookup;
use App\Modules\Inventory\Warehouses\Application\Contracts\WarehouseLookup;

class StockWorkspace
{
    public function __construct(private StockRepository $stock, private ItemStockLookup $items, private WarehouseLookup $warehouses, private UnitLookup $units) {}

    public function records(array $filters, int $page): array
    {
        return $this->stock->paginate($filters['tab'], $page, $filters);
    }

    public function options(): array
    {
        return ['warehouses' => $this->warehouses->choices(), 'units' => $this->units->listActive(),
            'items' => array_map(fn ($item) => ['id' => $item->id, 'sku' => $item->sku, 'name' => $item->name,
                'base_unit_id' => $item->baseUnitId,
                'units' => array_map(fn ($unit) => ['unit_id' => $unit->unitId, 'conversion_ratio' => $unit->conversionRatio], $item->units)], $this->items->choices())];
    }
}
