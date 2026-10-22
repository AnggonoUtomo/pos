<?php

namespace Tests\Feature\Modules\Purchasing\Returns;

use App\Modules\Purchasing\PurchaseOrders\Application\Actions\ManagePurchaseOrder;
use App\Modules\Purchasing\PurchaseOrders\Application\Actions\PostPurchaseOrder;
use App\Modules\Purchasing\Returns\Application\Actions\ManagePurchaseReturnDraft;
use App\Modules\Purchasing\Returns\Application\Actions\PostPurchaseReturnDraft;
use App\Modules\Purchasing\Suppliers\Infrastructure\Persistence\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;
use Tests\Feature\Modules\Inventory\Stock\StockTestCase;

class PostPurchaseReturnDraftTest extends StockTestCase
{
    private string $supplier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->supplier = Supplier::create(['code' => 'POST-RET-SUP', 'name' => 'Supplier CCTV', 'is_active' => true])->id;
    }

    private function received(): array
    {
        $id = app(ManagePurchaseOrder::class)->save($this->payload() + ['supplier_id' => $this->supplier]);
        app(PostPurchaseOrder::class)->handle($id, 1, $this->manager->id);

        return [$id, DB::table('purchase_order_lines')->where('purchase_order_id', $id)->value('id')];
    }

    private function returnDraft(string $purchaseId, string $lineId, string $quantity = '0.5'): string
    {
        return app(ManagePurchaseReturnDraft::class)->save(['purchase_order_id' => $purchaseId,
            'lines' => [['purchase_order_line_id' => $lineId, 'quantity' => $quantity]]]);
    }

    public function test_post_is_atomic_immutable_and_same_version_replays_without_extra_ledger(): void
    {
        [$purchaseId, $sourceLine] = $this->received();
        $id = $this->returnDraft($purchaseId, $sourceLine);
        $receipt = DB::table('stock_receipts')->where('source_id', $purchaseId)->sole();
        $sourceBefore = DB::table('purchase_orders')->where('id', $purchaseId)->sole();
        $result = app(PostPurchaseReturnDraft::class)->handle($id, 1, $this->manager->id);

        $this->assertSame('posted', $result['status']);
        $this->assertSame(2, $result['version']);
        $this->assertDatabaseHas('purchase_returns', ['id' => $id, 'stock_issue_id' => $result['stock_issue_id'],
            'posted_from_version' => 1, 'posted_by' => $this->manager->id]);
        $this->assertDatabaseHas('stock_balances', ['warehouse_id' => $this->warehouse, 'item_id' => $this->item,
            'quantity' => '18.000000', 'total_value' => '180000.000000']);
        $this->assertDatabaseHas('stock_issues', ['id' => $result['stock_issue_id'], 'source_type' => 'purchase_return']);
        $this->assertSame($receipt->id, DB::table('stock_receipts')->where('source_id', $purchaseId)->value('id'));
        $this->assertEquals($sourceBefore, DB::table('purchase_orders')->where('id', $purchaseId)->sole());
        $before = [DB::table('stock_movements')->count(), Activity::count()];
        $this->assertSame($result, app(PostPurchaseReturnDraft::class)->handle($id, 1, $this->manager->id));
        $this->assertSame($before, [DB::table('stock_movements')->count(), Activity::count()]);
        $this->assertSame(['created', 'posted'], Activity::where('log_name', 'purchasing.returns')->orderBy('id')->pluck('event')->all());

        foreach ([fn () => app(ManagePurchaseReturnDraft::class)->delete($id, 2),
            fn () => app(ManagePurchaseReturnDraft::class)->save(['purchase_order_id' => $purchaseId,
                'lines' => [['purchase_order_line_id' => $sourceLine, 'quantity' => '0.25']], 'version' => 2], $id),
            fn () => app(PostPurchaseReturnDraft::class)->handle($id, 2, $this->manager->id)] as $mutation) {
            try {
                $mutation();
                $this->fail('Dokumen posted tidak boleh dimutasi atau replay dengan revisi berbeda.');
            } catch (ValidationException) {
                $this->assertDatabaseHas('purchase_returns', ['id' => $id, 'status' => 'posted', 'version' => 2]);
            }
        }
    }

    public function test_other_purchase_stock_does_not_allow_over_return_of_original_layer(): void
    {
        [$purchaseId, $lineId] = $this->received();
        $this->received();
        app(PostPurchaseReturnDraft::class)->handle($this->returnDraft($purchaseId, $lineId, '1'), 1, $this->manager->id);
        $next = $this->returnDraft($purchaseId, $lineId, '1.5');
        $before = DB::table('stock_balances')->where('item_id', $this->item)->sole();
        try {
            app(PostPurchaseReturnDraft::class)->handle($next, 1, $this->manager->id);
            $this->fail('Posting melebihi sisa layer sumber harus ditolak.');
        } catch (ValidationException) {
            $this->assertDatabaseHas('purchase_returns', ['id' => $next, 'status' => 'draft', 'version' => 1]);
            $this->assertEquals($before, DB::table('stock_balances')->where('item_id', $this->item)->sole());
            $this->assertDatabaseCount('stock_issues', 1);
        }
    }

    public function test_returns_audit_veto_rolls_back_stock_issue_and_status(): void
    {
        [$purchaseId, $lineId] = $this->received();
        $id = $this->returnDraft($purchaseId, $lineId);
        $balance = DB::table('stock_balances')->where('item_id', $this->item)->sole();
        Activity::creating(fn ($log) => $log->log_name === 'purchasing.returns' && $log->event === 'posted' ? false : null);
        try {
            app(PostPurchaseReturnDraft::class)->handle($id, 1, $this->manager->id);
            $this->fail('Audit Returns wajib berhasil.');
        } catch (RuntimeException) {
            $this->assertDatabaseHas('purchase_returns', ['id' => $id, 'status' => 'draft', 'version' => 1]);
            $this->assertDatabaseCount('stock_issues', 0);
            $this->assertEquals($balance, DB::table('stock_balances')->where('item_id', $this->item)->sole());
        } finally {
            Activity::flushEventListeners();
        }
    }
}
