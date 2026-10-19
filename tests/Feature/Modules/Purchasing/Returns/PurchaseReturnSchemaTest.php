<?php

namespace Tests\Feature\Modules\Purchasing\Returns;

use App\Modules\Purchasing\PurchaseOrders\Application\Actions\ManagePurchaseOrder;
use App\Modules\Purchasing\PurchaseOrders\Application\Actions\PostPurchaseOrder;
use App\Modules\Purchasing\Returns\Database\Seeders\ReturnsDemoSeeder;
use App\Modules\Purchasing\Returns\ServiceProvider;
use App\Modules\Purchasing\Suppliers\Infrastructure\Persistence\Models\Supplier;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use LogicException;
use Tests\Feature\Modules\Inventory\Stock\StockTestCase;

class PurchaseReturnSchemaTest extends StockTestCase
{
    private const MIGRATION = 'Modules/Purchasing/Returns/Database/Migrations/2026_10_19_000003_create_purchase_return_drafts.php';

    public function test_provider_discovers_schema_without_business_endpoints_or_demo_mutation(): void
    {
        $this->assertTrue(app()->getLoadedProviders()[ServiceProvider::class] ?? false);
        foreach (['purchase_returns', 'purchase_return_lines'] as $table) {
            $this->assertTrue(Schema::hasTable($table));
            $this->assertTrue(Schema::hasColumn($table, 'snapshot'));
            $this->assertSame($table === 'purchase_returns', Schema::hasColumn($table, 'deleted_at'));
        }
        $this->assertFalse(Schema::hasColumn('purchase_returns', 'finance_refund_id'));
        $this->assertFalse(Schema::hasColumn('purchase_return_lines', 'total_value'));
        $this->getJson('/purchasing/returns')->assertMethodNotAllowed();
        $this->assertCount(7, collect(app('router')->getRoutes())->filter(fn ($route) => str_starts_with($route->uri(), 'purchasing/returns')));
        $before = $this->fingerprint();
        app(ReturnsDemoSeeder::class)->run();
        $this->assertSame($before, $this->fingerprint());
        $this->assertDatabaseCount('purchase_returns', 0);
        $this->assertDatabaseMissing('permissions', ['name' => 'purchasing.returns.view']);
    }

    public function test_defaults_exact_decimal_json_and_multiple_drafts_do_not_consume_source(): void
    {
        [$source, $line] = $this->source();
        $before = $this->fingerprint();
        $one = $this->header($source);
        $this->line($one, $line);
        $two = $this->header($source);
        $this->line($two, $line);
        $row = DB::table('purchase_returns')->where('id', $one)->first();
        $this->assertSame('draft', $row->status);
        $this->assertSame(1, (int) $row->version);
        $this->assertNull($row->deleted_at);
        $this->assertSame('0.123456', DB::table('purchase_return_lines')->where('purchase_return_id', $one)->value('quantity'));
        $this->assertSame('1.481472', DB::table('purchase_return_lines')->where('purchase_return_id', $one)->value('base_quantity'));
        $this->assertSame('120000.000000', json_decode(DB::table('purchase_return_lines')->where('purchase_return_id', $one)->value('snapshot'), true)['unit_cost']);
        $this->assertDatabaseCount('purchase_returns', 2);
        $this->assertSame($before, $this->fingerprint());
    }

    public function test_unique_and_restrict_constraints_keep_source_and_snapshot_references(): void
    {
        [$source, $line] = $this->source();
        $id = $this->header($source);
        $this->line($id, $line);
        foreach ([
            fn () => $this->line($id, $line),
            fn () => $this->header($source, 'PR-'.strtoupper($id)),
            fn () => DB::table('purchase_order_lines')->where('id', $line)->delete(),
            fn () => DB::table('purchase_orders')->where('id', $source)->delete(),
            fn () => DB::table('purchase_returns')->where('id', $id)->delete(),
            fn () => $this->line($id, strtolower((string) Str::ulid())),
        ] as $mutation) {
            try {
                DB::transaction($mutation);
                $this->fail('Unique/FK harus menolak mutation.');
            } catch (QueryException) {
                $this->assertDatabaseCount('purchase_returns', 1);
                $this->assertDatabaseCount('purchase_return_lines', 1);
            }
        }
    }

