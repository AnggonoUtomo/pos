<?php

namespace App\Modules\Inventory\Stock\Infrastructure\Persistence\Repositories;

use App\Modules\Inventory\Stock\Application\Contracts\StockAvailability;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;

class DatabaseStockAvailability implements StockAvailability
{
    public function forItems(string $warehouseId, array $itemIds, bool $lock = false): array
    {
        if ($lock && DB::transactionLevel() === 0) {
            throw new \LogicException('Saldo lock memerlukan transaction default.');
        }
        if (count($itemIds) > 100) {
            throw new \InvalidArgumentException('Batch saldo melebihi batas.');
        }
        $balances = DB::table('stock_balances')->where('warehouse_id', $warehouseId)->whereIn('item_id', $itemIds)->orderBy('item_id')
            ->when($lock, fn ($q) => $q->lockForUpdate())->pluck('quantity', 'item_id');
        $result = [];
        foreach ($itemIds as $id) {
            $result[$id] = (string) BigDecimal::of($balances[$id] ?? '0')->toScale(6);
        }

        return $result;
    }
}
