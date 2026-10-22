<?php

namespace Tests\Feature\Modules\Inventory\Stock;

use App\Modules\Inventory\Stock\Application\Actions\IssueStock;
use App\Modules\Inventory\Stock\Application\Contracts\PurchaseReturnStockWriter;
use App\Modules\Inventory\Stock\Application\Contracts\PurchaseStockWriter;
use App\Modules\Inventory\Stock\Application\DTOs\PurchaseReturnStockRequest;
use App\Modules\Inventory\Stock\Application\DTOs\PurchaseStockRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;

class PurchaseReturnStockTest extends StockTestCase
{
    private function purchase(string $quantity = '12', string $value = '120000'): array
    {
        $orderId = strtolower((string) Str::ulid());
        $lineId = strtolower((string) Str::ulid());
        DB::transaction(fn () => app(PurchaseStockWriter::class)->receive(new PurchaseStockRequest(
            $this->warehouse, $orderId, 'PO-'.strtoupper($orderId),
            [['source_line_id' => $lineId, 'item_id' => $this->item, 'base_unit_id' => $this->base,
                'base_quantity' => $quantity, 'total_value' => $value]], $this->manager->id
        )));
        $layerId = DB::table('fifo_layers as layer')->join('stock_movements as movement', 'movement.id', '=', 'layer.movement_id')
            ->join('stock_receipt_lines as line', 'line.id', '=', 'movement.receipt_line_id')
            ->where('line.source_line_id', $lineId)->value('layer.id');

        return [$orderId, $lineId, $layerId];
    }

    private function request(string $orderId, string $lineId, string $quantity, ?string $returnId = null): PurchaseReturnStockRequest
    {
        $returnId ??= strtolower((string) Str::ulid());

        return new PurchaseReturnStockRequest($this->warehouse, $returnId, 'PR-'.strtoupper($returnId), $orderId,
            [['source_line_id' => strtolower((string) Str::ulid()), 'purchase_line_id' => $lineId, 'base_quantity' => $quantity]], $this->manager->id);
    }

    public function test_issues_only_from_original_receipt_layer_and_replay_is_quiet(): void
    {
        [$orderId, $lineId, $originLayer] = $this->purchase();
        [, , $otherLayer] = $this->purchase('5', '100000');
        app(IssueStock::class)->handle(['warehouse_id' => $this->warehouse, 'source_id' => strtolower((string) Str::ulid()),
            'source_number' => 'POS-TEST', 'lines' => [['source_line_id' => strtolower((string) Str::ulid()),
                'item_id' => $this->item, 'base_quantity' => '6']]]);
        $request = $this->request($orderId, $lineId, '6');
        $result = DB::transaction(fn () => app(PurchaseReturnStockWriter::class)->issue($request));

        $this->assertDatabaseHas('stock_balances', ['warehouse_id' => $this->warehouse, 'item_id' => $this->item,
            'quantity' => '5.000000', 'total_value' => '100000.000000']);
        $this->assertDatabaseHas('fifo_layers', ['id' => $originLayer, 'remaining_quantity' => '0.000000', 'remaining_value' => '0.000000']);
        $this->assertDatabaseHas('fifo_layers', ['id' => $otherLayer, 'remaining_quantity' => '5.000000', 'remaining_value' => '100000.000000']);
        $this->assertDatabaseHas('stock_issues', ['id' => $result->id, 'source_type' => 'purchase_return', 'source_id' => $request->sourceId]);
        $this->assertDatabaseHas('stock_movements', ['type' => 'purchase_return', 'quantity' => '-6.000000', 'total_value' => '-60000.000000']);
        $this->assertDatabaseHas('fifo_consumptions', ['fifo_layer_id' => $originLayer, 'quantity' => '6.000000', 'total_value' => '60000.000000']);
        $before = [DB::table('stock_movements')->count(), Activity::count()];
        $this->assertSame($result->id, DB::transaction(fn () => app(PurchaseReturnStockWriter::class)->issue($request))->id);
        $this->assertSame($before, [DB::table('stock_movements')->count(), Activity::count()]);
    }

    public function test_other_stock_cannot_replace_exhausted_source_layer(): void
    {
        [$orderId, $lineId] = $this->purchase();
        $this->purchase('5', '100000');
        app(IssueStock::class)->handle(['warehouse_id' => $this->warehouse, 'source_id' => strtolower((string) Str::ulid()),
            'source_number' => 'POS-TEST', 'lines' => [['source_line_id' => strtolower((string) Str::ulid()),
                'item_id' => $this->item, 'base_quantity' => '12']]]);
        $before = DB::table('stock_balances')->where('item_id', $this->item)->sole();
        try {
            DB::transaction(fn () => app(PurchaseReturnStockWriter::class)->issue($this->request($orderId, $lineId, '1')));
            $this->fail('Sisa stok dari penerimaan lain tidak memenuhi retur sumber.');
        } catch (InvalidArgumentException) {
            $this->assertEquals($before, DB::table('stock_balances')->where('item_id', $this->item)->sole());
            $this->assertDatabaseCount('stock_issues', 1);
        }
    }

