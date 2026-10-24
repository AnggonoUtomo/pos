<?php

namespace App\Modules\Sales\POS\Infrastructure\Persistence\Repositories;

use App\Modules\Sales\POS\Application\Contracts\SalesReportReader;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;

class DatabaseSalesReportReader implements SalesReportReader
{
    public function summary(string $from, string $until, ?string $warehouseId): array
    {
        $query = DB::table('sales_invoices')->where('posted_at', '>=', $from)->where('posted_at', '<', $until)
            ->when($warehouseId, fn ($q, $id) => $q->where('warehouse_id', $id));
        $totals = array_fill_keys(['subtotal', 'discount_amount', 'tax_amount', 'total', 'total_cost'], BigDecimal::zero()->toScale(2));
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
