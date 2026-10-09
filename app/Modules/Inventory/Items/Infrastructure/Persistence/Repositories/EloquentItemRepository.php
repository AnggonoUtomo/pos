<?php

namespace App\Modules\Inventory\Items\Infrastructure\Persistence\Repositories;

use App\Modules\Inventory\Items\Application\Contracts\ItemRepository;
use App\Modules\Inventory\Items\Application\DTOs\CreateItemData;
use App\Modules\Inventory\Items\Infrastructure\Persistence\Models\Item;

class EloquentItemRepository implements ItemRepository
{
    public function findIdBySku(string $sku): ?string
    {
        return Item::withTrashed()->where('sku', $sku)->value('id');
    }

    public function create(CreateItemData $data): string
    {
        $item = Item::query()->create([
            'sku' => $data->sku,
            'name' => $data->name,
            'base_unit_id' => $data->baseUnitId,
            'is_active' => $data->isActive,
        ]);

        foreach ($data->units as $unit) {
            $item->units()->create([
                'unit_id' => $unit->unitId,
                'conversion_ratio' => $unit->conversionRatio,
                'barcode' => $unit->barcode,
                'is_sale_unit' => $unit->isSaleUnit,
                'is_purchase_unit' => $unit->isPurchaseUnit,
            ]);
        }

        return $item->id;
    }
}
