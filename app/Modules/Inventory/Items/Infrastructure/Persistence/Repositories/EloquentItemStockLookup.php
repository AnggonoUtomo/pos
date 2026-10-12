<?php

namespace App\Modules\Inventory\Items\Infrastructure\Persistence\Repositories;

use App\Modules\Inventory\Items\Application\Contracts\ItemStockLookup;
use App\Modules\Inventory\Items\Application\DTOs\ItemStockData;
use App\Modules\Inventory\Items\Application\DTOs\ItemUnitData;
use App\Modules\Inventory\Items\Infrastructure\Persistence\Models\Item;
use Illuminate\Support\Facades\DB;
use LogicException;

class EloquentItemStockLookup implements ItemStockLookup
{
    public function findActive(string $id, bool $lock = false): ?ItemStockData
    {
        if ($lock && DB::transactionLevel() === 0) {
            throw new LogicException('Lookup lock memerlukan transaction.');
        }
        $item = Item::where('is_active', true)->whereKey($id)
            ->when($lock, fn ($query) => $query->lockForUpdate())->first();

        if ($item !== null) {
            // A current read avoids a stale REPEATABLE READ snapshot established by draft lines.
            $item->setRelation('units', $item->units()->when($lock, fn ($query) => $query->lockForUpdate())->get());
        }

        return $item ? $this->data($item) : null;
    }

    public function choices(): array
    {
        return Item::where('is_active', true)->with('units')->orderBy('sku')->orderBy('id')->limit(1000)
            ->get()->map(fn (Item $item) => $this->data($item))->all();
    }

    private function data(Item $item): ItemStockData
    {
        return new ItemStockData($item->id, $item->sku, $item->name, $item->base_unit_id,
            $item->units->map(fn ($unit) => new ItemUnitData($unit->unit_id, $unit->conversion_ratio, $unit->barcode, $unit->is_sale_unit, $unit->is_purchase_unit))->all());
    }
}
