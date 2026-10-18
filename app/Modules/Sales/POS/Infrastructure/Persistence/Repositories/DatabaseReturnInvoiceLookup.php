<?php

namespace App\Modules\Sales\POS\Infrastructure\Persistence\Repositories;

use App\Modules\Sales\POS\Application\Contracts\ReturnInvoiceLookup;
use App\Modules\Sales\POS\Application\DTOs\ReturnInvoiceData;
use App\Modules\Sales\POS\Application\DTOs\ReturnRefundBasisData;
use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use JsonException;

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

    public function refundBasis(string $id): ?ReturnRefundBasisData
    {
        $row = DB::table('sales_invoices')->select('id', 'subtotal', 'discount_amount', 'tax_amount', 'total', 'payments')->where('id', strtolower($id))->first();
        if ($row === null) {
            return null;
        }
        try {
            $payment = json_decode($row->payments, true, flags: JSON_THROW_ON_ERROR);
            if (! is_array($payment) || ! is_string($payment['total'] ?? null)
                || ! preg_match('/^[0-9]{1,28}(?:\.[0-9]{1,2})?$/D', $payment['total'])) {
                throw new InvalidArgumentException('Snapshot pembayaran invoice asal tidak valid.');
            }
            $header = ['id' => $row->id];
            foreach (['subtotal', 'discount_amount', 'tax_amount', 'total'] as $field) {
                $header[$field] = (string) BigDecimal::of((string) $row->$field)->toScale(2);
            }
            $header['paid_total'] = (string) BigDecimal::of($payment['total'])->toScale(2);
            $lines = DB::table('sales_invoice_lines')->select('id', 'quantity', 'subtotal', 'discount_amount', 'tax_amount')
                ->where('invoice_id', $row->id)->orderBy('line_number')->get()->map(function ($line): array {
                    $result = (array) $line;
                    foreach (['quantity', 'subtotal', 'discount_amount', 'tax_amount'] as $field) {
                        $result[$field] = (string) BigDecimal::of((string) $result[$field])->toScale($field === 'quantity' ? 6 : 2);
                    }

                    return $result;
                })->all();

            return new ReturnRefundBasisData($header, $lines);
        } catch (JsonException|MathException) {
            throw new InvalidArgumentException('Snapshot nilai invoice asal tidak valid.');
        }
    }

    public function search(string $search): array
    {
        $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], trim($search)).'%';

        return DB::table('sales_invoices')->select(['id', 'number', 'customer_name'])->whereRaw("number LIKE ? ESCAPE '!'", [$like])
            ->orderByDesc('posted_at')->orderByDesc('id')->limit(25)->get()->map(fn ($row) => (array) $row)->all();
    }
}
