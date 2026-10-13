<?php

namespace App\Modules\Inventory\Items\Infrastructure\Persistence\Repositories;

use App\Modules\Inventory\Items\Application\Contracts\ItemPricingLookup;
use App\Modules\Inventory\Items\Application\DTOs\ItemPricingData;
use App\Modules\Inventory\Items\Application\DTOs\ItemUnitData;
use App\Modules\Inventory\Items\Infrastructure\Persistence\Models\Item;
use App\Modules\Inventory\Items\Infrastructure\Persistence\Models\ItemUnit;
use App\Modules\Inventory\Units\Application\Contracts\UnitLookup;
use Illuminate\Support\Facades\DB;
use LogicException;

class EloquentItemPricingLookup implements ItemPricingLookup
{
    public function __construct(private UnitLookup $units) {}

    public function findIdBySku(string $sku): ?string
    {
        return Item::where('sku', $sku)->value('id');
    }

    public function lock(string $itemId): ?ItemPricingData
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Pricing lookup lock memerlukan transaction.');
        }
        $ids = ItemUnit::where('item_id', $itemId)->orderBy('unit_id')->pluck('unit_id')->all();
        foreach ($ids as $id) {
            if ($this->units->findActiveById($id, lock: true) === null) {
                return null;
            }
        }
        $item = Item::whereKey($itemId)->lockForUpdate()->first();
        if ($item === null) {
            return null;
        }
        $rows = $item->units()->orderBy('unit_id')->lockForUpdate()->get();
        if ($rows->pluck('unit_id')->all() !== $ids) {
            return null;
        }

        $sale = $rows->where('is_sale_unit', true)->values();

        return new ItemPricingData($item->id, $sale->pluck('unit_id')->all(),
            $sale->map(fn ($row) => new ItemUnitData($row->unit_id, $row->conversion_ratio))->all());
    }
}
