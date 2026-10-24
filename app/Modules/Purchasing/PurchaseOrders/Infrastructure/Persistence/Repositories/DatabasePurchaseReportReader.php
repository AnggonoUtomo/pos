<?php

namespace App\Modules\Purchasing\PurchaseOrders\Infrastructure\Persistence\Repositories;

use App\Modules\Purchasing\PurchaseOrders\Application\Contracts\PurchaseReportReader;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;

class DatabasePurchaseReportReader implements PurchaseReportReader
{
    public function summary(string $from, string $until, ?string $warehouseId): array
    {
        $query = DB::table('purchase_orders')->where('posted_at', '>=', $from)->where('posted_at', '<', $until)
            ->when($warehouseId, fn ($q, $id) => $q->where('warehouse_id', $id))->where('status', 'posted');
        $totals = array_fill_keys(['total_value'], BigDecimal::zero()->toScale(6));
        $count = 0;
        foreach ($query->orderBy('id')->cursor() as $row) {
            $count++;
            foreach ($totals as $field => $value) {
                $totals[$field] = $value->plus((string) $row->$field);
            }
        }

        return ['count' => $count] + array_map(fn ($value) => (string) $value, $totals);
    }
}
