<?php

namespace App\Modules\Sales\Returns\Infrastructure\Persistence\Repositories;

use App\Modules\Sales\Returns\Application\Contracts\SalesReturnReportReader;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;

class DatabaseSalesReturnReportReader implements SalesReturnReportReader
{
    public function summary(string $from, string $until, ?string $warehouseId): array
    {
        $count = DB::table('sales_returns')->where('status', 'posted')->where('posted_at', '>=', $from)->where('posted_at', '<', $until)
            ->when($warehouseId, fn ($q, $id) => $q->where('snapshot->warehouse_id', $id))->count();
        $query = DB::table('sales_return_settlements as s')->join('sales_returns as r', 'r.id', '=', 's.sales_return_id')
            ->where('s.settled_at', '>=', $from)->where('s.settled_at', '<', $until)
            ->when($warehouseId, fn ($q, $id) => $q->where('r.snapshot->warehouse_id', $id))->select('s.id', 's.total');
        $settled = BigDecimal::zero()->toScale(2);
        foreach ($query->orderBy('s.id')->cursor() as $row) {
            $settled = $settled->plus((string) $row->total);
        }

        return ['count' => $count, 'settled_total' => (string) $settled];
    }
}
