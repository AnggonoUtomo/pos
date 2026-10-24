<?php

namespace Tests\Fixtures;

use App\Modules\Inventory\Items\Application\Contracts\ItemStockLookup;
use App\Modules\Inventory\Units\Application\Contracts\UnitLookup;
use App\Modules\Inventory\Warehouses\Application\Contracts\WarehouseLookup;
use Illuminate\Support\Facades\DB;
use LogicException;

final class TransferDraftFixture
{
    public static function seed(): array
    {
        if (! app()->environment('testing')) {
            throw new LogicException('Fixture hanya testing disposable.');
        }
        PurchaseReturnRefundFixture::purchase();
        $warehouses = collect(app(WarehouseLookup::class)->choices());
        $items = collect(app(ItemStockLookup::class)->choices());
        $units = app(UnitLookup::class);

        return ['source_warehouse_id' => $warehouses->firstWhere('code', 'WH-MAIN')->id,
            'destination_warehouse_id' => $warehouses->firstWhere('code', 'WH-SECONDARY')->id,
            'notes' => 'Transfer QA', 'lines' => [
                ['item_id' => $items->firstWhere('sku', 'DEMO-CCTV-IP-2MP')->id, 'unit_id' => $units->findActiveByCode('BOX')->id, 'quantity' => '1'],
                ['item_id' => $items->firstWhere('sku', 'DEMO-ELEC-NYM-3X15')->id, 'unit_id' => $units->findActiveByCode('ROLL')->id, 'quantity' => '0.125'],
            ]];
    }

    public static function fingerprint(): string
    {
        $tables = ['items', 'item_units', 'units', 'warehouses', 'stock_balances', 'stock_movements', 'fifo_layers', 'fifo_consumptions',
            'purchase_orders', 'purchase_order_lines', 'stock_receipts', 'stock_receipt_lines', 'stock_issues', 'stock_issue_lines',
            'payment_receipts', 'payment_records', 'payment_disbursements', 'payment_disbursement_lines',
            'payment_refunds', 'payment_refund_lines', 'purchase_refund_receipts', 'purchase_refund_receipt_lines'];

        return hash('sha256', json_encode(array_map(fn ($table) => DB::table($table)->orderBy('id')->get()->all(), $tables), JSON_THROW_ON_ERROR));
    }
}
