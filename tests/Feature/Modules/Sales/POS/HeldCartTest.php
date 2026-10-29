<?php

namespace Tests\Feature\Modules\Sales\POS;

use App\Models\User;
use App\Modules\Finance\Payments\Database\Seeders\PaymentsDemoSeeder;
use App\Modules\Inventory\Items\Database\Seeders\ItemsDemoSeeder;
use App\Modules\Inventory\Stock\Database\Seeders\StockDemoSeeder;
use App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder;
use App\Modules\Inventory\Warehouses\Database\Seeders\WarehousesDemoSeeder;
use App\Modules\Sales\Parties\Database\Seeders\ItemPricesDemoSeeder;
use App\Modules\Sales\Parties\Database\Seeders\PartiesDemoSeeder;
use App\Modules\Sales\POS\Database\Seeders\POSAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\Fixtures\CashierShiftFixture;
use Tests\TestCase;

class HeldCartTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;

    private string $id;

    private array $input;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([POSAccessSeeder::class, WarehousesDemoSeeder::class, UnitsDemoSeeder::class,
            ItemsDemoSeeder::class, PartiesDemoSeeder::class, ItemPricesDemoSeeder::class, StockDemoSeeder::class, PaymentsDemoSeeder::class]);
        $this->cashier = User::factory()->create()->givePermissionTo(['sales.pos.invoices.create', 'sales.pos.invoices.post']);
        $this->actingAs($this->cashier);
        CashierShiftFixture::open($this->cashier->id);
        $this->id = strtolower((string) Str::ulid());
        $item = DB::table('items')->where('sku', 'DEMO-CCTV-IP-2MP')->first();
        $this->input = ['version' => 0, 'label' => 'Kamera meja A',
            'warehouse_id' => DB::table('warehouses')->where('code', 'WH-MAIN')->value('id'),
            'customer_id' => DB::table('customers')->where('code', 'DEMO-INSTALLER')->value('id'),
            'lines' => [['item_id' => $item->id, 'unit_id' => $item->base_unit_id, 'quantity' => '2']],
            'pricing' => ['discount_type' => 'percent', 'discount_value' => '10', 'tax_rate' => '11']];
    }

    private function store(): array
    {
        return $this->postJson("/pos/held-carts/{$this->id}", $this->input)->assertOk()->json();
    }

    private function ledger(): string
    {
        return hash('sha256', json_encode(array_map(fn ($table) => DB::table($table)->orderBy('id')->get()->toArray(),
            ['sales_invoices', 'stock_balances', 'stock_movements', 'fifo_layers', 'fifo_consumptions', 'payment_receipts', 'payment_records'])));
    }

    private function checkout(array $cart): array
    {
        CashierShiftFixture::open((int) auth()->id());
        $quote = $this->postJson('/pos/quote', collect($this->input)->except(['version', 'label'])->all())->assertOk()->json();

        return ['idempotency_key' => strtolower((string) Str::ulid()), 'warehouse_id' => $this->input['warehouse_id'],
            'customer_id' => $this->input['customer_id'], 'lines' => $this->input['lines'], 'pricing' => $this->input['pricing'],
            'quote_fingerprint' => $quote['fingerprint'], 'held_cart' => ['id' => $cart['id'], 'version' => $cart['version']],
            'payments' => [['method_id' => DB::table('payment_methods')->where('code', 'TUNAI')->value('id'), 'amount' => $quote['total']]]];
    }

    public function test_hold_and_recall_are_own_only_and_do_not_reserve_or_mutate_ledger(): void
    {
        $before = $this->ledger();
        $cart = $this->store();
        $this->assertSame($this->id, $cart['id']);
        $this->assertSame(1, $cart['version']);
        $this->getJson('/pos/held-carts')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.label', 'Kamera meja A');
        $auditCount = DB::table('activity_log')->count();
        $this->getJson("/pos/held-carts/{$this->id}")->assertOk()->assertJsonPath('cart.lines.0.quantity', '2.000000')
            ->assertJsonPath('quote.discount_amount', '123500.00')->assertJsonPath('quote.total', '1233765.00');
        $this->assertSame($before, $this->ledger());
        $this->assertSame($auditCount, DB::table('activity_log')->count());
        $this->assertDatabaseHas('activity_log', ['event' => 'held', 'subject_id' => $this->id, 'causer_id' => $this->cashier->id]);
    }

    public function test_other_cashier_and_even_super_system_cannot_read_change_cancel_or_checkout_foreign_cart(): void
    {
        $cart = $this->store();
        $checkout = $this->checkout($cart);
        foreach ([User::factory()->create()->givePermissionTo(['sales.pos.invoices.create', 'sales.pos.invoices.post']),
            User::factory()->create()->assignRole(Role::findOrCreate('super-system', 'web'))] as $other) {
            $this->actingAs($other)->getJson('/pos/held-carts')->assertOk()->assertJsonCount(0, 'data');
            $this->getJson("/pos/held-carts/{$this->id}")->assertNotFound();
            $this->postJson("/pos/held-carts/{$this->id}", [...$this->input, 'version' => 1])->assertNotFound();
            $this->postJson("/pos/held-carts/{$this->id}/cancel", ['version' => 1])->assertNotFound();
            CashierShiftFixture::open($other->id);
            $this->postJson('/pos/invoices', $checkout)->assertUnprocessable()->assertJsonValidationErrors('held_cart');
        }
        $this->assertDatabaseCount('sales_invoices', 0);
    }

    public function test_permission_guest_and_untrusted_fields_are_rejected(): void
    {
        $this->store();
        $this->actingAs(User::factory()->create())->getJson('/pos/held-carts')->assertForbidden();
        $this->postJson("/pos/held-carts/{$this->id}", $this->input)->assertForbidden();
        auth()->forgetGuards();
        $this->getJson('/pos/held-carts')->assertUnauthorized();
        $this->actingAs($this->cashier)->getJson('/pos/held-carts?actor_id=1')->assertUnprocessable();
        $this->postJson('/pos/held-carts/'.Str::ulid(), [...$this->input, 'actor_id' => 1])->assertUnprocessable();
        foreach (['zero', 'duplicate', 'unknown', 'price', 'bad_pricing'] as $case) {
            $input = $this->input;
            if ($case === 'zero') {
                $input['lines'][0]['quantity'] = '0';
            }
            if ($case === 'duplicate') {
                $input['lines'][] = $input['lines'][0];
            }
            if ($case === 'unknown') {
                $input['lines'][0]['unit_id'] = (string) Str::ulid();
            }
            if ($case === 'price') {
                $input['lines'][0]['unit_price'] = '1';
            }
            if ($case === 'bad_pricing') {
                $input['pricing']['tax_rate'] = '101';
            }
            $this->postJson('/pos/held-carts/'.Str::ulid(), $input)->assertUnprocessable();
        }
        $this->assertDatabaseCount('pos_held_carts', 1);
    }

    public function test_retry_is_idempotent_and_stale_updates_do_not_overwrite(): void
    {
        $first = $this->store();
        $audit = DB::table('activity_log')->count();
        $this->assertSame($first, $this->store());
        $this->assertSame($audit, DB::table('activity_log')->count());
        $change = [...$this->input, 'version' => 1, 'label' => 'Kamera meja B'];
        $next = $this->postJson("/pos/held-carts/{$this->id}", $change)->assertOk()->assertJsonPath('version', 2)->json();
        $this->assertSame($next, $this->postJson("/pos/held-carts/{$this->id}", $change)->assertOk()->json());
        $this->postJson("/pos/held-carts/{$this->id}", [...$change, 'label' => 'stale'])->assertUnprocessable()->assertJsonValidationErrors('version');
        $this->getJson('/pos/held-carts')->assertJsonPath('data.0.label', 'Kamera meja B');
    }

    public function test_recall_refreshes_prices_and_preserves_invalid_or_unavailable_lines(): void
    {
        $this->store();
        DB::table('item_prices')->where('item_id', $this->input['lines'][0]['item_id'])->update(['amount' => '500000.00']);
        $this->getJson("/pos/held-carts/{$this->id}")->assertOk()->assertJsonPath('quote.subtotal', '1000000.00');
        DB::table('stock_balances')->where('item_id', $this->input['lines'][0]['item_id'])->update(['quantity' => '0']);
        $this->getJson("/pos/held-carts/{$this->id}")->assertOk()->assertJsonPath('quote', null)
            ->assertJsonCount(1, 'cart.lines')->assertJsonStructure(['errors']);
        DB::table('items')->where('id', $this->input['lines'][0]['item_id'])->update(['is_active' => false]);
        $this->getJson("/pos/held-carts/{$this->id}")->assertOk()->assertJsonPath('quote', null)
            ->assertJsonPath('cart.lines.0.item.name', 'Kamera IP 2MP')->assertJsonPath('cart.lines.0.unitId', $this->input['lines'][0]['unit_id']);
        DB::table('customers')->where('id', $this->input['customer_id'])->update(['is_active' => false]);
        $this->getJson("/pos/held-carts/{$this->id}")->assertOk()->assertJsonCount(1, 'cart.lines')->assertJsonPath('quote', null);
    }

    public function test_cancel_is_soft_delete_audited_and_rejects_stale_checkout(): void
    {
        $cart = $this->store();
        $checkout = $this->checkout($cart);
        $before = $this->ledger();
        $this->postJson("/pos/held-carts/{$this->id}/cancel", ['version' => 0])->assertUnprocessable();
        $this->postJson("/pos/held-carts/{$this->id}/cancel", ['version' => 1])->assertOk();
        $audit = DB::table('activity_log')->count();
        $this->postJson("/pos/held-carts/{$this->id}/cancel", ['version' => 1])->assertOk();
        $this->assertSame($audit, DB::table('activity_log')->count());
        $this->assertSoftDeleted('pos_held_carts', ['id' => $this->id]);
        $this->getJson("/pos/held-carts/{$this->id}")->assertNotFound();
        $this->postJson('/pos/invoices', $checkout)->assertUnprocessable()->assertJsonValidationErrors('held_cart');
        $this->assertSame($before, $this->ledger());
    }

    public function test_linked_checkout_is_atomic_once_and_original_key_can_replay_after_completion(): void
    {
        $input = $this->checkout($this->store());
        $first = $this->postJson('/pos/invoices', $input)->assertCreated()->json();
        $this->assertSame($first, $this->postJson('/pos/invoices', $input)->assertCreated()->json());
        $this->postJson('/pos/invoices', [...$input, 'idempotency_key' => (string) Str::ulid()])
            ->assertUnprocessable()->assertJsonValidationErrors('held_cart');
        $this->assertDatabaseCount('sales_invoices', 1);
        $this->assertDatabaseCount('stock_issues', 1);
        $this->assertDatabaseCount('payment_receipts', 1);
        $this->assertDatabaseHas('pos_held_carts', ['id' => $this->id, 'invoice_id' => $first['id']]);
        $this->getJson('/pos/held-carts')->assertJsonCount(0, 'data');
    }

    public function test_payment_and_held_audit_failures_roll_back_invoice_stock_money_and_source(): void
    {
        $input = $this->checkout($this->store());
        $before = $this->ledger();
        $this->postJson('/pos/invoices', [...$input, 'payments' => [['method_id' => $input['payments'][0]['method_id'], 'amount' => '1']]])->assertUnprocessable();
        $this->assertSame($before, $this->ledger());
        Activity::creating(function ($log): void {
            if ($log->log_name === 'sales.pos.held_carts' && $log->event === 'completed') {
                throw new \RuntimeException('Simulated held audit failure');
            }
        });
        $this->withoutExceptionHandling();
        try {
            $this->postJson('/pos/invoices', $input);
            $this->fail('Audit must be atomic.');
        } catch (\RuntimeException $error) {
            $this->assertSame('Simulated held audit failure', $error->getMessage());
        }
        $this->assertSame($before, $this->ledger());
        $this->assertDatabaseHas('pos_held_carts', ['id' => $this->id, 'invoice_id' => null, 'version' => 1]);
    }
}
