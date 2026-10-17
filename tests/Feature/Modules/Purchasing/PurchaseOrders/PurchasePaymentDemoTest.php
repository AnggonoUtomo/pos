<?php

namespace Tests\Feature\Modules\Purchasing\PurchaseOrders;

use App\Modules\Finance\Payments\Database\Seeders\PaymentsDemoSeeder;
use App\Modules\Inventory\Items\Database\Seeders\ItemsDemoSeeder;
use App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder;
use App\Modules\Inventory\Warehouses\Database\Seeders\WarehousesDemoSeeder;
use App\Modules\Purchasing\PurchaseOrders\Database\Seeders\PurchaseOrdersDemoSeeder;
use App\Modules\Purchasing\PurchaseOrders\Database\Seeders\PurchaseOrdersPaymentDemoSeeder;
use App\Modules\Purchasing\Suppliers\Database\Seeders\SuppliersDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

class PurchasePaymentDemoTest extends TestCase
{
    use RefreshDatabase;

    private function masters(): void
    {
        $this->seed([UnitsDemoSeeder::class, WarehousesDemoSeeder::class, ItemsDemoSeeder::class, SuppliersDemoSeeder::class, PaymentsDemoSeeder::class]);
    }

    public function test_explicit_relational_demo_idempotent_and_no_accounts_or_sales_created(): void
    {
        $this->masters();
        $this->seed(PurchaseOrdersPaymentDemoSeeder::class);
        $this->assertDatabaseCount('payment_disbursements', 1);
        $this->assertDatabaseHas('purchase_orders', ['status' => 'posted', 'version' => 3]);
        $tables = ['purchase_orders', 'stock_balances', 'fifo_layers', 'payment_disbursements', 'payment_disbursement_lines', 'activity_log'];
        $state = fn () => array_map(fn ($t) => DB::table($t)->orderBy('id')->get()->toJson(), $tables);
        $before = $state();
        $this->seed(PurchaseOrdersPaymentDemoSeeder::class);
        $this->assertSame($before, $state());
        foreach (['users', 'payment_receipts', 'sales_invoices', 'model_has_roles', 'model_has_permissions'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
    }

    public function test_user_edited_or_trashed_demo_not_automatically_posted_or_paid(): void
    {
        $this->masters();
        $this->seed(PurchaseOrdersDemoSeeder::class);
        DB::table('purchase_orders')->update(['version' => 2, 'notes' => 'Edited']);
        foreach ([false, true] as $trashed) {
            if ($trashed) {
                DB::table('purchase_orders')->update(['deleted_at' => now()]);
            }
            $before = DB::table('purchase_orders')->get()->toJson();
            $this->seed(PurchaseOrdersPaymentDemoSeeder::class);
            $this->assertSame($before, DB::table('purchase_orders')->get()->toJson());
            $this->assertDatabaseCount('stock_receipts', 0);
            $this->assertDatabaseCount('payment_disbursements', 0);
        }
    }

    public function test_demo_rejects_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        $this->expectException(LogicException::class);
        app(PurchaseOrdersPaymentDemoSeeder::class)->run();
    }
}
