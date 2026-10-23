<?php

namespace Tests\Fixtures;

use App\Modules\Finance\Payments\Database\Seeders\PaymentsDemoSeeder;
use App\Modules\Inventory\Items\Database\Seeders\ItemsDemoSeeder;
use App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder;
use App\Modules\Inventory\Warehouses\Database\Seeders\WarehousesDemoSeeder;
use App\Modules\Purchasing\PurchaseOrders\Application\Actions\ManagePurchaseOrder;
use App\Modules\Purchasing\PurchaseOrders\Application\Actions\PayPurchaseOrder;
use App\Modules\Purchasing\PurchaseOrders\Application\Actions\PostPurchaseOrder;
use App\Modules\Purchasing\Returns\Application\Actions\ManagePurchaseReturnDraft;
use App\Modules\Purchasing\Returns\Application\Actions\PostPurchaseReturnDraft;
use App\Modules\Purchasing\Suppliers\Database\Seeders\SuppliersDemoSeeder;
use Illuminate\Support\Facades\DB;
use LogicException;

final class PurchaseReturnRefundFixture
{
    public static function purchase(string $quantity = '2', string $cost = '120000', bool $paid = true): array
    {
        if (! app()->environment('testing')) {
            throw new LogicException('Fixture hanya testing disposable.');
        }
        foreach ([UnitsDemoSeeder::class, WarehousesDemoSeeder::class, ItemsDemoSeeder::class, SuppliersDemoSeeder::class, PaymentsDemoSeeder::class] as $seeder) {
            app($seeder)->run();
        }
        $item = DB::table('items')->where('sku', 'DEMO-CCTV-IP-2MP')->sole();
        $id = app(ManagePurchaseOrder::class)->save([
            'supplier_id' => DB::table('suppliers')->where('code', 'DEMO-SUP-CCTV')->value('id'),
            'warehouse_id' => DB::table('warehouses')->where('code', 'WH-MAIN')->value('id'),
            'lines' => [['item_id' => $item->id, 'unit_id' => $item->base_unit_id, 'quantity' => $quantity, 'unit_cost' => $cost]],
        ]);
        app(PostPurchaseOrder::class)->handle($id, 1);
        $total = (string) DB::table('purchase_orders')->where('id', $id)->value('total_value');
        if ($paid) {
            app(PayPurchaseOrder::class)->handle($id, 2, [['method_id' => DB::table('payment_methods')->where('code', 'TUNAI')->value('id'), 'amount' => $total]]);
        }

        return ['id' => $id, 'line_id' => DB::table('purchase_order_lines')->where('purchase_order_id', $id)->value('id')];
    }

    public static function posted(array $purchase, string $quantity): string
    {
        $id = app(ManagePurchaseReturnDraft::class)->save(['purchase_order_id' => $purchase['id'],
            'lines' => [['purchase_order_line_id' => $purchase['line_id'], 'quantity' => $quantity]]]);
        app(PostPurchaseReturnDraft::class)->handle($id, 1);

        return $id;
    }

    public static function fingerprint(): string
    {
        $rows = [];
        foreach (['purchase_orders', 'purchase_order_lines', 'purchase_returns', 'purchase_return_lines', 'stock_receipts', 'stock_receipt_lines',
            'stock_balances', 'stock_movements', 'fifo_layers', 'fifo_consumptions', 'stock_issues', 'stock_issue_lines',
            'payment_receipts', 'payment_records', 'payment_disbursements', 'payment_disbursement_lines', 'payment_refunds', 'payment_refund_lines'] as $table) {
            $rows[$table] = DB::table($table)->orderBy('id')->get()->all();
        }

        return hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR));
    }
}
