<?php

namespace App\Modules\Purchasing\PurchaseOrders\Infrastructure\Persistence\Repositories;

use App\Modules\Purchasing\PurchaseOrders\Application\Contracts\ReturnPurchaseLookup;
use App\Modules\Purchasing\PurchaseOrders\Application\DTOs\ReturnPurchaseData;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class DatabaseReturnPurchaseLookup implements ReturnPurchaseLookup
{
    public function findEligible(string $id): ?ReturnPurchaseData
    {
        $header = $this->eligibleQuery()->where('purchase_orders.id', strtolower($id))->first();

        return $header === null ? null : $this->data($header, $this->lines([$header->id])[$header->id] ?? []);
    }

    public function searchEligible(string $search): array
    {
        $search = trim($search);
        if (mb_strlen($search) > 150) {
            throw new InvalidArgumentException('Pencarian pembelian maksimal 150 karakter.');
        }
        $query = $this->eligibleQuery();
        if ($search !== '') {
            $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search).'%';
            $query->where(fn ($builder) => $builder->whereRaw("purchase_orders.number LIKE ? ESCAPE '!'", [$like])
                ->orWhereRaw("purchase_orders.supplier_code LIKE ? ESCAPE '!'", [$like])
                ->orWhereRaw("purchase_orders.supplier_name LIKE ? ESCAPE '!'", [$like])
                ->orWhereRaw("purchase_orders.warehouse_name LIKE ? ESCAPE '!'", [$like]));
        }
        $headers = $query->orderByDesc('purchase_orders.posted_at')->orderByDesc('purchase_orders.id')->limit(25)->get();
        $lines = $this->lines($headers->pluck('id')->all());

        return $headers->map(fn ($header) => $this->data($header, $lines[$header->id] ?? []))->all();
    }

    private function eligibleQuery()
    {
        return DB::table('purchase_orders')
            ->join('stock_receipts', function ($join): void {
                $join->on('stock_receipts.id', '=', 'purchase_orders.stock_receipt_id')
                    ->on('stock_receipts.source_id', '=', 'purchase_orders.id')
                    ->on('stock_receipts.source_number', '=', 'purchase_orders.number')
                    ->on('stock_receipts.warehouse_id', '=', 'purchase_orders.warehouse_id')
                    ->on('stock_receipts.posted_at', '=', 'purchase_orders.posted_at')
                    ->where('stock_receipts.source_type', '=', 'purchase');
            })
            ->where('purchase_orders.status', 'posted')
            ->whereNull('purchase_orders.deleted_at')
            ->whereNotNull('purchase_orders.stock_receipt_id')
            ->whereNotNull('purchase_orders.posted_at')
            ->whereExists(fn ($query) => $query->selectRaw('1')->from('purchase_order_lines')
                ->whereColumn('purchase_order_lines.purchase_order_id', 'purchase_orders.id'))
            ->whereRaw('(SELECT COUNT(*) FROM purchase_order_lines WHERE purchase_order_id = purchase_orders.id) = (SELECT COUNT(*) FROM stock_receipt_lines WHERE receipt_id = stock_receipts.id)')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')->from('purchase_order_lines as source_line')
                    ->whereColumn('source_line.purchase_order_id', 'purchase_orders.id')
                    ->whereNotExists(fn ($receiptLine) => $receiptLine->selectRaw('1')->from('stock_receipt_lines as receipt_line')
                        ->whereColumn('receipt_line.receipt_id', 'stock_receipts.id')
                        ->whereColumn('receipt_line.source_line_id', 'source_line.id')
                        ->whereColumn('receipt_line.item_id', 'source_line.item_id')
                        ->whereColumn('receipt_line.base_unit_id', 'source_line.base_unit_id')
                        ->whereColumn('receipt_line.quantity', 'source_line.base_quantity')
                        ->whereColumn('receipt_line.total_value', 'source_line.total_value'));
            })
            ->select(['purchase_orders.id', 'purchase_orders.number', 'purchase_orders.supplier_id', 'purchase_orders.supplier_code',
                'purchase_orders.supplier_name', 'purchase_orders.warehouse_id', 'purchase_orders.warehouse_code',
                'purchase_orders.warehouse_name', 'purchase_orders.posted_at']);
    }

    private function lines(array $purchaseIds): array
    {
        if ($purchaseIds === []) {
            return [];
        }

        return DB::table('purchase_order_lines')->whereIn('purchase_order_id', $purchaseIds)->orderBy('purchase_order_id')->orderBy('item_id')
            ->get(['purchase_order_id', 'id', 'item_id', 'unit_id', 'base_unit_id', 'sku', 'item_name', 'unit_name', 'base_unit_name',
                'quantity', 'base_quantity', 'conversion_ratio', 'unit_cost', 'total_value'])
            ->map(function ($row): array {
                $line = (array) $row;
                $purchaseId = $line['purchase_order_id'];
                unset($line['purchase_order_id']);
                foreach (['quantity', 'base_quantity', 'conversion_ratio', 'unit_cost', 'total_value'] as $field) {
                    $line[$field] = (string) BigDecimal::of((string) $line[$field])->toScale(6);
                }

                return ['purchase_order_id' => $purchaseId, 'line' => $line];
            })->groupBy('purchase_order_id')->map(fn ($rows) => $rows->pluck('line')->all())
            ->all();
    }

    private function data(object $header, array $lines): ReturnPurchaseData
    {
        return new ReturnPurchaseData([
            'id' => $header->id, 'number' => $header->number, 'supplier_id' => $header->supplier_id,
            'supplier_code' => $header->supplier_code, 'supplier_name' => $header->supplier_name,
            'warehouse_id' => $header->warehouse_id, 'warehouse_code' => $header->warehouse_code,
            'warehouse_name' => $header->warehouse_name, 'posted_at' => (string) $header->posted_at,
        ], array_values($lines));
    }
}