    public function test_demo_marker_stays_unique_after_soft_delete(): void
    {
        [$source] = $this->source();
        $id = $this->header($source);
        DB::table('purchase_returns')->where('id', $id)->update(['demo_key' => 'qa-cctv-return', 'deleted_at' => now()]);
        $other = $this->header($source);
        $this->expectException(QueryException::class);
        DB::table('purchase_returns')->where('id', $other)->update(['demo_key' => 'qa-cctv-return']);
    }

    public function test_empty_migration_down_up_keeps_source_tables(): void
    {
        $migration = require app_path(self::MIGRATION);
        $before = $this->fingerprint();
        $migration->down();
        $this->assertFalse(Schema::hasTable('purchase_returns'));
        $this->assertTrue(Schema::hasTable('purchase_orders'));
        $migration->up();
        $this->assertTrue(Schema::hasTable('purchase_return_lines'));
        $this->assertSame($before, $this->fingerprint());
    }

    public function test_populated_or_soft_deleted_draft_blocks_destructive_down(): void
    {
        [$source] = $this->source();
        $id = $this->header($source);
        $migration = require app_path(self::MIGRATION);
        foreach ([null, now()] as $deletedAt) {
            DB::table('purchase_returns')->where('id', $id)->update(['deleted_at' => $deletedAt]);
            try {
                $migration->down();
                $this->fail('Down populated harus ditolak.');
            } catch (LogicException) {
                $this->assertTrue(Schema::hasTable('purchase_return_lines'));
                $this->assertDatabaseCount('purchase_returns', 1);
            }
        }
    }

    private function source(): array
    {
        $supplier = Supplier::create(['code' => 'RET-QA', 'name' => 'Supplier CCTV QA', 'is_active' => true]);
        $id = app(ManagePurchaseOrder::class)->save($this->payload() + ['supplier_id' => $supplier->id]);
        app(PostPurchaseOrder::class)->handle($id, 1, $this->manager->id);

        return [$id, DB::table('purchase_order_lines')->where('purchase_order_id', $id)->value('id')];
    }

    private function header(string $source, ?string $number = null): string
    {
        $id = strtolower((string) Str::ulid());
        DB::table('purchase_returns')->insert(['id' => $id, 'number' => $number ?? 'PR-'.strtoupper($id), 'purchase_order_id' => $source,
            'purchase_order_number' => DB::table('purchase_orders')->where('id', $source)->value('number'),
            'supplier_name' => 'Supplier CCTV QA', 'snapshot' => json_encode(['id' => $source]), 'created_at' => now(), 'updated_at' => now()]);

        return $id;
    }

    private function line(string $returnId, string $sourceLine): void
    {
        DB::table('purchase_return_lines')->insert(['id' => strtolower((string) Str::ulid()), 'purchase_return_id' => $returnId,
            'purchase_order_line_id' => $sourceLine, 'quantity' => '0.123456', 'base_quantity' => '1.481472',
            'snapshot' => json_encode(['id' => $sourceLine, 'unit_cost' => '120000.000000'])]);
    }

    private function fingerprint(): string
    {
        return hash('sha256', json_encode(array_map(fn ($table) => DB::table($table)->orderBy('id')->get()->all(),
            ['purchase_orders', 'purchase_order_lines', 'stock_receipts', 'stock_receipt_lines', 'stock_balances', 'stock_movements',
                'fifo_layers', 'fifo_consumptions', 'payment_receipts', 'payment_records', 'payment_disbursements', 'payment_disbursement_lines',
                'payment_refunds', 'payment_refund_lines', 'activity_log', 'permissions']), JSON_THROW_ON_ERROR));
    }
}
