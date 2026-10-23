<?php

namespace Tests\Feature\Modules\Purchasing\Returns;

use App\Models\User;
use App\Modules\Finance\Payments\Application\Contracts\PurchaseRefundReceiptWriter;
use App\Modules\Finance\Payments\Application\DTOs\PurchaseRefundReceiptRequest;
use App\Modules\Finance\Payments\Database\Seeders\PaymentsDemoSeeder;
use App\Modules\Purchasing\PurchaseOrders\Application\Actions\ManagePurchaseOrder;
use App\Modules\Purchasing\PurchaseOrders\Application\Actions\PayPurchaseOrder;
use App\Modules\Purchasing\PurchaseOrders\Application\Actions\PostPurchaseOrder;
use App\Modules\Purchasing\Returns\Application\Actions\ManagePurchaseReturnDraft;
use App\Modules\Purchasing\Returns\Application\Actions\PostPurchaseReturnDraft;
use App\Modules\Purchasing\Returns\Application\Actions\SettleReturnRefund;
use App\Modules\Purchasing\Suppliers\Infrastructure\Persistence\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Tests\Feature\Modules\Inventory\Stock\StockTestCase;

class PurchaseReturnRefundTest extends StockTestCase
{
    private array $methods;

    private string $purchase;

    private string $line;

