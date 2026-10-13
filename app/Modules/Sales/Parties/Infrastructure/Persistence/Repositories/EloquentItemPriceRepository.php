<?php

namespace App\Modules\Sales\Parties\Infrastructure\Persistence\Repositories;

use App\Modules\Sales\Parties\Application\Contracts\ItemPriceRepository;
use App\Modules\Sales\Parties\Application\DTOs\ItemPriceData;
use App\Modules\Sales\Parties\Application\DTOs\PriceLevelData;
use App\Modules\Sales\Parties\Infrastructure\Persistence\Models\CustomerLevel;
use App\Modules\Sales\Parties\Infrastructure\Persistence\Models\ItemPrice;

class EloquentItemPriceRepository implements ItemPriceRepository
{
    public function activeLevels(bool $lock): array
    {
        return CustomerLevel::where('is_active', true)->orderBy('id')->when($lock, fn ($q) => $q->lockForUpdate())
            ->limit(1001)->get(['id', 'code', 'name'])->map(fn ($row) => new PriceLevelData($row->id, $row->code, $row->name))->all();
    }

    public function replace(string $itemId, array $prices): void
    {
        $existing = ItemPrice::withTrashed()->where('item_id', $itemId)->orderBy('id')->lockForUpdate()->get()->keyBy(fn ($row) => $row->unit_id.':'.$row->customer_level_id);
        $keys = [];
        foreach ($prices as $data) {
            $key = $data->unitId.':'.$data->customerLevelId;
            $keys[$key] = true;
            $row = $existing[$key] ?? new ItemPrice;
            $row->fill(['item_id' => $itemId, 'unit_id' => $data->unitId, 'customer_level_id' => $data->customerLevelId, 'amount' => $data->amount]);
            if ($row->trashed()) {
                $row->restore();
            } else {
                $row->save();
            }
        }
        foreach ($existing as $key => $row) {
            if (! isset($keys[$key]) && ! $row->trashed()) {
                $row->delete();
            }
        }
    }

    public function forItems(array $itemIds): array
    {
        $result = [];
        foreach (ItemPrice::whereIn('item_id', $itemIds)->orderBy('unit_id')->orderBy('customer_level_id')->get() as $row) {
            $result[$row->item_id][] = new ItemPriceData($row->unit_id, $row->customer_level_id, $row->amount);
        }

        return $result;
    }
}
