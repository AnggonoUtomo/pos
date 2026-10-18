<?php

namespace Tests\Feature\Modules\Sales\Returns;

use App\Models\User;
use App\Modules\Sales\POS\Application\Contracts\ReturnInvoiceLookup;
use App\Modules\Sales\Returns\Application\Actions\ManageReturnDraft;
use App\Modules\Sales\Returns\Database\Seeders\ReturnsAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\Fixtures\ReturnRefundFixture;
use Tests\Fixtures\SalesReturnFixture;
use Tests\TestCase;

class SettleReturnRefundTest extends TestCase
{
    use RefreshDatabase;

    private array $invoice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(ReturnsAccessSeeder::class);
        $this->invoice = ReturnRefundFixture::invoice();
        $this->actingAs(User::factory()->create()->givePermissionTo('sales.returns.refund'));
    }

    private function quote(string $id): array
    {
        return $this->getJson('/sales/returns/'.$id.'/refund-quote')->assertOk()->json();
    }

    private function input(array $quote, ?array $payments = null): array
    {
        return ['version' => $quote['version'], 'quote_fingerprint' => $quote['quote_fingerprint'], 'payments' => $payments ??
            ($quote['total'] === '0.00' ? [] : [['method_id' => DB::table('payment_methods')->where('code', 'TRANSFER')->value('id'), 'amount' => $quote['total']]])];
    }

    public function test_quote_is_readonly_and_only_uses_safe_source_amounts(): void
    {
        $id = ReturnRefundFixture::posted($this->invoice, '0.5');
        $before = SalesReturnFixture::fingerprint();
        $audit = Activity::count();
        $quote = $this->quote($id);
        $this->assertSame('50.00', $quote['total']);
        $this->assertSame(0, $quote['invoice_revision']);
        $this->assertSame('0.500000', $quote['lines'][0]['quantity']);
        $this->assertCount(3, $quote['methods']);
        $this->assertDatabaseCount('sales_return_refund_scopes', 0);
        $this->assertSame($audit, Activity::count());
        $this->assertSame($before, SalesReturnFixture::fingerprint());
        foreach (['total_cost', 'total_value', 'paid_total', 'account_number', 'request_hash', 'actor_id'] as $field) {
            $this->assertStringNotContainsString($field, json_encode($quote));
        }
        $source = app(ReturnInvoiceLookup::class)->refundBasis($this->invoice['id']);
        $this->assertSame($this->invoice['total'], $source->header['paid_total']);
        $this->assertArrayNotHasKey('payments', app(ReturnInvoiceLookup::class)->find($this->invoice['id'])->toArray());
    }

    public function test_settlement_and_canonical_replay_do_not_change_source_or_posting_version(): void
    {
        $id = ReturnRefundFixture::posted($this->invoice);
        $quote = $this->quote($id);
        $before = SalesReturnFixture::fingerprint();
        $header = DB::table('sales_returns')->where('id', $id)->first();
        $methods = DB::table('payment_methods')->pluck('id', 'code');
        $input = $this->input($quote, [['method_id' => $methods['TRANSFER'], 'amount' => '60'], ['method_id' => $methods['TUNAI'], 'amount' => '40']]);
        $first = $this->postJson('/sales/returns/'.$id.'/refund', $input)->assertOk()->assertJsonPath('version', 2)
            ->assertJsonPath('settlement.outcome', 'refunded')->assertJsonPath('settlement.total', '100.00')->json();
        $this->assertDatabaseCount('payment_refunds', 1);
        $this->assertDatabaseCount('sales_return_settlements', 1);
        $this->assertDatabaseCount('sales_return_refund_allocations', 1);
        $this->assertEquals($header, DB::table('sales_returns')->where('id', $id)->first());
        $this->assertSame($before, SalesReturnFixture::fingerprint());
        $audit = Activity::count();
        DB::table('payment_methods')->update(['is_active' => false, 'deleted_at' => now()]);
        $input['payments'] = array_reverse($input['payments']);
        $this->actingAs(User::factory()->create()->givePermissionTo('sales.returns.refund'));
        $this->postJson('/sales/returns/'.strtoupper($id).'/refund', $input)->assertOk()->assertExactJson($first);
        $this->assertSame($audit, Activity::count());
        $this->getJson('/sales/returns/'.$id.'/refund-quote')->assertUnprocessable();
        $input['payments'] = [['method_id' => $methods['TUNAI'], 'amount' => '100']];
        $this->postJson('/sales/returns/'.$id.'/refund', $input)->assertUnprocessable();
    }

    public function test_another_return_settlement_invalidates_quote_even_when_total_stays_the_same(): void
    {
        $first = ReturnRefundFixture::posted($this->invoice, '0.5');
        $last = ReturnRefundFixture::posted($this->invoice, '0.5');
        $a = $this->quote($first);
        $b = $this->quote($last);
        $this->postJson('/sales/returns/'.$first.'/refund', $this->input($a))->assertOk();
        $this->postJson('/sales/returns/'.$last.'/refund', $this->input($b))->assertUnprocessable()->assertJsonValidationErrors('quote_fingerprint');
        $fresh = $this->quote($last);
        $this->assertSame($b['total'], $fresh['total']);
        $this->assertSame(1, $fresh['invoice_revision']);
        $this->postJson('/sales/returns/'.$last.'/refund', $this->input($fresh))->assertOk();
        $this->assertDatabaseCount('payment_refunds', 2);
        $this->assertDatabaseHas('sales_return_refund_scopes', ['invoice_id' => $this->invoice['id'], 'revision' => 2]);
    }

    public function test_zero_settlement_consumes_quantity_without_payout_and_preserves_final_cent(): void
    {
        $invoice = ReturnRefundFixture::invoice('0.01');
        $id = ReturnRefundFixture::posted($invoice, '0.000001');
        $last = ReturnRefundFixture::posted($invoice, '0.999999');
        $quote = $this->quote($id);
        $this->assertSame('0.00', $quote['total']);
        $this->assertSame([], $quote['methods']);
        $before = SalesReturnFixture::fingerprint();
        $bad = $this->input($quote, [['method_id' => DB::table('payment_methods')->value('id'), 'amount' => '0.01']]);
        $this->postJson('/sales/returns/'.$id.'/refund', $bad)->assertUnprocessable();
        $this->postJson('/sales/returns/'.$id.'/refund', $this->input($quote))->assertOk()
            ->assertJsonPath('settlement.outcome', 'no_payment')->assertJsonPath('settlement.finance_refund_id', null);
        $this->assertDatabaseCount('payment_refunds', 0);
        $this->assertDatabaseHas('sales_return_refund_allocations', ['quantity' => '0.000001', 'amount' => '0.00']);
        $lastQuote = $this->quote($last);
        $this->assertSame('0.01', $lastQuote['total']);
        $this->postJson('/sales/returns/'.$last.'/refund', $this->input($lastQuote))->assertOk();
        $this->assertSame($before, SalesReturnFixture::fingerprint());
    }

    public function test_repeated_partial_refunds_keep_exact_residual(): void
    {
        $values = [];
        foreach (['0.333333', '0.333333', '0.333334'] as $quantity) {
            $id = ReturnRefundFixture::posted($this->invoice, $quantity);
            $quote = $this->quote($id);
            $values[] = $quote['total'];
            $this->postJson('/sales/returns/'.$id.'/refund', $this->input($quote))->assertOk();
        }
        $this->assertSame(['33.33', '33.33', '33.34'], $values);
    }

    public function test_returns_audit_veto_after_finance_rolls_back_everything(): void
    {
        $id = ReturnRefundFixture::posted($this->invoice);
        $input = $this->input($this->quote($id));
        $before = SalesReturnFixture::fingerprint();
        Activity::creating(fn ($log) => $log->log_name === 'sales.returns' && $log->event === 'refund_settled' ? false : null);
        try {
            $this->withoutExceptionHandling();
            try {
                $this->postJson('/sales/returns/'.$id.'/refund', $input);
                $this->fail('Audit veto did not propagate.');
            } catch (RuntimeException) {
                $this->assertDatabaseCount('payment_refunds', 0);
                $this->assertDatabaseCount('sales_return_settlements', 0);
                $this->assertDatabaseCount('sales_return_refund_allocations', 0);
                $this->assertDatabaseCount('sales_return_refund_scopes', 0);
                $this->assertSame($before, SalesReturnFixture::fingerprint());
            }
        } finally {
            Activity::flushEventListeners();
        }
    }

    public function test_permissions_and_posted_invariant_apply_to_super_system_too(): void
    {
        $id = ReturnRefundFixture::posted($this->invoice);
        $input = $this->input($this->quote($id));
        $this->actingAs(User::factory()->create()->givePermissionTo(['sales.returns.view', 'sales.returns.manage', 'sales.returns.post']));
        $this->getJson('/sales/returns/'.$id.'/refund-quote')->assertForbidden();
        $this->postJson('/sales/returns/'.$id.'/refund', $input)->assertForbidden();
        $draft = app(ManageReturnDraft::class)->save(['invoice_id' => $this->invoice['id'], 'lines' => [['invoice_line_id' => $this->invoice['lines'][0]['id'], 'quantity' => '1']]]);
        $this->actingAs(User::factory()->create()->assignRole(Role::findOrCreate('super-system', 'web')));
        $this->getJson('/sales/returns/'.$draft.'/refund-quote')->assertUnprocessable();
        $this->postJson('/sales/returns/'.$draft.'/refund', $input)->assertUnprocessable();
        auth()->logout();
        $this->getJson('/sales/returns/'.$id.'/refund-quote')->assertUnauthorized();
    }

    public function test_invalid_fields_version_and_source_snapshot_never_pay(): void
    {
        $id = ReturnRefundFixture::posted($this->invoice);
        $input = $this->input($this->quote($id));
        foreach ([$input + ['total' => '1'], array_replace($input, ['version' => 1]), array_replace($input, ['quote_fingerprint' => str_repeat('0', 64)]),
            array_replace($input, ['payments' => [['method_id' => $input['payments'][0]['method_id'], 'amount' => '100', 'account_id' => strtolower((string) Str::ulid())]]])] as $bad) {
            $this->postJson('/sales/returns/'.$id.'/refund', $bad)->assertUnprocessable();
            $this->assertDatabaseCount('payment_refunds', 0);
        }
        DB::table('sales_return_lines')->where('sales_return_id', $id)->update(['base_quantity' => '0.5']);
        $this->getJson('/sales/returns/'.$id.'/refund-quote')->assertUnprocessable();
        $this->postJson('/sales/returns/'.$id.'/refund', $input)->assertUnprocessable();
        $this->assertDatabaseCount('sales_return_refund_scopes', 0);
    }

    public function test_invalid_payment_summary_is_not_silently_replaced_by_header_total(): void
    {
        $id = ReturnRefundFixture::posted($this->invoice);
        DB::table('sales_invoices')->where('id', $this->invoice['id'])->update(['payments' => json_encode(['total' => 100])]);
        $this->getJson('/sales/returns/'.$id.'/refund-quote')->assertUnprocessable();
        $this->assertDatabaseCount('payment_refunds', 0);
    }

    public function test_price_changes_and_inactive_product_masters_do_not_reprice_received_returns(): void
    {
        $id = ReturnRefundFixture::posted($this->invoice, '0.5');
        $before = SalesReturnFixture::fingerprint();
        DB::table('item_prices')->update(['amount' => '999.99']);
        foreach (['items', 'units', 'warehouses', 'customers', 'customer_levels'] as $table) {
            DB::table($table)->update(['is_active' => false]);
        }
        $quote = $this->quote($id);
        $this->assertSame('50.00', $quote['total']);
        $this->postJson('/sales/returns/'.$id.'/refund', $this->input($quote))->assertOk();
        $this->assertSame($before, SalesReturnFixture::fingerprint());
    }

    public function test_empty_migration_roundtrip_and_posted_rollback_guard(): void
    {
        $migration = require app_path('Modules/Sales/Returns/Database/Migrations/2026_10_19_000002_create_sales_return_settlements.php');
        $migration->down();
        $id = ReturnRefundFixture::posted($this->invoice);
        $this->getJson('/sales/returns/'.$id.'/refund-quote')->assertUnprocessable()->assertJsonValidationErrors('refund');
        $migration->up();
        $this->postJson('/sales/returns/'.$id.'/refund', $this->input($this->quote($id)))->assertOk();
        $this->expectException(\LogicException::class);
        $migration->down();
    }

    public function test_multi_unit_lines_of_the_same_item_have_independent_refund_rights(): void
    {
        $invoice = ReturnRefundFixture::invoice(withBox: true);
        foreach ([0, 1] as $line) {
            $id = ReturnRefundFixture::posted($invoice, $invoice['lines'][$line]['quantity'], $line);
            $quote = $this->quote($id);
            $this->assertSame($invoice['lines'][$line]['subtotal'], $quote['total']);
            $this->postJson('/sales/returns/'.$id.'/refund', $this->input($quote))->assertOk();
        }
        $this->assertDatabaseCount('sales_return_refund_allocations', 2);
        $this->assertSame(2, DB::table('sales_return_refund_allocations')->distinct()->count('invoice_line_id'));
    }

    public function test_missing_trashed_and_scope_revision_overflow_fail_closed(): void
    {
        $this->getJson('/sales/returns/'.strtolower((string) Str::ulid()).'/refund-quote')->assertNotFound();
        $id = ReturnRefundFixture::posted($this->invoice);
        DB::table('sales_return_refund_scopes')->insert(['id' => strtolower((string) Str::ulid()), 'invoice_id' => $this->invoice['id'], 'revision' => 2147483647]);
        $this->getJson('/sales/returns/'.$id.'/refund-quote')->assertUnprocessable();
        $this->postJson('/sales/returns/'.$id.'/refund', ['version' => 2, 'quote_fingerprint' => str_repeat('0', 64), 'payments' => []])->assertUnprocessable();
        $this->assertDatabaseCount('payment_refunds', 0);
        DB::table('sales_returns')->where('id', $id)->update(['deleted_at' => now()]);
        $this->getJson('/sales/returns/'.$id.'/refund-quote')->assertNotFound();
    }

    public function test_finance_audit_veto_and_disabled_zero_audit_are_atomic(): void
    {
        $id = ReturnRefundFixture::posted($this->invoice);
        $input = $this->input($this->quote($id));
        Activity::creating(fn ($log) => $log->log_name === 'finance.payments' && $log->event === 'refunded' ? false : null);
        $this->withoutExceptionHandling();
        try {
            try {
                $this->postJson('/sales/returns/'.$id.'/refund', $input);
                $this->fail('Finance audit veto ignored.');
            } catch (RuntimeException) {
                $this->assertDatabaseCount('payment_refunds', 0);
                $this->assertDatabaseCount('sales_return_settlements', 0);
            }
        } finally {
            Activity::flushEventListeners();
        }
        $invoice = ReturnRefundFixture::invoice('0.01');
        $zero = ReturnRefundFixture::posted($invoice, '0.000001');
        $input = $this->input($this->quote($zero));
        activity()->disableLogging();
        try {
            try {
                $this->postJson('/sales/returns/'.$zero.'/refund', $input);
                $this->fail('Disabled audit ignored.');
            } catch (RuntimeException) {
                $this->assertDatabaseCount('sales_return_settlements', 0);
                $this->assertDatabaseCount('sales_return_refund_scopes', 0);
                $this->assertDatabaseCount('payment_refunds', 0);
            }
        } finally {
            activity()->enableLogging();
        }
    }
}
