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
use App\Modules\Sales\POS\Application\Actions\PostInvoice;
use App\Modules\Sales\POS\Application\DTOs\CartLineData;
use App\Modules\Sales\POS\Application\Queries\CheckCart;
use App\Modules\Sales\POS\Database\Seeders\POSAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InvoiceHistoryTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;

    private User $other;

    private User $viewer;

    private array $invoices;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed([POSAccessSeeder::class, WarehousesDemoSeeder::class, UnitsDemoSeeder::class, ItemsDemoSeeder::class,
            PartiesDemoSeeder::class, ItemPricesDemoSeeder::class, StockDemoSeeder::class, PaymentsDemoSeeder::class]);
        $this->cashier = User::factory()->create()->givePermissionTo('sales.pos.invoices.post');
        $this->other = User::factory()->create()->givePermissionTo('sales.pos.invoices.post');
        $this->viewer = User::factory()->create()->givePermissionTo('sales.pos.invoices.view');
        $this->invoices = [
            $this->sale($this->cashier, 'DEMO-INSTALLER', '2026-10-15 17:00:00'),
            $this->sale($this->cashier, 'DEMO-INSTALLER', '2026-10-16 16:59:59'),
            $this->sale($this->other, 'DEMO-GROSIR', '2026-10-16 17:00:00'),
        ];
    }

    private function sale(User $actor, string $customerCode, string $postedAt): array
    {
        $item = DB::table('items')->where('sku', 'DEMO-CCTV-IP-2MP')->first();
        $warehouse = DB::table('warehouses')->where('code', 'WH-MAIN')->value('id');
        $customer = DB::table('customers')->where('code', $customerCode)->value('id');
        $quote = app(CheckCart::class)->handle($warehouse, $customer, [new CartLineData($item->id, $item->base_unit_id, '0.1')]);
        $invoice = app(PostInvoice::class)->handle(['idempotency_key' => strtolower((string) Str::ulid()), 'warehouse_id' => $warehouse,
            'customer_id' => $customer, 'quote_fingerprint' => $quote['fingerprint'],
            'lines' => [['item_id' => $item->id, 'unit_id' => $item->base_unit_id, 'quantity' => '0.1']],
            'payments' => [['method_id' => DB::table('payment_methods')->where('code', 'TUNAI')->value('id'), 'amount' => $quote['subtotal']]]], $actor->id);
        DB::table('sales_invoices')->where('id', $invoice['id'])->update(['posted_at' => $postedAt]);

        return $invoice;
    }

    public function test_auth_and_ownership_apply_to_rows_counts_choices_and_detail(): void
    {
        $this->get('/sales/invoices')->assertRedirect('/login');
        $this->getJson('/sales/invoices/data')->assertUnauthorized();
        foreach ([User::factory()->create(), User::factory()->create()->givePermissionTo('sales.pos.invoices.create')] as $outsider) {
            $this->actingAs($outsider)->getJson('/sales/invoices/data')->assertForbidden();
            $this->get('/sales/invoices')->assertForbidden();
        }
        $result = $this->actingAs($this->cashier)->getJson('/sales/invoices/data')->assertOk()
            ->assertJsonPath('records.meta.total', 2)->assertJsonPath('viewAll', false)->json();
        $this->assertSame([$this->invoices[1]['id'], $this->invoices[0]['id']], array_column($result['records']['data'], 'id'));
        $this->assertSame([$this->invoices[0]['customer_id']], array_column($result['customerOptions'], 'id'));
        $this->getJson('/pos/invoices/'.$this->invoices[2]['id'])->assertForbidden();
        $this->getJson('/sales/invoices/data?customer_id='.$this->invoices[2]['customer_id'])->assertOk()->assertJsonPath('records.meta.total', 0);
        $this->actingAs($this->viewer)->getJson('/sales/invoices/data')->assertOk()->assertJsonPath('records.meta.total', 3)
            ->assertJsonPath('viewAll', true)->assertJsonCount(2, 'customerOptions');
        $this->getJson('/pos/invoices/'.$this->invoices[2]['id'])->assertOk();
        $super = User::factory()->create()->assignRole(Role::findOrCreate('super-system', 'web'));
        $this->actingAs($super)->getJson('/sales/invoices/data')->assertOk()->assertJsonPath('records.meta.total', 3);
    }

    public function test_filters_use_wib_inclusive_days_literal_search_and_historical_snapshots(): void
    {
        $this->actingAs($this->viewer)->getJson('/sales/invoices/data?date_from=2026-10-16&date_to=2026-10-16')
            ->assertOk()->assertJsonPath('records.meta.total', 2);
        $this->getJson('/sales/invoices/data?date_from=2026-10-17')->assertOk()->assertJsonPath('records.meta.total', 1);
        $this->getJson('/sales/invoices/data?date_to=2026-10-15')->assertOk()->assertJsonPath('records.meta.total', 0);
        $number = strtolower(substr($this->invoices[0]['number'], 5));
        $this->getJson('/sales/invoices/data?search='.$number)->assertOk()->assertJsonPath('records.meta.total', 1);
        foreach (['%', '_', "' OR 1=1 --"] as $literal) {
            $this->getJson('/sales/invoices/data?'.http_build_query(['search' => $literal]))->assertOk()->assertJsonPath('records.meta.total', 0);
        }
        DB::table('customers')->where('id', $this->invoices[0]['customer_id'])->update(['name' => 'Nama berubah', 'is_active' => false, 'deleted_at' => now()]);
        DB::table('warehouses')->where('id', $this->invoices[0]['warehouse_id'])->update(['name' => 'Gudang berubah', 'is_active' => false, 'deleted_at' => now()]);
        $query = http_build_query(['customer_id' => strtoupper($this->invoices[0]['customer_id']), 'warehouse_id' => $this->invoices[0]['warehouse_id']]);
        $this->getJson('/sales/invoices/data?'.$query)->assertOk()->assertJsonPath('records.meta.total', 2)
            ->assertJsonPath('records.data.0.customer_name', 'Installer CCTV Demo')
            ->assertJsonPath('warehouseOptions.0.name', 'Gudang Utama')->assertJsonPath('customerOptions.0.name', 'Installer CCTV Demo');
        $this->getJson('/sales/invoices/data?warehouse_id='.(string) Str::ulid())->assertOk()->assertJsonPath('records.meta.total', 0);
    }

    public function test_read_is_private_exact_and_does_not_write_ledger_or_audit(): void
    {
        $tables = ['sales_invoices', 'sales_invoice_lines', 'stock_balances', 'stock_movements', 'fifo_layers', 'fifo_consumptions',
            'stock_issues', 'stock_issue_lines', 'payment_receipts', 'payment_records', 'activity_log'];
        $snapshot = fn () => array_map(fn ($table) => DB::table($table)->orderBy('id')->get()->toJson(), $tables);
        $before = $snapshot();
        DB::table('sales_invoices')->where('id', $this->invoices[0]['id'])->update(['total' => '9007199254740993.01']);
        $largeBefore = $snapshot();
        $response = $this->actingAs($this->cashier)->getJson('/sales/invoices/data')->assertOk();
        $row = collect($response->json('records.data'))->firstWhere('id', $this->invoices[0]['id']);
        $this->assertSame('9007199254740993.01', $row['total']);
        foreach (['total_cost', 'request_hash', 'idempotency_key', 'actor_id', 'account_number', 'account_holder', 'phone', 'email', 'address', 'bank_name'] as $private) {
            $this->assertStringNotContainsString('"'.$private.'"', $response->getContent());
        }
        $this->assertSame($largeBefore, $snapshot());
        $this->get('/sales/invoices')->assertOk()->assertInertia(fn (Assert $page) => $page->component('sales/pos/history/index', false)
            ->where('viewAll', false)->has('records.data', 2));
        $this->getJson('/pos/invoices/'.$this->invoices[1]['id'])->assertOk();
        $this->assertSame($largeBefore, $snapshot());
        $this->assertNotSame($before, $largeBefore); // The only change was the explicit precision fixture.
    }

    public function test_pagination_order_is_stable_and_scoped(): void
    {
        for ($i = 0; $i < 25; $i++) {
            $this->sale($this->cashier, 'DEMO-INSTALLER', '2026-10-18 00:00:00');
        }
        $one = $this->actingAs($this->cashier)->getJson('/sales/invoices/data')->assertOk()->assertJsonPath('records.meta.total', 27)
            ->assertJsonPath('records.meta.last_page', 2)->assertJsonCount(25, 'records.data')->json('records.data');
        $two = $this->getJson('/sales/invoices/data?page=2')->assertOk()->assertJsonCount(2, 'records.data')->json('records.data');
        $ids = array_merge(array_column($one, 'id'), array_column($two, 'id'));
        $this->assertCount(27, array_unique($ids));
        $this->assertSame(DB::table('sales_invoices')->where('actor_id', $this->cashier->id)->orderByDesc('posted_at')->orderByDesc('id')->pluck('id')->all(), $ids);
    }

    #[DataProvider('invalidFilters')]
    public function test_invalid_filters_are_rejected(array $input, string $field): void
    {
        $this->actingAs($this->cashier)->getJson('/sales/invoices/data?'.http_build_query($input))->assertUnprocessable()->assertJsonValidationErrors($field);
    }

    public static function invalidFilters(): array
    {
        return [
            [['date_from' => '2026-02-30'], 'date_from'], [['date_to' => 'yesterday'], 'date_to'],
            [['date_from' => '2026-10-18', 'date_to' => '2026-10-17'], 'date_to'],
            [['warehouse_id' => 'bad'], 'warehouse_id'], [['customer_id' => ['bad']], 'customer_id'],
            [['page' => 0], 'page'], [['search' => str_repeat('x', 151)], 'search'],
            [['actor_id' => 99], 'filters'], [['viewAll' => true], 'filters'],
        ];
    }
}
