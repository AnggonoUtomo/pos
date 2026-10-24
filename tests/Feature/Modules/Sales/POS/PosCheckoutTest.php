<?php

namespace Tests\Feature\Modules\Sales\POS;

use App\Models\User;
use App\Modules\Finance\Payments\Application\Contracts\SalePaymentWriter;
use App\Modules\Finance\Payments\Database\Seeders\PaymentsDemoSeeder;
use App\Modules\Inventory\Items\Database\Seeders\ItemsDemoSeeder;
use App\Modules\Inventory\Stock\Application\Contracts\SaleStockWriter;
use App\Modules\Inventory\Stock\Database\Seeders\StockDemoSeeder;
use App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder;
use App\Modules\Inventory\Warehouses\Database\Seeders\WarehousesDemoSeeder;
use App\Modules\Platform\CompanySettings\Application\Actions\UpdateCompanySettings;
use App\Modules\Sales\Parties\Database\Seeders\ItemPricesDemoSeeder;
use App\Modules\Sales\Parties\Database\Seeders\PartiesDemoSeeder;
use App\Modules\Sales\POS\Application\Actions\PostInvoice;
use App\Modules\Sales\POS\Application\Contracts\InvoiceRepository;
use App\Modules\Sales\POS\Application\Contracts\ReturnInvoiceLookup;
use App\Modules\Sales\POS\Database\Seeders\POSAccessSeeder;
use App\Modules\Sales\Returns\Domain\ValueObjects\RefundInvoiceBasis;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PosCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;

    private array $input;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([POSAccessSeeder::class, WarehousesDemoSeeder::class, UnitsDemoSeeder::class, ItemsDemoSeeder::class,
            PartiesDemoSeeder::class, ItemPricesDemoSeeder::class, StockDemoSeeder::class, PaymentsDemoSeeder::class]);
        $this->cashier = User::factory()->create()->givePermissionTo(['sales.pos.invoices.create', 'sales.pos.invoices.post']);
        $this->actingAs($this->cashier);
        $item = DB::table('items')->where('sku', 'DEMO-CCTV-IP-2MP')->first();
        DB::table('item_prices')->where('item_id', $item->id)->update(['amount' => '50000.00']);
        $this->input = ['idempotency_key' => strtolower((string) Str::ulid()),
            'warehouse_id' => DB::table('warehouses')->where('code', 'WH-MAIN')->value('id'),
            'customer_id' => DB::table('customers')->where('code', 'DEMO-INSTALLER')->value('id'),
            'lines' => [['item_id' => $item->id, 'unit_id' => $item->base_unit_id, 'quantity' => '2']],
            'payments' => [['method_id' => DB::table('payment_methods')->where('code', 'TUNAI')->value('id'), 'amount' => '50000'],
                ['method_id' => DB::table('payment_methods')->where('code', 'TRANSFER')->value('id'), 'amount' => '60000']]];
        $this->refreshQuote();
    }

    private function refreshQuote(): void
    {
        $this->input['quote_fingerprint'] = $this->postJson('/pos/quote', array_intersect_key($this->input, array_flip(['warehouse_id', 'customer_id', 'lines'])))
            ->assertOk()->json('fingerprint');
        $this->assertIsString($this->input['quote_fingerprint']);
    }

    private function fingerprint(): array
    {
        return array_map(fn ($table) => DB::table($table)->orderBy('id')->get()->toJson(), ['sales_invoices', 'sales_invoice_lines', 'stock_issues',
            'stock_issue_lines', 'stock_balances', 'stock_movements', 'fifo_layers', 'fifo_consumptions', 'payment_receipts', 'payment_records', 'activity_log']);
    }

    public function test_discount_tax_checkout_snapshot_and_refund_basis_use_the_same_net_value(): void
    {
        $this->input['pricing'] = ['discount_type' => 'percent', 'discount_value' => '10', 'tax_rate' => '11'];
        $quote = $this->postJson('/pos/quote', array_intersect_key($this->input, array_flip(['warehouse_id', 'customer_id', 'lines', 'pricing'])))
            ->assertOk()->assertJsonPath('total', '99900.00')->json();
        $this->input['quote_fingerprint'] = $quote['fingerprint'];
        $invoice = $this->postJson('/pos/invoices', $this->input)->assertCreated()->assertJsonPath('discount_amount', '10000.00')
            ->assertJsonPath('tax_amount', '9900.00')->assertJsonPath('payments.change', '10100.00')->json();
        $basis = app(ReturnInvoiceLookup::class)->refundBasis($invoice['id']);
        $net = RefundInvoiceBasis::fromSnapshot($basis->header, $basis->lines);
        $this->assertSame('99900.00', $net->total);
        $this->assertSame('99900.00', $net->lines[$invoice['lines'][0]['id']]['net_amount']);
        $this->assertSame('10.00', json_decode(DB::table('sales_invoices')->value('pricing'), true)['discount_value']);
        $this->assertSame($invoice, $this->postJson('/pos/invoices', $this->input)->assertCreated()->json());
    }

    public function test_pricing_changes_require_a_fresh_quote_and_fail_without_mutations(): void
    {
        $before = $this->fingerprint();
        $this->input['pricing'] = ['discount_type' => 'fixed', 'discount_value' => '10000', 'tax_rate' => '0'];
        $this->postJson('/pos/invoices', $this->input)->assertUnprocessable()->assertJsonValidationErrors('quote_fingerprint');
        $this->assertSame($before, $this->fingerprint());
    }

    public function test_receipt_keeps_original_company_identity_after_settings_change(): void
    {
        $settings = app(UpdateCompanySettings::class);
        $settings->handle(['name' => 'Toko Lama', 'version' => 0], $this->cashier->id);
        $invoice = $this->postJson('/pos/invoices', $this->input)->assertCreated()->assertJsonPath('company_snapshot.name', 'Toko Lama')->json();
        $settings->handle(['name' => 'Toko Baru', 'version' => 1], $this->cashier->id);
        $this->getJson('/pos/invoices/'.$invoice['id'])->assertOk()->assertJsonPath('company_snapshot.name', 'Toko Lama');
    }

    public function test_posting_is_atomic_and_receipt_does_not_leak_private_data(): void
    {
        $response = $this->postJson('/pos/invoices', $this->input)->assertCreated();
        $id = $response->json('id');
        $this->assertTrue(Str::isUlid($id));
        $response->assertJsonPath('number', 'POS-'.strtoupper($id))->assertJsonPath('total', '100000.00')
            ->assertJsonPath('payments.change', '10000.00')->assertJsonPath('discount_amount', '0.00')->assertJsonPath('tax_amount', '0.00');
        $this->assertDatabaseCount('sales_invoices', 1);
        $this->assertDatabaseCount('payment_records', 2);
        $this->assertDatabaseCount('stock_issues', 1);
        $this->assertDatabaseHas('stock_balances', ['warehouse_id' => $this->input['warehouse_id'], 'item_id' => $this->input['lines'][0]['item_id'], 'quantity' => '6.000000']);
        $this->assertDatabaseHas('activity_log', ['log_name' => 'sales.pos', 'event' => 'posted', 'subject_id' => $id, 'causer_id' => $this->cashier->id]);
        $receipt = $this->getJson('/pos/invoices/'.$id)->assertOk()->json();
        $this->assertSame($response->json(), $receipt);
        foreach (['total_cost', 'request_hash', 'idempotency_key', 'account_number', 'account_holder', 'phone', 'email', 'address', 'bank_name'] as $private) {
            $this->assertStringNotContainsString('"'.$private.'"', json_encode(Arr::except($receipt, ['company_snapshot'])));
        }
        $line = DB::table('sales_invoice_lines')->where('invoice_id', $id)->sole();
        $this->assertSame('Kamera IP 2MP', $line->name);
        $this->assertSame('1.000000', $line->conversion_ratio);
        $this->assertSame((string) DB::table('fifo_consumptions')->value('total_value'), (string) $line->total_cost);
    }

    public function test_canonical_retry_returns_original_snapshot_after_master_changes(): void
    {
        $first = $this->postJson('/pos/invoices', $this->input)->assertCreated()->json();
        DB::table('customers')->where('id', $this->input['customer_id'])->update(['name' => 'Nama Baru', 'is_active' => false]);
        DB::table('item_prices')->where('item_id', $this->input['lines'][0]['item_id'])->update(['amount' => '999']);
        $before = $this->fingerprint();
        $this->input['lines'][0]['quantity'] = '02.000000';
        $this->input['payments'] = array_reverse($this->input['payments']);
        foreach ($this->input['payments'] as &$row) {
            $row['method_id'] = strtoupper($row['method_id']);
            $row['amount'] .= '.00';
        }
        unset($row);
        $this->assertSame($first, $this->postJson('/pos/invoices', $this->input)->assertCreated()->json());
        $this->assertSame($before, $this->fingerprint());
        $this->assertSame($first, $this->getJson('/pos/invoices/'.$first['id'])->assertOk()->json());
    }

    public function test_reusing_key_for_changed_payload_rejects_without_new_mutation(): void
    {
        $this->postJson('/pos/invoices', $this->input)->assertCreated();
        $before = $this->fingerprint();
        $this->input['payments'][0]['amount'] = '60000';
        $this->postJson('/pos/invoices', $this->input)->assertUnprocessable()->assertJsonValidationErrors('idempotency_key');
        $this->assertSame($before, $this->fingerprint());
    }

    public function test_unique_collision_rolls_back_new_consumers_then_returns_the_winning_invoice(): void
    {
        $first = app(PostInvoice::class)->handle($this->input, $this->cashier->id);
        $real = app(InvoiceRepository::class);
        $reads = 0;
        $repository = \Mockery::mock($real)->makePartial();
        $repository->shouldReceive('lockByKey')->twice()->andReturnUsing(function ($actor, $key) use ($real, &$reads) {
            return ++$reads === 1 ? null : $real->lockByKey($actor, $key);
        });
        $this->app->instance(InvoiceRepository::class, $repository);
        $before = $this->fingerprint();
        $this->assertSame($first, app(PostInvoice::class)->handle($this->input, $this->cashier->id));
        $this->assertSame($before, $this->fingerprint());
    }

    public function test_cashier_method_choices_exclude_inactive_or_incompatible_accounts(): void
    {
        DB::table('payment_accounts')->where('type', 'bank')->update(['is_active' => false, 'account_number' => '00123456']);
        $response = $this->getJson('/pos/payment-methods')->assertOk()->assertJsonCount(1, 'methods');
        $response->assertJsonPath('methods.0.type', 'cash');
        foreach (['account_number', 'account_holder', 'payment_account_id'] as $key) {
            $this->assertStringNotContainsString('"'.$key.'"', $response->getContent());
        }
    }

    public function test_stale_quote_and_tampering_never_post_a_sale(): void
    {
        DB::table('item_prices')->where('item_id', $this->input['lines'][0]['item_id'])->update(['amount' => '49000']);
        $before = $this->fingerprint();
        $this->postJson('/pos/invoices', $this->input)->assertUnprocessable()->assertJsonValidationErrors('quote_fingerprint');
        foreach (['unit_price', 'actor_id', 'discount_amount', 'tax_amount', 'status'] as $key) {
            $this->postJson('/pos/invoices', $this->input + [$key => '1'])->assertUnprocessable();
        }
        $this->assertSame($before, $this->fingerprint());
    }

    public function test_current_invariants_hold_even_for_super_system(): void
    {
        $this->cashier->assignRole(Role::findOrCreate('super-system', 'web'));
        foreach (['customer', 'method', 'stock', 'unit', 'sale_flag'] as $case) {
            DB::beginTransaction();
            try {
                match ($case) {
                    'customer' => DB::table('customers')->where('id', $this->input['customer_id'])->update(['is_active' => false]),
                    'method' => DB::table('payment_methods')->where('id', $this->input['payments'][0]['method_id'])->update(['is_active' => false]),
                    'stock' => DB::table('stock_balances')->where('item_id', $this->input['lines'][0]['item_id'])->update(['quantity' => '1']),
                    'unit' => DB::table('units')->where('id', $this->input['lines'][0]['unit_id'])->update(['is_active' => false]),
                    'sale_flag' => DB::table('item_units')->where('item_id', $this->input['lines'][0]['item_id'])->update(['is_sale_unit' => false]),
                };
                $before = $this->fingerprint();
                $this->postJson('/pos/invoices', $this->input)->assertUnprocessable();
                $this->assertSame($before, $this->fingerprint());
            } finally {
                DB::rollBack();
            }
        }
    }

    public function test_multisatuan_aggregates_stock_and_cost_per_source_line(): void
    {
        $this->input['lines'][0]['quantity'] = '1';
        $this->input['lines'][] = ['item_id' => $this->input['lines'][0]['item_id'], 'unit_id' => DB::table('units')->where('code', 'BOX')->value('id'), 'quantity' => '1'];
        $this->refreshQuote();
        $result = $this->postJson('/pos/invoices', $this->input)->assertCreated()->assertJsonCount(2, 'lines')->json();
        $this->assertDatabaseHas('stock_balances', ['item_id' => $this->input['lines'][0]['item_id'], 'warehouse_id' => $this->input['warehouse_id'], 'quantity' => '3.000000']);
        foreach (DB::table('sales_invoice_lines')->where('invoice_id', $result['id'])->get() as $line) {
            $this->assertDatabaseHas('stock_issue_lines', ['source_line_id' => $line->id, 'total_value' => $line->total_cost]);
        }
    }

    public function test_failure_after_stock_or_payment_write_rolls_back_the_entire_checkout(): void
    {
        foreach ([SaleStockWriter::class => 'issue', SalePaymentWriter::class => 'receive'] as $contract => $method) {
            $real = app($contract);
            $mock = \Mockery::mock($contract);
            $mock->shouldReceive($method)->once()->andReturnUsing(function ($request) use ($real, $method) {
                $real->$method($request);
                throw new \RuntimeException('Simulated integration failure');
            });
            $this->app->instance($contract, $mock);
            $before = $this->fingerprint();
            try {
                app(PostInvoice::class)->handle($this->input, $this->cashier->id);
                $this->fail('Failure must roll back.');
            } catch (\RuntimeException $exception) {
                $this->assertSame('Simulated integration failure', $exception->getMessage());
                $this->assertSame($before, $this->fingerprint());
            } finally {
                $this->app->instance($contract, $real);
            }
        }
    }

    public function test_pos_audit_veto_rolls_back_stock_payments_and_invoice(): void
    {
        $before = $this->fingerprint();
        Activity::creating(static fn (Activity $row) => $row->log_name === 'sales.pos' ? false : null);
        try {
            app(PostInvoice::class)->handle($this->input, $this->cashier->id);
            $this->fail('Audit veto must fail.');
        } catch (\RuntimeException) {
            $this->assertSame($before, $this->fingerprint());
        } finally {
            Activity::flushEventListeners();
        }
        $this->postJson('/pos/invoices', $this->input)->assertCreated();
    }

    public function test_invoice_persistence_failure_rolls_back_consumers(): void
    {
        $real = app(InvoiceRepository::class);
        $repository = \Mockery::mock($real)->makePartial();
        $repository->shouldReceive('createPosted')->once()->andReturnUsing(function ($header, $lines) use ($real): void {
            $real->createPosted($header, $lines);
            throw new \RuntimeException('Simulated invoice failure');
        });
        $this->app->instance(InvoiceRepository::class, $repository);
        $before = $this->fingerprint();
        try {
            app(PostInvoice::class)->handle($this->input, $this->cashier->id);
            $this->fail('Persistence must fail.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated invoice failure', $exception->getMessage());
            $this->assertSame($before, $this->fingerprint());
        }
    }

    public function test_post_permissions_choices_and_detail_ownership_are_independent_of_finance_crud(): void
    {
        $this->getJson('/pos/payment-methods')->assertOk()->assertJsonCount(3, 'methods');
        $first = $this->postJson('/pos/invoices', $this->input)->assertCreated()->json('id');
        $createOnly = User::factory()->create()->givePermissionTo('sales.pos.invoices.create');
        $this->actingAs($createOnly)->postJson('/pos/invoices', $this->input)->assertForbidden();
        $this->getJson('/pos/payment-methods')->assertForbidden();
        $this->getJson('/pos/invoices/'.$first)->assertForbidden();
        $postOnly = User::factory()->create()->givePermissionTo('sales.pos.invoices.post');
        $this->actingAs($postOnly)->postJson('/pos/invoices', $this->input)->assertForbidden();
        $other = User::factory()->create()->givePermissionTo(['sales.pos.invoices.create', 'sales.pos.invoices.post']);
        $this->actingAs($other)->getJson('/pos/invoices/'.$first)->assertForbidden();
        $this->postJson('/pos/invoices', $this->input)->assertCreated();
        $this->assertDatabaseCount('sales_invoices', 2);
        $viewer = User::factory()->create()->givePermissionTo('sales.pos.invoices.view');
        $this->actingAs($viewer)->getJson('/pos/invoices/'.$first)->assertOk();
        $this->postJson('/pos/invoices', $this->input)->assertForbidden();
        auth()->logout();
        $this->postJson('/pos/invoices', $this->input)->assertUnauthorized();
        $this->getJson('/pos/invoices/'.$first)->assertUnauthorized();
    }

    public function test_posted_invoice_cannot_be_mutated_or_migration_dropped(): void
    {
        $id = $this->postJson('/pos/invoices', $this->input)->assertCreated()->json('id');
        $before = $this->fingerprint();
        $this->putJson('/pos/invoices/'.$id, [])->assertStatus(405);
        $this->deleteJson('/pos/invoices/'.$id)->assertStatus(405);
        $this->assertSame($before, $this->fingerprint());
        $migration = require app_path('Modules/Sales/POS/Database/Migrations/2026_10_16_000003_create_sales_invoices.php');
        $this->expectException(\LogicException::class);
        $migration->down();
    }

    public function test_checkout_requires_csrf_and_invalid_payments_rollback_stock(): void
    {
        $before = $this->fingerprint();
        $invalid = $this->input;
        $invalid['payments'][0]['amount'] = '1';
        $this->postJson('/pos/invoices', $invalid)->assertUnprocessable();
        $this->assertSame($before, $this->fingerprint());
        $this->app->instance(ValidateCsrfToken::class, new class($this->app, $this->app['encrypter']) extends ValidateCsrfToken
        {
            protected function runningUnitTests(): bool
            {
                return false;
            }
        });
        $this->postJson('/pos/invoices', $this->input)->assertStatus(419);
        $this->assertSame($before, $this->fingerprint());
    }
}
