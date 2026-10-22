<?php

namespace Tests\Feature\Modules\Purchasing\Returns;

use App\Modules\Inventory\Items\Database\Seeders\ItemsDemoSeeder;
use App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder;
use App\Modules\Inventory\Warehouses\Database\Seeders\WarehousesDemoSeeder;
use App\Modules\Purchasing\PurchaseOrders\Database\Seeders\PurchaseOrdersDemoSeeder;
use App\Modules\Purchasing\PurchaseOrders\Database\Seeders\PurchaseOrdersReceiptDemoSeeder;
use App\Modules\Purchasing\Returns\Database\Seeders\ReturnsAccessSeeder;
use App\Modules\Purchasing\Returns\Database\Seeders\ReturnsDemoSeeder;
use App\Modules\Purchasing\Suppliers\Database\Seeders\SuppliersDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

class ReturnsDemoTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_skips_without_eligible_source_and_never_creates_purchase_or_receipt(): void
    {
        $before = $this->fingerprint();

        app(ReturnsDemoSeeder::class)->run();

        $this->assertSame($before, $this->fingerprint());
        $this->assertDatabaseCount('purchase_returns', 0);
        $this->assertDatabaseCount('purchase_orders', 0);
        $this->assertDatabaseCount('stock_receipts', 0);
        $this->assertDatabaseCount('payment_disbursements', 0);
    }

    public function test_demo_creates_one_relational_cctv_draft_idempotently_without_changing_sources_or_ledgers(): void
    {
        $this->seed([UnitsDemoSeeder::class, WarehousesDemoSeeder::class, ItemsDemoSeeder::class,
            SuppliersDemoSeeder::class, PurchaseOrdersDemoSeeder::class, PurchaseOrdersReceiptDemoSeeder::class]);
        $before = $this->fingerprint();
        app(ReturnsDemoSeeder::class)->run();
        $after = $this->fingerprint();
        app(ReturnsDemoSeeder::class)->run();

        $draft = DB::table('purchase_returns')->first();
        $lines = DB::table('purchase_return_lines')->where('purchase_return_id', $draft->id)->get();
        $this->assertSame($before, $after);
        $this->assertSame($after, $this->fingerprint());
        $this->assertSame('draft', $draft->status);
        $this->assertSame(1, (int) $draft->version);
        $this->assertSame('cctv-electrical-return-v1', $draft->demo_key);
        $this->assertDatabaseHas('purchase_orders', ['id' => $draft->purchase_order_id, 'status' => 'posted']);
        $this->assertCount(2, $lines);
        $this->assertEqualsCanonicalizing(['DEMO-CCTV-IP-2MP', 'DEMO-ELEC-NYM-3X15'], $lines->map(fn ($line) => json_decode($line->snapshot, true, flags: JSON_THROW_ON_ERROR)['sku'])->all());
        foreach ($lines as $line) {
            $this->assertDatabaseHas('purchase_order_lines', ['id' => $line->purchase_order_line_id, 'purchase_order_id' => $draft->purchase_order_id]);
        }
        $this->assertDatabaseCount('purchase_returns', 1);
        $this->assertDatabaseCount('stock_receipts', 1);
        $this->assertDatabaseCount('payment_disbursements', 0);
        $this->assertDatabaseCount('permissions', 0);
        $this->assertDatabaseCount('model_has_permissions', 0);
        $this->assertDatabaseCount('model_has_roles', 0);
    }

    public function test_demo_does_not_overwrite_user_edited_or_trashed_marker_and_catalog_does_not_grant(): void
    {
        $this->seed([UnitsDemoSeeder::class, WarehousesDemoSeeder::class, ItemsDemoSeeder::class,
            SuppliersDemoSeeder::class, PurchaseOrdersDemoSeeder::class, PurchaseOrdersReceiptDemoSeeder::class]);
        app(ReturnsDemoSeeder::class)->run();
        $id = DB::table('purchase_returns')->value('id');
        DB::table('purchase_returns')->where('id', $id)->update(['notes' => 'Catatan buatan user', 'version' => 7, 'deleted_at' => now()]);
        $before = DB::table('purchase_returns')->where('id', $id)->first();

        app(ReturnsDemoSeeder::class)->run();
        app(ReturnsAccessSeeder::class)->run();

        $after = DB::table('purchase_returns')->where('id', $id)->first();
        $this->assertEquals($before, $after);
        $this->assertDatabaseCount('purchase_returns', 1);
        $this->assertDatabaseCount('permissions', 3);
        $this->assertDatabaseCount('model_has_permissions', 0);
        $this->assertDatabaseCount('model_has_roles', 0);
    }

    public function test_demo_rejected_outside_local_and_testing(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        $this->expectException(LogicException::class);

        app(ReturnsDemoSeeder::class)->run();
    }

    private function fingerprint(): string
    {
        $tables = ['purchase_orders', 'purchase_order_lines', 'stock_receipts', 'stock_receipt_lines', 'stock_balances',
            'stock_movements', 'fifo_layers', 'fifo_consumptions', 'payment_disbursements', 'payment_disbursement_lines',
            'payment_receipts', 'payment_records', 'payment_refunds', 'payment_refund_lines', 'payment_accounts', 'payment_methods'];
        $rows = array_map(fn (string $table) => DB::table($table)->orderBy('id')->get()->all(), $tables);
        $rows['source_activity'] = DB::table('activity_log')->where('log_name', '!=', 'purchasing.returns')->orderBy('id')->get()->all();

        return hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR));
    }
}
