<?php

namespace Tests\Feature\Modules\Inventory\Stock;

use App\Modules\Inventory\Stock\Application\Actions\IssueStock;
use App\Modules\Inventory\Stock\Application\Contracts\PurchaseStockWriter;
use App\Modules\Inventory\Stock\Application\Contracts\StockRepository;
use App\Modules\Inventory\Stock\Application\DTOs\PurchaseStockRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;

class PurchaseStockReceiptTest extends StockTestCase
{
    private function request(string $quantity = '24', string $value = '240000', ?string $source = null, ?string $line = null): PurchaseStockRequest
    {
        $source ??= strtolower((string) Str::ulid());

        return new PurchaseStockRequest($this->warehouse, $source, 'PO-'.strtoupper($source), [
            ['source_line_id' => $line ?? strtolower((string) Str::ulid()), 'item_id' => $this->item, 'base_unit_id' => $this->base, 'base_quantity' => $quantity, 'total_value' => $value],
        ], $this->manager->id);
    }

    private function receive(PurchaseStockRequest $request): mixed
    {
        return DB::transaction(fn () => app(PurchaseStockWriter::class)->receive($request));
    }

    public function test_receipt_without_opening_has_visible_balance_movement_layer_and_actor(): void
    {
        $request = $this->request();
        $result = $this->receive($request);
        $this->assertDatabaseHas('stock_balances', ['opening_line_id' => null, 'quantity' => '24.000000', 'total_value' => '240000.000000']);
        $this->assertDatabaseHas('stock_movements', ['opening_line_id' => null, 'type' => 'purchase', 'quantity' => '24.000000', 'source_number' => $request->sourceNumber]);
        $this->assertDatabaseHas('fifo_layers', ['original_quantity' => '24.000000', 'remaining_value' => '240000.000000']);
        $this->assertDatabaseHas('stock_receipts', ['id' => $result->id, 'source_type' => 'purchase', 'posted_by' => $this->manager->id]);
        $this->assertSame($this->manager->id, Activity::where('event', 'received')->sole()->causer_id);
        $balances = app(StockRepository::class)->paginate('balances', 1, []);
        $this->assertSame(1, $balances['meta']['total']);
        $this->assertSame('Barang QA', $balances['data'][0]['item_name']);
        $this->assertSame('24.000000', $balances['data'][0]['quantity']);
        $this->assertDatabaseCount('stock_openings', 0);
    }

    public function test_canonical_replay_is_noop_but_changed_source_or_reused_line_rejected(): void
    {
        $request = $this->request();
        $first = $this->receive($request);
        $same = $this->request('24.000000', '240000.0', strtoupper($request->sourceId), strtoupper($request->lines[0]['source_line_id']));
        $this->assertEquals($first, $this->receive($same));
        $this->assertDatabaseCount('stock_receipts', 1);
        $this->assertDatabaseCount('stock_movements', 1);
        foreach ([$this->request('25', '240000', $request->sourceId, $request->lines[0]['source_line_id']),
            $this->request(line: $request->lines[0]['source_line_id'])] as $invalid) {
            try {
                $this->receive($invalid);
                $this->fail('Source conflict harus ditolak.');
            } catch (InvalidArgumentException) {
                $this->assertDatabaseCount('stock_receipts', 1);
            }
        }
    }

    public function test_receipt_adds_after_opening_and_sale_and_fifo_drains_old_layer_first(): void
    {
        $this->travelTo(now()->startOfSecond());
        $id = $this->draft();
        $this->postJson('/inventory/stock/openings/'.$id.'/post')->assertOk();
        $this->travel(1)->seconds();
        $this->receive($this->request('6', '120000'));
        $source = strtolower((string) Str::ulid());
        $result = app(IssueStock::class)->handle(['warehouse_id' => $this->warehouse, 'source_id' => $source, 'source_number' => 'POS-'.$source,
            'lines' => [['source_line_id' => strtolower((string) Str::ulid()), 'item_id' => $this->item, 'base_quantity' => '25']]], $this->manager->id);
        $this->assertSame('260000.000000', $result['lines'][0]['total_value']);
        $this->assertDatabaseHas('stock_balances', ['quantity' => '5.000000', 'total_value' => '100000.000000']);
        $this->receive($this->request('2', '50000'));
        $this->assertDatabaseHas('stock_balances', ['quantity' => '7.000000', 'total_value' => '150000.000000']);
        $this->assertSame(1, app(StockRepository::class)->paginate('balances', 1, [])['meta']['total']);
    }

    public function test_audit_veto_and_corrupt_balance_roll_back_everything(): void
    {
        Activity::creating(fn ($log) => $log->event === 'received' ? false : null);
        try {
            $this->receive($this->request());
            $this->fail('Audit veto harus rollback.');
        } catch (RuntimeException) {
            $this->assertDatabaseCount('stock_balances', 0);
            $this->assertDatabaseCount('stock_receipts', 0);
        } finally {
            Activity::flushEventListeners();
        }
        $this->receive($this->request());
        DB::table('stock_balances')->update(['quantity' => '25.000000']);
        try {
            $this->receive($this->request());
            $this->fail('Saldo corrupt harus ditolak.');
        } catch (InvalidArgumentException) {
            $this->assertDatabaseCount('stock_receipts', 1);
            $this->assertDatabaseCount('fifo_layers', 1);
        }
    }

    public function test_balance_overflow_and_posted_migration_rollback_are_rejected(): void
    {
        $this->receive($this->request('99999999999999.999999', '1'));
        try {
            $this->receive($this->request('0.000001', '1'));
            $this->fail('Overflow harus ditolak.');
        } catch (InvalidArgumentException) {
            $this->assertDatabaseCount('stock_receipts', 1);
        }
        $migration = require app_path('Modules/Inventory/Stock/Database/Migrations/2026_10_17_000003_create_stock_receipts.php');
        $this->expectException(LogicException::class);
        $migration->down();
    }

    public function test_writer_requires_transaction_caller(): void
    {
        // RefreshDatabase wraps tests; use a mock facade to exercise the explicit guard.
        $request = $this->request();
        $writer = app(PurchaseStockWriter::class);
        DB::shouldReceive('transactionLevel')->once()->andReturn(0);
        $this->expectException(LogicException::class);
        $writer->receive($request);
    }
}
