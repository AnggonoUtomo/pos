<?php

namespace Tests\Feature\Modules\Purchasing\Returns;

use App\Modules\Purchasing\PurchaseOrders\Application\Actions\ManagePurchaseOrder;
use App\Modules\Purchasing\PurchaseOrders\Application\Actions\PostPurchaseOrder;
use App\Modules\Purchasing\Returns\Application\Actions\ManagePurchaseReturnDraft;
use App\Modules\Purchasing\Returns\Application\Contracts\ReturnDraftRepository;
use App\Modules\Purchasing\Suppliers\Infrastructure\Persistence\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;
use Tests\Feature\Modules\Inventory\Stock\StockTestCase;

class ReturnDraftTest extends StockTestCase
{
    private string $supplier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->supplier = Supplier::create(['code' => 'DRAFT-SUP', 'name' => 'Supplier CCTV', 'phone' => 'private-contact', 'is_active' => true])->id;
    }

    private function received(): array
    {
        $id = app(ManagePurchaseOrder::class)->save($this->payload() + ['supplier_id' => $this->supplier, 'notes' => 'Catatan pembelian private']);
        app(PostPurchaseOrder::class)->handle($id, 1, $this->manager->id);

        return [$id, DB::table('purchase_order_lines')->where('purchase_order_id', $id)->value('id')];
    }

    private function input(string $purchaseId, string $lineId): array
    {
        return ['purchase_order_id' => $purchaseId, 'notes' => '0', 'lines' => [
            ['purchase_order_line_id' => $lineId, 'quantity' => '0.5'],
        ]];
    }

    public function test_save_uses_published_source_snapshot_and_keeps_stock_and_purchase_unchanged(): void
    {
        [$purchaseId, $sourceLineId] = $this->received();
        $sourceBefore = DB::table('purchase_orders')->where('id', $purchaseId)->first();
        $sourceLineBefore = DB::table('purchase_order_lines')->where('id', $sourceLineId)->first();
        $stockBefore = [DB::table('stock_receipts')->get()->toJson(), DB::table('stock_receipt_lines')->get()->toJson(),
            DB::table('stock_balances')->get()->toJson(), DB::table('stock_movements')->get()->toJson(), DB::table('fifo_layers')->get()->toJson(),
            DB::table('fifo_consumptions')->get()->toJson(), DB::table('payment_disbursements')->get()->toJson(),
            DB::table('payment_disbursement_lines')->get()->toJson(), DB::table('payment_refunds')->get()->toJson()];

        $id = app(ManagePurchaseReturnDraft::class)->save($this->input($purchaseId, $sourceLineId) + [
            'id' => strtolower((string) Str::ulid()), 'number' => 'FORGED', 'status' => 'posted',
            'snapshot' => ['forged' => true], 'unit_cost' => '1', 'version' => 999,
        ]);

        $row = app(ReturnDraftRepository::class)->paginate(1, ['search' => '', 'status' => 'all', 'trashed' => false])['data'][0];
        $this->assertTrue(Str::isUlid($id));
        $this->assertSame('PR-'.strtoupper($id), $row['number']);
        $this->assertSame('draft', $row['status']);
        $this->assertSame(1, $row['version']);
        $this->assertSame('0', $row['notes']);
        $this->assertSame('PO-'.strtoupper($purchaseId), $row['purchase_order_number']);
        $this->assertSame('6.000000', $row['lines'][0]['base_quantity']);
        $this->assertSame('120000.000000', $row['lines'][0]['snapshot']['unit_cost']);
        $this->assertArrayNotHasKey('demo_key', $row);
        $this->assertStringNotContainsString('private-contact', json_encode($row, JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString('Catatan pembelian private', json_encode($row, JSON_THROW_ON_ERROR));
        $this->assertEquals($sourceBefore, DB::table('purchase_orders')->where('id', $purchaseId)->first());
        $this->assertEquals($sourceLineBefore, DB::table('purchase_order_lines')->where('id', $sourceLineId)->first());
        $this->assertSame($stockBefore, [DB::table('stock_receipts')->get()->toJson(), DB::table('stock_receipt_lines')->get()->toJson(),
            DB::table('stock_balances')->get()->toJson(), DB::table('stock_movements')->get()->toJson(), DB::table('fifo_layers')->get()->toJson(),
            DB::table('fifo_consumptions')->get()->toJson(), DB::table('payment_disbursements')->get()->toJson(),
            DB::table('payment_disbursement_lines')->get()->toJson(), DB::table('payment_refunds')->get()->toJson()]);
        $this->assertSame('created', Activity::where('log_name', 'purchasing.returns')->sole()->event);
        $this->assertStringNotContainsString('private-contact', Activity::where('log_name', 'purchasing.returns')->sole()->toJson());
    }

    public function test_invalid_source_membership_duplicate_and_quantity_do_not_create_draft(): void
    {
        [$purchaseId, $lineId] = $this->received();
        $invalid = [
            array_replace($this->input($purchaseId, $lineId), ['purchase_order_id' => strtolower((string) Str::ulid())]),
            array_replace($this->input($purchaseId, strtolower((string) Str::ulid())), []),
            ['purchase_order_id' => $purchaseId, 'lines' => [
                ['purchase_order_line_id' => $lineId, 'quantity' => '1'], ['purchase_order_line_id' => $lineId, 'quantity' => '1'],
            ]],
            ['purchase_order_id' => $purchaseId, 'lines' => [
                ['purchase_order_line_id' => $lineId, 'quantity' => '3'],
            ]],
        ];

        foreach ($invalid as $input) {
            try {
                app(ManagePurchaseReturnDraft::class)->save($input);
                $this->fail('Input draft retur invalid harus ditolak.');
            } catch (ValidationException) {
                $this->assertDatabaseCount('purchase_returns', 0);
                $this->assertDatabaseCount('purchase_return_lines', 0);
            }
        }
    }

    public function test_update_requires_current_version_keeps_source_immutable_and_noop_is_quiet(): void
    {
        [$purchaseId, $lineId] = $this->received();
        [$otherPurchase] = $this->received();
        $action = app(ManagePurchaseReturnDraft::class);
        $input = $this->input($purchaseId, $lineId);
        $id = $action->save($input);
        $logs = Activity::where('log_name', 'purchasing.returns')->count();
        $action->save($input + ['version' => 1], $id);
        $this->assertDatabaseHas('purchase_returns', ['id' => $id, 'version' => 1]);
        $this->assertSame($logs, Activity::where('log_name', 'purchasing.returns')->count());

        try {
            $action->save($this->input($otherPurchase, $lineId) + ['version' => 1], $id);
            $this->fail('Pembelian sumber draft tidak boleh diganti.');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('purchase_order_id', $error->errors());
        }
        try {
            $action->save($input + ['version' => 2], $id);
            $this->fail('Version stale harus ditolak.');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('version', $error->errors());
        }
        $changed = $input + ['version' => 1];
        $changed['lines'][0]['quantity'] = '0.25';
        $action->save($changed, $id);
        $this->assertDatabaseHas('purchase_returns', ['id' => $id, 'version' => 2]);
        $this->assertSame($logs + 1, Activity::where('log_name', 'purchasing.returns')->count());
    }

    public function test_audit_veto_rolls_back_header_and_lines(): void
    {
        [$purchaseId, $lineId] = $this->received();
        Activity::creating(fn ($log) => $log->log_name === 'purchasing.returns' ? false : null);
        try {
            app(ManagePurchaseReturnDraft::class)->save($this->input($purchaseId, $lineId));
            $this->fail('Audit gagal harus membatalkan penyimpanan draft.');
        } catch (RuntimeException) {
            $this->assertDatabaseCount('purchase_returns', 0);
            $this->assertDatabaseCount('purchase_return_lines', 0);
        } finally {
            Activity::flushEventListeners();
        }
    }
}
