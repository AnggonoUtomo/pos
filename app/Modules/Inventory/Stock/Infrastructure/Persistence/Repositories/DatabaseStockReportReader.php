<?php

namespace App\Modules\Inventory\Stock\Infrastructure\Persistence\Repositories;

use App\Modules\Inventory\Stock\Application\Contracts\StockReportReader;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;

class DatabaseStockReportReader implements StockReportReader
{
    public function summary(?string $warehouseId): array
    {
        $query = DB::table('stock_balances as b')->leftJoin('stock_opening_lines as l', 'l.id', '=', 'b.opening_line_id')
            ->leftJoin('stock_openings as o', 'o.id', '=', 'l.opening_id')->when($warehouseId, fn ($q, $id) => $q->where('b.warehouse_id', $id))
            ->select('b.id', 'b.quantity', 'b.total_value')->selectRaw('COALESCE(b.sku,l.sku) as sku, COALESCE(b.item_name,l.item_name) as item_name, COALESCE(b.base_unit_name,l.base_unit_name) as unit, COALESCE(b.warehouse_name,o.warehouse_name) as warehouse');
        $count = 0;
        $rows = [];
        $total = BigDecimal::zero()->toScale(6);
        foreach ($query->orderBy('b.item_id')->orderBy('b.warehouse_id')->cursor() as $row) {
            $count++;
            $total = $total->plus((string) $row->total_value);
            if (count($rows) < 100) {
                $rows[] = ['sku' => $row->sku, 'name' => $row->item_name, 'unit' => $row->unit, 'warehouse' => $row->warehouse,
                    'quantity' => (string) BigDecimal::of((string) $row->quantity)->toScale(6),
                    'total_value' => (string) BigDecimal::of((string) $row->total_value)->toScale(6)];
            }
        }

        return ['count' => $count, 'total_value' => (string) $total, 'rows' => $rows, 'truncated' => $count > count($rows)];
    }
}
