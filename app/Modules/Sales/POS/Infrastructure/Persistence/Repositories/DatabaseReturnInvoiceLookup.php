<?php

namespace App\Modules\Sales\POS\Infrastructure\Persistence\Repositories;

use App\Modules\Sales\POS\Application\Contracts\ReturnInvoiceLookup;
use App\Modules\Sales\POS\Application\DTOs\ReturnInvoiceData;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;

final class DatabaseReturnInvoiceLookup implements ReturnInvoiceLookup
{
    public function find(string $id): ?ReturnInvoiceData
    {
        $header = DB::table('sales_invoices')->select(['id', 'number', 'warehouse_id', 'warehouse_code', 'warehouse_name',
            'customer_id', 'customer_code', 'customer_name', 'customer_level_name', 'posted_at'])->where('id', strtolower($id))->first();
        if ($header === null) {
            return null;
        }
        $lines = DB::table('sales_invoice_lines')->select(['id', 'line_number', 'item_id', 'unit_id', 'base_unit_id', 'sku', 'name',
            'unit_name', 'base_unit_name', 'quantity', 'base_quantity', 'conversion_ratio', 'unit_price', 'subtotal', 'discount_amount', 'tax_amount'])
            ->where('invoice_id', $header->id)->orderBy('line_number')->get()->map(function ($row): array {
                $line = (array) $row;
                foreach (['quantity', 'base_quantity', 'conversion_ratio'] as $field) {
                    $line[$field] = (string) BigDecimal::of($line[$field])->toScale(6);
                }
                foreach (['unit_price', 'subtotal', 'discount_amount', 'tax_amount'] as $field) {
                    $line[$field] = (string) BigDecimal::of($line[$field])->toScale(2);
                }

                return $line;
            })->all();

        return new ReturnInvoiceData((array) $header, $lines);
    }

    public function search(string $search): array
    {
        $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], trim($search)).'%';

        return DB::table('sales_invoices')->select(['id', 'number', 'customer_name'])->whereRaw("number LIKE ? ESCAPE '!'", [$like])
            ->orderByDesc('posted_at')->orderByDesc('id')->limit(25)->get()->map(fn ($row) => (array) $row)->all();
    }
}
