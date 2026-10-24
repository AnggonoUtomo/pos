<?php

namespace App\Modules\Sales\POS\Application\DTOs;

final class InvoiceReceipt
{
    public static function fromRecord(array $record): array
    {
        $result = array_intersect_key($record, array_flip(['id', 'number', 'warehouse_id', 'warehouse_code', 'warehouse_name',
            'customer_id', 'customer_code', 'customer_name', 'customer_level_name', 'subtotal', 'discount_amount', 'tax_amount', 'total', 'posted_at', 'payments', 'pricing']));
        $result['lines'] = array_map(fn ($row) => array_intersect_key($row, array_flip(['id', 'line_number', 'item_id', 'unit_id',
            'base_unit_id', 'sku', 'name', 'unit_name', 'base_unit_name', 'quantity', 'base_quantity', 'conversion_ratio',
            'unit_price', 'subtotal', 'discount_amount', 'tax_amount'])), $record['lines']);

        return $result;
    }
}
