<?php

namespace App\Modules\Purchasing\Returns\Infrastructure\Persistence\Repositories;

use App\Modules\Purchasing\Returns\Application\Contracts\PurchaseReturnReportReader;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;

class DatabasePurchaseReturnReportReader implements PurchaseReturnReportReader
{
    public function summary(string $from, string $until, ?string $warehouseId): array
    {
        $count = DB::table('purchase_returns')->where('status', 'posted')->where('posted_at', '>=', $from)->where('posted_at', '<', $until)
            ->when($warehouseId, fn ($q, $id) => $q->where('snapshot->warehouse_id', $id))->count();
        $query = DB::table('purchase_return_settlements as s')->join('purchase_returns as r', 'r.id', '=', 's.purchase_return_id')
            ->where('s.settled_at', '>=', $from)->where('s.settled_at', '<', $until)
            ->when($warehouseId, fn ($q, $id) => $q->where('r.snapshot->warehouse_id', $id))->select('s.id', 's.total');
        $settled = BigDecimal::zero()->toScale(6);
        foreach ($query->orderBy('s.id')->cursor() as $row) {
            $settled = $settled->plus((string) $row->total);
        }

        return ['count' => $count, 'settled_total' => (string) $settled];
    }
}
