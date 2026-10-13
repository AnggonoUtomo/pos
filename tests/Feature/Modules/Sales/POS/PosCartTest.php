<?php

namespace Tests\Feature\Modules\Sales\POS;

use App\Models\User;
use App\Modules\Inventory\Items\Database\Seeders\ItemsDemoSeeder;
use App\Modules\Inventory\Stock\Database\Seeders\StockDemoSeeder;
use App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder;
use App\Modules\Inventory\Warehouses\Database\Seeders\WarehousesDemoSeeder;
use App\Modules\Sales\Parties\Database\Seeders\ItemPricesDemoSeeder;
use App\Modules\Sales\Parties\Database\Seeders\PartiesDemoSeeder;
use App\Modules\Sales\POS\Database\Seeders\POSAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PosCartTest extends TestCase
{
    use RefreshDatabase;

    private array $context;

    private string $item;

    private string $unit;

    private string $box;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([POSAccessSeeder::class, WarehousesDemoSeeder::class, UnitsDemoSeeder::class, ItemsDemoSeeder::class,
            PartiesDemoSeeder::class, ItemPricesDemoSeeder::class, StockDemoSeeder::class]);
        $this->context = ['warehouse_id' => DB::table('warehouses')->where('code', 'WH-MAIN')->value('id'),
            'customer_id' => DB::table('customers')->where('code', 'DEMO-INSTALLER')->value('id')];
        $this->item = DB::table('items')->where('sku', 'DEMO-CCTV-IP-2MP')->value('id');
        $this->unit = DB::table('items')->where('id', $this->item)->value('base_unit_id');
        $this->box = DB::table('units')->where('code', 'BOX')->value('id');
        $this->actingAs(User::factory()->create()->givePermissionTo('sales.pos.invoices.create'));
    }

    private function line(string $qty = '1', ?string $unit = null): array
    {
        return ['item_id' => $this->item, 'unit_id' => $unit ?? $this->unit, 'quantity' => $qty];
    }

    public function test_catalog_prices_and_quote_use_real_level_unit_and_warehouse_without_mutation(): void
    {
        $before = $this->fingerprint();
        $data = $this->getJson('/pos/catalog?'.http_build_query($this->context + ['search' => 'DEMO-CCTV-IP-2MP-BOX']))
            ->assertOk()->assertJsonCount(1, 'items')->assertJsonPath('items.0.stock', '8.000000')->json();
        $base = collect($data['items'][0]['units'])->firstWhere('id', $this->unit);
        $this->assertSame('617500.00', $base['price']);
        $this->postJson('/pos/quote', $this->context + ['lines' => [$this->line('2', $this->box)]])->assertOk()
            ->assertJsonPath('subtotal', '4940000.00')->assertJsonPath('lines.0.base_quantity', '8.000000');
        $this->assertSame($before, $this->fingerprint());
        $this->context['warehouse_id'] = DB::table('warehouses')->where('code', 'WH-SECONDARY')->value('id');
        $this->postJson('/pos/quote', $this->context + ['lines' => [$this->line()]])->assertUnprocessable();
    }

    public function test_stock_is_aggregated_across_units_and_spoofed_amounts_are_rejected(): void
    {
        $this->postJson('/pos/quote', $this->context + ['lines' => [$this->line('2', $this->box), $this->line()]])
            ->assertUnprocessable()->assertJsonValidationErrors('lines.1.quantity');
        $this->postJson('/pos/quote', $this->context + ['lines' => [$this->line(), $this->line()]])
            ->assertUnprocessable()->assertJsonValidationErrors('lines.1.unit_id');
        $this->postJson('/pos/quote', $this->context + ['price' => '1', 'customer_level_id' => $this->unit, 'lines' => [$this->line()]])
            ->assertUnprocessable()->assertJsonValidationErrors('cart');
        $this->postJson('/pos/quote', $this->context + ['lines' => [$this->line() + ['unit_price' => '1']]])->assertUnprocessable();
    }

    public function test_missing_price_inactive_customer_and_non_sale_unit_are_rejected_even_for_super_system(): void
    {
        $this->actingAs(User::factory()->create()->assignRole(Role::findOrCreate('super-system', 'web')));
        DB::table('item_prices')->where('item_id', $this->item)->where('unit_id', $this->unit)->update(['deleted_at' => now()]);
        $this->postJson('/pos/quote', $this->context + ['lines' => [$this->line()]])->assertUnprocessable()->assertJsonValidationErrors('lines.0.unit_id');
        DB::table('item_units')->where('item_id', $this->item)->where('unit_id', $this->box)->update(['is_sale_unit' => false]);
        $this->postJson('/pos/quote', $this->context + ['lines' => [$this->line('1', $this->box)]])->assertUnprocessable();
        DB::table('customers')->where('id', $this->context['customer_id'])->update(['is_active' => false]);
        $this->getJson('/pos/catalog?'.http_build_query($this->context))->assertUnprocessable()->assertJsonValidationErrors('customer_id');
    }

    public function test_quantity_precision_and_rounding_are_not_silently_changed(): void
    {
        foreach (['0', '-1', '1e2', '1,5', '0.0000001', '999999999999999'] as $qty) {
            $this->postJson('/pos/quote', $this->context + ['lines' => [$this->line($qty)]])->assertUnprocessable();
        }
        DB::table('item_prices')->where('item_id', $this->item)->update(['amount' => '0.01']);
        $this->postJson('/pos/quote', $this->context + ['lines' => [$this->line('0.1')]])->assertUnprocessable();
        DB::table('item_prices')->where('item_id', $this->item)->update(['amount' => '100.25']);
        $this->postJson('/pos/quote', $this->context + ['lines' => [$this->line('1.2')]])->assertOk()->assertJsonPath('subtotal', '120.30');
    }

    public function test_latest_price_and_stock_are_read_again_and_auth_permission_enforced(): void
    {
        $payload = $this->context + ['lines' => [$this->line()]];
        $this->postJson('/pos/quote', $payload)->assertOk()->assertJsonPath('subtotal', '617500.00');
        DB::table('item_prices')->where('item_id', $this->item)->update(['amount' => '900.25']);
        $this->postJson('/pos/quote', $payload)->assertOk()->assertJsonPath('subtotal', '900.25');
        DB::table('stock_balances')->where('item_id', $this->item)->update(['quantity' => '0']);
        $this->postJson('/pos/quote', $payload)->assertUnprocessable();
        $this->actingAs(User::factory()->create());
        $this->get('/pos')->assertForbidden();
        $this->getJson('/pos/catalog?'.http_build_query($this->context))->assertForbidden();
        $this->postJson('/pos/quote', $payload)->assertForbidden();
        auth()->logout();
        $this->postJson('/pos/quote', $payload)->assertUnauthorized();
    }

    private function fingerprint(): array
    {
        return array_map(fn ($table) => DB::table($table)->orderBy('id')->get()->toJson(),
            ['items', 'item_units', 'item_prices', 'customers', 'stock_balances', 'stock_movements', 'fifo_layers', 'activity_log']);
    }
}