    public function test_partial_zero_value_and_full_drain_keep_residual(): void
    {
        [$orderId, $lineId, $layerId] = $this->purchase('3', '0.000001');
        DB::transaction(fn () => app(PurchaseReturnStockWriter::class)->issue($this->request($orderId, $lineId, '1')));
        $this->assertDatabaseHas('fifo_layers', ['id' => $layerId, 'remaining_quantity' => '2.000000', 'remaining_value' => '0.000001']);
        DB::transaction(fn () => app(PurchaseReturnStockWriter::class)->issue($this->request($orderId, $lineId, '2')));
        $this->assertDatabaseHas('fifo_layers', ['id' => $layerId, 'remaining_quantity' => '0.000000', 'remaining_value' => '0.000000']);
        $this->assertSame(['0.000000', '0.000001'], DB::table('stock_issue_lines')->orderBy('id')->pluck('total_value')->sort()->values()->all());
    }

    public function test_audit_veto_rolls_back_entire_issue(): void
    {
        [$orderId, $lineId, $layerId] = $this->purchase();
        Activity::creating(fn ($log) => $log->log_name === 'inventory.stock' && $log->event === 'issued' ? false : null);
        try {
            DB::transaction(fn () => app(PurchaseReturnStockWriter::class)->issue($this->request($orderId, $lineId, '2')));
            $this->fail('Audit wajib berhasil.');
        } catch (RuntimeException) {
            $this->assertDatabaseCount('stock_issues', 0);
            $this->assertDatabaseHas('fifo_layers', ['id' => $layerId, 'remaining_quantity' => '12.000000']);
        } finally {
            Activity::flushEventListeners();
        }
    }

    public function test_changed_replay_and_foreign_purchase_line_are_rejected(): void
    {
        [$orderId, $lineId] = $this->purchase();
        $request = $this->request($orderId, $lineId, '2');
        DB::transaction(fn () => app(PurchaseReturnStockWriter::class)->issue($request));
        $changed = new PurchaseReturnStockRequest($request->warehouseId, $request->sourceId, $request->sourceNumber,
            $request->purchaseId, [array_replace($request->lines[0], ['base_quantity' => '3'])], $request->actorId);
        try {
            DB::transaction(fn () => app(PurchaseReturnStockWriter::class)->issue($changed));
            $this->fail('Replay berbeda harus ditolak.');
        } catch (InvalidArgumentException) {
            $this->assertDatabaseCount('stock_issues', 1);
        }
        try {
            DB::transaction(fn () => app(PurchaseReturnStockWriter::class)->issue($this->request($orderId, strtolower((string) Str::ulid()), '1')));
            $this->fail('Baris pembelian asing harus ditolak.');
        } catch (InvalidArgumentException) {
            $this->assertDatabaseCount('stock_issues', 1);
        }
    }

    public function test_corrupt_balance_and_source_layer_metadata_are_rejected(): void
    {
        [$orderId, $lineId, $layerId] = $this->purchase();
        DB::table('stock_balances')->where('item_id', $this->item)->update(['total_value' => '1.000000']);
        try {
            DB::transaction(fn () => app(PurchaseReturnStockWriter::class)->issue($this->request($orderId, $lineId, '1')));
            $this->fail('Saldo yang tidak cocok dengan layer harus ditolak.');
        } catch (InvalidArgumentException) {
            $this->assertDatabaseCount('stock_issues', 0);
        }
        DB::table('stock_balances')->where('item_id', $this->item)->update(['total_value' => '120000.000000']);
        DB::table('fifo_layers')->where('id', $layerId)->update(['original_quantity' => '11.000000']);
        try {
            DB::transaction(fn () => app(PurchaseReturnStockWriter::class)->issue($this->request($orderId, $lineId, '1')));
            $this->fail('Layer yang tidak cocok dengan receipt harus ditolak.');
        } catch (InvalidArgumentException) {
            $this->assertDatabaseCount('stock_issues', 0);
        }
    }

    public function test_writer_requires_caller_transaction(): void
    {
        [$orderId, $lineId] = $this->purchase();
        $request = $this->request($orderId, $lineId, '1');
        DB::shouldReceive('transactionLevel')->once()->andReturn(0);
        $this->expectException(\LogicException::class);
        app(PurchaseReturnStockWriter::class)->issue($request);
    }
}
