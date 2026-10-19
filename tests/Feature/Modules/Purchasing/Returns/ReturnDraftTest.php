<?php

namespace Tests\Feature\Modules\Purchasing\Returns;

use App\Modules\Purchasing\PurchaseOrders\Application\Actions\ManagePurchaseOrder;
use App\Modules\Purchasing\PurchaseOrders\Application\Actions\PostPurchaseOrder;
use App\Modules\Purchasing\Returns\Application\Actions\ManagePurchaseReturnDraft;
use App\Modules\Purchasing\Returns\Application\Contracts\ReturnDraftRepository;
use App\Modules\Purchasing\Suppliers\Infrastructure\Persistence\Models\Supplier;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
        $input['lines'][0]['quantity'] = '0.123456';
        $id = $action->save($input);
        $headerSnapshot = json_decode(DB::table('purchase_returns')->where('id', $id)->value('snapshot'), true, flags: JSON_THROW_ON_ERROR);
        $lineSnapshot = json_decode(DB::table('purchase_return_lines')->where('purchase_return_id', $id)->value('snapshot'), true, flags: JSON_THROW_ON_ERROR);
        DB::table('purchase_returns')->where('id', $id)->update(['snapshot' => json_encode(array_reverse($headerSnapshot, true))]);
        DB::table('purchase_return_lines')->where('purchase_return_id', $id)->update(['snapshot' => json_encode(array_reverse($lineSnapshot, true))]);
        $logs = Activity::where('log_name', 'purchasing.returns')->count();
        $action->save($input + ['version' => 1], $id);
        $this->assertDatabaseHas('purchase_returns', ['id' => $id, 'version' => 1]);
        $this->assertDatabaseHas('purchase_return_lines', ['purchase_return_id' => $id, 'quantity' => '0.123456', 'base_quantity' => '1.481472']);
        $this->assertSame('0', DB::table('purchase_returns')->where('id', $id)->value('notes'));
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

    public function test_delete_and_restore_are_versioned_audited_and_keep_snapshots(): void
    {
        [$purchaseId, $lineId] = $this->received();
        $action = app(ManagePurchaseReturnDraft::class);
        $id = $action->save($this->input($purchaseId, $lineId));
        $lineBefore = DB::table('purchase_return_lines')->where('purchase_return_id', $id)->first();

        $action->delete($id, 1);
        $this->assertDatabaseHas('purchase_returns', ['id' => $id, 'version' => 2]);
        $this->assertNotNull(DB::table('purchase_returns')->where('id', $id)->value('deleted_at'));
        $this->assertEquals($lineBefore, DB::table('purchase_return_lines')->where('purchase_return_id', $id)->first());

        $action->restore($id, 2);
        $this->assertDatabaseHas('purchase_returns', ['id' => $id, 'version' => 3, 'deleted_at' => null]);
        $this->assertEquals($lineBefore, DB::table('purchase_return_lines')->where('purchase_return_id', $id)->first());
        $this->assertSame(['created', 'deleted', 'restored'], Activity::where('log_name', 'purchasing.returns')->orderBy('id')->pluck('event')->all());
    }

    public function test_delete_restore_reject_stale_wrong_state_and_posted_invariant(): void
    {
        [$purchaseId, $lineId] = $this->received();
        $action = app(ManagePurchaseReturnDraft::class);
        $id = $action->save($this->input($purchaseId, $lineId));

        try {
            $action->delete($id, 2);
            $this->fail('Stale version harus ditolak.');
        } catch (ValidationException) {
            $this->assertDatabaseHas('purchase_returns', ['id' => $id, 'version' => 1, 'deleted_at' => null]);
        }
        try {
            $action->restore($id, 1);
            $this->fail('Draft aktif tidak dapat dipulihkan.');
        } catch (ModelNotFoundException) {
            $this->assertDatabaseHas('purchase_returns', ['id' => $id, 'version' => 1, 'deleted_at' => null]);
        }

        $action->delete($id, 1);
        try {
            $action->delete($id, 2);
            $this->fail('Draft terhapus tidak dapat dihapus ulang.');
        } catch (ModelNotFoundException) {
            $this->assertDatabaseHas('purchase_returns', ['id' => $id, 'version' => 2]);
        }
        try {
            $action->restore($id, 3);
            $this->fail('Restore dengan version stale harus ditolak.');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('version', $error->errors());
        }

        DB::table('purchase_returns')->where('id', $id)->update(['status' => 'posted']);
        try {
            $action->restore($id, 2);
            $this->fail('Super-system maupun user biasa tidak boleh memulihkan status posted.');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('purchase_return', $error->errors());
            $this->assertDatabaseHas('purchase_returns', ['id' => $id, 'version' => 2]);
        }
    }

    public function test_delete_and_restore_audit_veto_roll_back_lifecycle_and_version(): void
    {
        [$purchaseId, $lineId] = $this->received();
        $action = app(ManagePurchaseReturnDraft::class);
        $id = $action->save($this->input($purchaseId, $lineId));

        foreach ([['deleted', fn () => $action->delete($id, 1)], ['restored', fn () => $action->restore($id, 2)]] as [$event, $mutation]) {
            $before = DB::table('purchase_returns')->where('id', $id)->first();
            Activity::creating(fn ($log) => $log->log_name === 'purchasing.returns' && $log->event === $event ? false : null);
            try {
                $mutation();
                $this->fail("Audit {$event} gagal harus membatalkan lifecycle.");
            } catch (RuntimeException) {
                $after = DB::table('purchase_returns')->where('id', $id)->first();
                $this->assertSame($before->version, $after->version);
                $this->assertSame($before->deleted_at, $after->deleted_at);
            } finally {
                Activity::flushEventListeners();
            }
            if ($event === 'deleted') {
                $action->delete($id, 1);
            }
        }
    }
}
