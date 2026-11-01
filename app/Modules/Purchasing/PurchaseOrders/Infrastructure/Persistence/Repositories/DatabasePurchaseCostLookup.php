<?php

namespace App\Modules\Purchasing\PurchaseOrders\Infrastructure\Persistence\Repositories;

use App\Modules\Purchasing\PurchaseOrders\Application\Contracts\PurchaseCostLookup;
use App\Modules\Purchasing\PurchaseOrders\Application\DTOs\PurchaseCostData;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class DatabasePurchaseCostLookup implements PurchaseCostLookup
{
    public function latestForItem(string $itemId): ?PurchaseCostData
    {
        $row = DB::table('purchase_order_lines as l')->join('purchase_orders as p', 'p.id', '=', 'l.purchase_order_id')
            ->join('stock_receipts as r', fn ($join) => $join->on('r.id', '=', 'p.stock_receipt_id')
                ->on('r.source_id', '=', 'p.id')->on('r.source_number', '=', 'p.number')
                ->on('r.warehouse_id', '=', 'p.warehouse_id')->on('r.posted_at', '=', 'p.posted_at')->where('r.source_type', 'purchase'))
            ->join('stock_receipt_lines as rl', fn ($join) => $join->on('rl.receipt_id', '=', 'r.id')
                ->on('rl.source_line_id', '=', 'l.id')->on('rl.item_id', '=', 'l.item_id')
                ->on('rl.base_unit_id', '=', 'l.base_unit_id')->on('rl.quantity', '=', 'l.base_quantity')->on('rl.total_value', '=', 'l.total_value'))
            ->where('l.item_id', strtolower($itemId))->where('p.status', 'posted')->whereNull('p.deleted_at')
            ->orderByDesc('p.posted_at')->orderByDesc('p.id')->orderByDesc('l.id')
            ->first(['p.id', 'p.number', 'p.posted_at', 'l.base_unit_id', 'l.unit_name', 'l.unit_cost', 'l.conversion_ratio']);

        return $row === null ? null : new PurchaseCostData($row->id, $row->number,
            Carbon::parse($row->posted_at, config('app.timezone'))->utc()->toISOString(),
            $row->base_unit_id, $row->unit_name, (string) $row->unit_cost, (string) $row->conversion_ratio);
    }
}
