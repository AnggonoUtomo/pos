<?php

namespace Tests\Feature\Modules\Purchasing\PurchaseOrders;

use App\Modules\Inventory\Items\Database\Seeders\ItemsDemoSeeder;
use App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder;
use App\Modules\Inventory\Warehouses\Database\Seeders\WarehousesDemoSeeder;
use App\Modules\Purchasing\PurchaseOrders\Database\Seeders\PurchaseOrdersDemoSeeder;
use App\Modules\Purchasing\PurchaseOrders\Database\Seeders\PurchaseOrdersReceiptDemoSeeder;
use App\Modules\Purchasing\Suppliers\Database\Seeders\SuppliersDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

class PurchaseReceiptDemoTest extends TestCase
{
    use RefreshDatabase;

    private function masters(): void
    {
        $this->seed([UnitsDemoSeeder::class, WarehousesDemoSeeder::class, ItemsDemoSeeder::class, SuppliersDemoSeeder::class, PurchaseOrdersDemoSeeder::class]);
    }

    public function test_explicit_receipt_demo_is_relational_idempotent_without_finance_or_accounts(): void
    {
        $this->masters();
        $this->seed(PurchaseOrdersReceiptDemoSeeder::class);
        $this->assertDatabaseHas('purchase_orders', ['status' => 'posted', 'version' => 2]);
        $this->assertDatabaseCount('stock_receipt_lines', 2);
        $this->assertDatabaseCount('stock_balances', 2);
        $before = [DB::table('purchase_orders')->get()->toJson(), DB::table('fifo_layers')->get()->toJson(), DB::table('activity_log')->count()];
        $this->seed(PurchaseOrdersReceiptDemoSeeder::class);
        $this->assertSame($before, [DB::table('purchase_orders')->get()->toJson(), DB::table('fifo_layers')->get()->toJson(), DB::table('activity_log')->count()]);
        foreach (['users', 'model_has_roles', 'model_has_permissions', 'payment_receipts', 'sales_invoices'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
    }

    public function test_edited_or_deleted_demo_is_never_posted_automatically(): void
    {
        $this->masters();
        DB::table('purchase_orders')->update(['notes' => 'User edited', 'version' => 2]);
        foreach ([false, true] as $trashed) {
            if ($trashed) {
                DB::table('purchase_orders')->update(['deleted_at' => now()]);
            }
            $before = DB::table('purchase_orders')->get()->toJson();
            $this->seed(PurchaseOrdersReceiptDemoSeeder::class);
            $this->assertSame($before, DB::table('purchase_orders')->get()->toJson());
            $this->assertDatabaseCount('stock_receipts', 0);
        }
    }

    public function test_demo_rejected_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        $this->expectException(LogicException::class);
        app(PurchaseOrdersReceiptDemoSeeder::class)->run();
    }
}
