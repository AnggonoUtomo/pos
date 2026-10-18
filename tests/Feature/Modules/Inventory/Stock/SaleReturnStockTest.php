<?php

namespace Tests\Feature\Modules\Inventory\Stock;

use App\Modules\Inventory\Stock\Application\Actions\IssueStock;
use App\Modules\Inventory\Stock\Application\Actions\ManageOpening;
use App\Modules\Inventory\Stock\Application\Actions\PostOpening;
use App\Modules\Inventory\Stock\Application\Contracts\PurchaseStockWriter;
use App\Modules\Inventory\Stock\Application\Contracts\SaleReturnStockWriter;
use App\Modules\Inventory\Stock\Application\DTOs\PurchaseStockRequest;
use App\Modules\Inventory\Stock\Application\DTOs\SaleReturnStockRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Spatie\Activitylog\Models\Activity;
use Tests\Fixtures\SalesReturnFixture;
use Tests\TestCase;

class SaleReturnStockTest extends TestCase
{
    use RefreshDatabase;

    private array $invoice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->invoice = SalesReturnFixture::seed();
    }

    private function request(string $quantity = '1'): SaleReturnStockRequest
    {
        return new SaleReturnStockRequest($this->invoice['warehouse_id'], strtolower((string) Str::ulid()), 'SR-QA', $this->invoice['id'],
            [['source_line_id' => strtolower((string) Str::ulid()), 'sale_line_id' => $this->invoice['lines'][0]['id'], 'base_quantity' => $quantity]]);
    }

    public function test_new_layer_receipt_and_trace_use_original_cost_and_replay_is_noop(): void
    {
        $source = SalesReturnFixture::sourceFingerprint();
        $layers = DB::table('fifo_layers')->orderBy('id')->get();
        $request = $this->request();
        $this->travel(1)->minutes();
        $writer = app(SaleReturnStockWriter::class);
        $result = DB::transaction(fn () => $writer->receive($request));
        $receipt = DB::table('stock_receipt_lines')->where('receipt_id', $result->id)->sole();
        $this->assertSame('500000.000000', $receipt->total_value);
        $this->assertSame('1.000000', $receipt->quantity);
        $this->assertDatabaseHas('stock_return_allocations', ['receipt_line_id' => $receipt->id, 'quantity' => '1.000000', 'total_value' => '500000.000000']);
        foreach ($layers as $old) {
            $this->assertEquals($old, DB::table('fifo_layers')->where('id', $old->id)->sole());
        }
        $after = SalesReturnFixture::fingerprint();
        $audit = Activity::count();
        $this->assertSame($result->id, DB::transaction(fn () => $writer->receive($request))->id);
        $this->assertSame($after, SalesReturnFixture::fingerprint());
        $this->assertSame($audit, Activity::count());
        $this->assertSame($source, SalesReturnFixture::sourceFingerprint());
        $this->assertDatabaseHas('stock_movements', ['type' => 'sale_return', 'receipt_line_id' => $receipt->id]);
    }

    public function test_two_returns_cannot_exceed_sold_quantity_and_changed_replay_is_rejected(): void
    {
        $writer = app(SaleReturnStockWriter::class);
        $first = $this->request('1.5');
        DB::transaction(fn () => $writer->receive($first));
        $before = SalesReturnFixture::fingerprint();
        foreach ([$this->request('0.500001'), new SaleReturnStockRequest($first->warehouseId, $first->sourceId, $first->sourceNumber, $first->invoiceId,
            [array_replace($first->lines[0], ['base_quantity' => '1'])])] as $request) {
            try {
                DB::transaction(fn () => $writer->receive($request));
                $this->fail('Invalid return harus ditolak.');
            } catch (InvalidArgumentException) {
                $this->assertSame($before, SalesReturnFixture::fingerprint());
            }
        }
        DB::transaction(fn () => $writer->receive($this->request('0.5')));
        $this->assertSame(2, DB::table('stock_return_allocations')->count());
    }

    public function test_stock_audit_veto_rolls_back_receipt_layer_balance_and_trace(): void
    {
        $before = SalesReturnFixture::fingerprint();
        Activity::saving(fn ($log) => $log->log_name === 'inventory.stock' && $log->event === 'received' ? false : null);
        try {
            DB::transaction(fn () => app(SaleReturnStockWriter::class)->receive($this->request()));
            $this->fail('Audit wajib berhasil.');
        } catch (\RuntimeException) {
            $this->assertSame($before, SalesReturnFixture::fingerprint());
            $this->assertDatabaseCount('stock_receipts', 0);
            $this->assertDatabaseCount('stock_return_allocations', 0);
        } finally {
            Activity::flushEventListeners();
        }
    }

    public function test_writer_rejects_foreign_sale_line(): void
    {
        $request = $this->request();
        $this->expectException(InvalidArgumentException::class);
        DB::transaction(fn () => app(SaleReturnStockWriter::class)->receive(new SaleReturnStockRequest($request->warehouseId, $request->sourceId, 'SR-QA', $request->invoiceId,
            [array_replace($request->lines[0], ['sale_line_id' => strtolower((string) Str::ulid())])])));
    }

    public function test_zero_cost_issued_stock_returns_and_can_be_sold_again_without_losing_residual(): void
    {
        $item = DB::table('items')->where('sku', 'DEMO-CCTV-NVR-8CH')->sole();
        $warehouse = $this->invoice['warehouse_id'];
        $opening = app(ManageOpening::class)->create(['warehouse_id' => $warehouse,
            'lines' => [['item_id' => $item->id, 'unit_id' => $item->base_unit_id, 'quantity' => '3', 'unit_cost' => '0.000001']]]);
        app(PostOpening::class)->handle($opening);
        $invoiceId = strtolower((string) Str::ulid());
        $line = strtolower((string) Str::ulid());
        app(IssueStock::class)->handle(['source_id' => $invoiceId, 'source_number' => 'POS-MICRO', 'warehouse_id' => $warehouse,
            'lines' => [['source_line_id' => $line, 'item_id' => $item->id, 'base_quantity' => '0.000001']]]);
        $receipt = DB::transaction(fn () => app(SaleReturnStockWriter::class)->receive(new SaleReturnStockRequest($warehouse, strtolower((string) Str::ulid()), 'SR-MICRO', $invoiceId,
            [['source_line_id' => strtolower((string) Str::ulid()), 'sale_line_id' => $line, 'base_quantity' => '0.000001']])));
        $this->assertDatabaseHas('stock_receipt_lines', ['receipt_id' => $receipt->id, 'quantity' => '0.000001', 'total_value' => '0.000000']);
        $sale = app(IssueStock::class)->handle(['source_id' => strtolower((string) Str::ulid()), 'source_number' => 'POS-RESALE', 'warehouse_id' => $warehouse,
            'lines' => [['source_line_id' => strtolower((string) Str::ulid()), 'item_id' => $item->id, 'base_quantity' => '3']]]);
        $this->assertSame('0.000003', $sale['lines'][0]['total_value']);
        $this->assertDatabaseHas('stock_balances', ['item_id' => $item->id, 'warehouse_id' => $warehouse, 'quantity' => '0.000000', 'total_value' => '0.000000']);
    }

    public function test_writer_requires_shared_transaction(): void
    {
        $request = $this->request();
        $writer = app(SaleReturnStockWriter::class);
        DB::shouldReceive('transactionLevel')->once()->andReturn(0);
        $this->expectException(\LogicException::class);
        $writer->receive($request);
    }

    public function test_return_to_a_different_warehouse_is_rejected(): void
    {
        $request = $this->request();
        $warehouse = DB::table('warehouses')->where('code', 'WH-SECONDARY')->value('id');
        $this->expectException(InvalidArgumentException::class);
        DB::transaction(fn () => app(SaleReturnStockWriter::class)->receive(new SaleReturnStockRequest($warehouse, $request->sourceId, $request->sourceNumber, $request->invoiceId, $request->lines)));
    }

    public function test_one_sale_line_return_can_restore_two_different_original_cost_consumptions(): void
    {
        $item = DB::table('items')->where('sku', 'DEMO-CCTV-IP-2MP')->sole();
        DB::transaction(fn () => app(PurchaseStockWriter::class)->receive(
            new PurchaseStockRequest($this->invoice['warehouse_id'], strtolower((string) Str::ulid()), 'PO-COST-LAYER',
                [['source_line_id' => strtolower((string) Str::ulid()), 'item_id' => $item->id, 'base_unit_id' => $item->base_unit_id, 'base_quantity' => '2', 'total_value' => '1200000']])));
        $sale = SalesReturnFixture::seed('UNIT', '7');
        $receipt = DB::transaction(fn () => app(SaleReturnStockWriter::class)->receive(new SaleReturnStockRequest($sale['warehouse_id'], strtolower((string) Str::ulid()), 'SR-MIXED-COST', $sale['id'],
            [['source_line_id' => strtolower((string) Str::ulid()), 'sale_line_id' => $sale['lines'][0]['id'], 'base_quantity' => '7']])));
        $this->assertDatabaseHas('stock_receipt_lines', ['receipt_id' => $receipt->id, 'quantity' => '7.000000', 'total_value' => '3600000.000000']);
        $this->assertDatabaseCount('stock_return_allocations', 2);
    }
}