    private const URL = '/purchasing/returns/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PaymentsDemoSeeder::class);
        foreach (['view', 'manage', 'post', 'refund'] as $key) {
            $this->manager->givePermissionTo(Permission::findOrCreate('purchasing.returns.'.$key, 'web'));
        }
        $this->methods = DB::table('payment_methods')->pluck('id', 'code')->all();
        $supplier = Supplier::create(['code' => 'REFUND-SUP', 'name' => 'Supplier CCTV', 'is_active' => true]);
        $this->purchase = app(ManagePurchaseOrder::class)->save($this->payload() + ['supplier_id' => $supplier->id]);
        app(PostPurchaseOrder::class)->handle($this->purchase, 1, $this->manager->id);
        $this->line = DB::table('purchase_order_lines')->where('purchase_order_id', $this->purchase)->value('id');
    }

    private function paid(): void
    {
        app(PayPurchaseOrder::class)->handle($this->purchase, 2, [['method_id' => $this->methods['TUNAI'], 'amount' => '240000']], $this->manager->id);
    }

    private function returned(string $quantity = '0.5', bool $post = true): string
    {
        $id = app(ManagePurchaseReturnDraft::class)->save(['purchase_order_id' => $this->purchase,
            'lines' => [['purchase_order_line_id' => $this->line, 'quantity' => $quantity]]]);
        if ($post) {
            app(PostPurchaseReturnDraft::class)->handle($id, 1, $this->manager->id);
        }

        return $id;
    }

    private function quote(string $id): array
    {
        return $this->getJson(self::URL.$id.'/refund-quote')->assertOk()->json();
    }

    private function input(array $quote): array
    {
        return ['version' => $quote['version'], 'quote_fingerprint' => $quote['quote_fingerprint'],
            'payments' => [['method_id' => $this->methods['TRANSFER'], 'amount' => $quote['total']]]];
    }

    private function stock(): array
    {
        return array_map(fn ($table) => DB::table($table)->orderBy('id')->get()->toJson(),
            ['purchase_orders', 'purchase_order_lines', 'stock_balances', 'stock_movements', 'fifo_layers', 'stock_issues', 'stock_issue_lines', 'payment_disbursements', 'payment_disbursement_lines']);
    }

    public function test_receipt_is_atomic_exact_and_replays_without_live_master_or_stock_changes(): void
    {
        $this->paid();
        $id = $this->returned();
        $before = $this->stock();
        $quote = $this->quote($id);
        $this->assertSame('60000.000000', $quote['total']);
        $input = $this->input($quote);
        $input['payments'] = [['method_id' => $this->methods['TRANSFER'], 'amount' => '40000'], ['method_id' => $this->methods['TUNAI'], 'amount' => '20000']];
        $result = $this->postJson(self::URL.$id.'/refund', $input)->assertOk()
            ->assertJsonPath('settlement.outcome', 'received')->assertJsonPath('settlement.total', '60000.000000')->json();
        $this->assertDatabaseCount('purchase_refund_receipts', 1);
        $this->assertDatabaseCount('purchase_refund_receipt_lines', 2);
        $this->assertDatabaseCount('purchase_return_settlements', 1);
        $this->assertDatabaseHas('purchase_returns', ['id' => $id, 'version' => 2, 'status' => 'posted']);
        $logs = Activity::count();
        DB::table('payment_methods')->update(['is_active' => false, 'name' => 'Diubah']);
        $input['payments'] = array_reverse($input['payments']);
        $this->postJson(self::URL.$id.'/refund', $input)->assertOk()->assertExactJson($result);
        $this->assertSame($logs, Activity::count());
        $this->assertSame($before, $this->stock());
        $this->getJson(self::URL.$id.'/refund-quote')->assertUnprocessable();
        $input['payments'][0]['amount'] = '1';
        $this->postJson(self::URL.$id.'/refund', $input)->assertUnprocessable();
    }

    public function test_unpaid_draft_stale_tampered_and_nonexact_requests_are_rejected(): void
    {
        $id = $this->returned();
        $this->getJson(self::URL.$id.'/refund-quote')->assertUnprocessable();
        $this->paid();
        $draft = $this->returned('0.1', false);
        $this->getJson(self::URL.$draft.'/refund-quote')->assertUnprocessable();
        $input = $this->input($this->quote($id));
        foreach ([array_replace($input, ['version' => 1]), $input + ['total' => '1'],
            array_replace($input, ['quote_fingerprint' => str_repeat('a', 64)]),
            array_replace($input, ['payments' => [['method_id' => $this->methods['TRANSFER'], 'amount' => '60000.000001']]])] as $bad) {
            $this->postJson(self::URL.$id.'/refund', $bad)->assertUnprocessable();
            $this->assertDatabaseCount('purchase_refund_receipts', 0);
            $this->assertDatabaseCount('purchase_return_settlements', 0);
        }
        DB::table('payment_methods')->where('id', $this->methods['TRANSFER'])->update(['is_active' => false]);
        $this->postJson(self::URL.$id.'/refund', $input)->assertUnprocessable();
    }

    public function test_scope_revision_requires_refresh_and_final_refund_cannot_exceed_paid_source(): void
    {
        $this->paid();
        $first = $this->returned('1');
        $second = $this->returned('1');
        $stale = $this->input($this->quote($second));
        $this->postJson(self::URL.$first.'/refund', $this->input($this->quote($first)))->assertOk();
        $this->postJson(self::URL.$second.'/refund', $stale)->assertUnprocessable()->assertJsonValidationErrors('quote_fingerprint');
        $this->postJson(self::URL.$second.'/refund', $this->input($this->quote($second)))->assertOk();
        $this->assertDatabaseCount('purchase_refund_receipts', 2);
        $request = new PurchaseRefundReceiptRequest(strtolower((string) Str::ulid()), 'OVER-REFUND', $this->purchase,
            DB::table('purchase_orders')->where('id', $this->purchase)->value('payment_disbursement_id'), '0.000001',
            [['method_id' => $this->methods['TRANSFER'], 'amount' => '0.000001']], $this->manager->id);
        try {
            DB::transaction(fn () => app(PurchaseRefundReceiptWriter::class)->receive($request));
            $this->fail('Penerimaan tidak boleh melebihi source paid.');
        } catch (InvalidArgumentException) {
            $this->assertDatabaseCount('purchase_refund_receipts', 2);
        }
    }

    public function test_either_audit_veto_rolls_back_all_money_and_allocations(): void
    {
        $this->paid();
        $id = $this->returned();
        $input = $this->input($this->quote($id));
        $before = $this->stock();
        foreach (['finance.payments', 'purchasing.returns'] as $name) {
            Activity::creating(fn ($log) => $log->log_name === $name && in_array($log->event, ['supplier_refund_received', 'refund_settled'], true) ? false : null);
            try {
                app(SettleReturnRefund::class)->handle($id, $input, $this->manager->id);
                $this->fail('Audit wajib atomic.');
            } catch (RuntimeException) {
                $this->assertDatabaseCount('purchase_refund_receipts', 0);
                $this->assertDatabaseCount('purchase_refund_receipt_lines', 0);
                $this->assertDatabaseCount('purchase_return_settlements', 0);
                $this->assertDatabaseCount('purchase_return_refund_allocations', 0);
                $this->assertSame($before, $this->stock());
            } finally {
                Activity::flushEventListeners();
            }
        }
        $this->postJson(self::URL.$id.'/refund', $input)->assertOk();
    }

    public function test_permission_is_separate_and_csrf_is_required(): void
    {
        $this->paid();
        $id = $this->returned();
        $input = $this->input($this->quote($id));
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('purchasing.returns.view');
        $this->actingAs($viewer)->getJson(self::URL.$id.'/refund-quote')->assertForbidden();
        $this->postJson(self::URL.$id.'/refund', $input)->assertForbidden();
        $this->actingAs($this->manager);
        $this->app->detectEnvironment(fn () => 'local');
        $this->postJson(self::URL.$id.'/refund', $input)->assertStatus(419);
        $this->app->detectEnvironment(fn () => 'testing');
        $this->postJson(self::URL.$id.'/refund', $input)->assertOk();
    }

    public function test_history_migrations_refuse_to_drop_populated_receipts_and_settlements(): void
    {
        $this->paid();
        $id = $this->returned();
        $this->postJson(self::URL.$id.'/refund', $this->input($this->quote($id)))->assertOk();
        foreach (['Finance/Payments/Database/Migrations/2026_10_23_000001_create_purchase_refund_receipts.php',
            'Purchasing/Returns/Database/Migrations/2026_10_23_000002_create_purchase_return_settlements.php'] as $path) {
            try {
                (require app_path('Modules/'.$path))->down();
                $this->fail('Histori tidak boleh dihapus lewat rollback.');
            } catch (LogicException) {
                $this->assertDatabaseCount('purchase_refund_receipts', 1);
                $this->assertDatabaseCount('purchase_return_settlements', 1);
            }
        }
    }

    public function test_zero_allocation_has_no_money_ledger_and_final_quantity_receives_residual(): void
    {
        $supplier = DB::table('purchase_orders')->where('id', $this->purchase)->value('supplier_id');
        $this->purchase = app(ManagePurchaseOrder::class)->save(['supplier_id' => $supplier, 'warehouse_id' => $this->warehouse,
            'lines' => [['item_id' => $this->item, 'unit_id' => $this->base, 'quantity' => '3', 'unit_cost' => '0.000001']]]);
        app(PostPurchaseOrder::class)->handle($this->purchase, 1);
        app(PayPurchaseOrder::class)->handle($this->purchase, 2, [['method_id' => $this->methods['TUNAI'], 'amount' => '0.000003']]);
        $this->line = DB::table('purchase_order_lines')->where('purchase_order_id', $this->purchase)->value('id');
        $tiny = $this->returned('0.000001');
        $quote = $this->quote($tiny);
        $this->assertSame('0.000000', $quote['total']);
        $this->assertSame([], $quote['methods']);
        $input = ['version' => 2, 'quote_fingerprint' => $quote['quote_fingerprint'], 'payments' => []];
        $this->postJson(self::URL.$tiny.'/refund', $input)->assertOk()->assertJsonPath('settlement.outcome', 'no_payment');
        $this->assertDatabaseCount('purchase_refund_receipts', 0);
        $last = $this->returned('2.999999');
        $quote = $this->quote($last);
        $this->assertSame('0.000003', $quote['total']);
        $this->postJson(self::URL.$last.'/refund', $this->input($quote))->assertOk();
        $this->getJson(self::URL.'data')->assertOk()->assertJsonPath('data.0.settlement.total', '0.000003');
        $this->assertDatabaseCount('purchase_refund_receipts', 1);
    }
}
