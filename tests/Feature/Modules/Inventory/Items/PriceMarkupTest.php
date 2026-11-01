<?php

namespace Tests\Feature\Modules\Inventory\Items;

use App\Models\User;
use App\Modules\Inventory\Warehouses\Infrastructure\Persistence\Models\Warehouse;
use App\Modules\Purchasing\PurchaseOrders\Application\Actions\ManagePurchaseOrder;
use App\Modules\Purchasing\PurchaseOrders\Application\Actions\PostPurchaseOrder;
use App\Modules\Purchasing\Suppliers\Infrastructure\Persistence\Models\Supplier;
use App\Modules\Sales\Parties\Infrastructure\Persistence\Models\CustomerLevel;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Modules\Inventory\Stock\StockTestCase;

class PriceMarkupTest extends StockTestCase
{
    private function purchase(string $cost, ?string $warehouse = null, bool $post = true): string
    {
        $supplier = Supplier::firstOrCreate(['code' => 'MARKUP-S'], ['name' => 'Supplier', 'is_active' => true]);
        $input = $this->payload() + ['supplier_id' => $supplier->id];
        $input['warehouse_id'] = $warehouse ?? $this->warehouse;
        $input['lines'][0]['unit_cost'] = $cost;
        $id = app(ManagePurchaseOrder::class)->save($input);
        if ($post) {
            app(PostPurchaseOrder::class)->handle($id, 1, $this->manager->id);
        }

        return $id;
    }

    private function body(): array
    {
        $level = CustomerLevel::firstOrCreate(['code' => 'MEMBER'], ['name' => 'Member toko', 'is_active' => true]);

        return ['units' => [['unit_id' => $this->base, 'conversion_ratio' => '1'], ['unit_id' => $this->box, 'conversion_ratio' => '12']],
            'markups' => [['customer_level_id' => $level->id, 'percent' => '20']]];
    }

    public function test_preview_normalizes_purchase_unit_and_uses_dynamic_level_without_saving(): void
    {
        $supplier = Supplier::create(['code' => 'S', 'name' => 'Supplier', 'is_active' => true]);
        $id = app(ManagePurchaseOrder::class)->save($this->payload() + ['supplier_id' => $supplier->id]);
        app(PostPurchaseOrder::class)->handle($id, 1, $this->manager->id);
        $level = CustomerLevel::create(['code' => 'MEMBER', 'name' => 'Member toko', 'is_active' => true]);
        $this->postJson('/inventory/items/'.$this->item.'/price-preview', [
            'units' => [['unit_id' => $this->base, 'conversion_ratio' => '1'], ['unit_id' => $this->box, 'conversion_ratio' => '12']],
            'markups' => [['customer_level_id' => $level->id, 'percent' => '20']],
        ])->assertOk()->assertJsonPath('prices.0.amount', '12000.00')->assertJsonPath('prices.1.amount', '144000.00');
        $this->assertDatabaseCount('item_prices', 0);
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_latest_received_across_warehouses_not_draft_and_no_mutation(): void
    {
        $this->purchase('120000');
        $other = Warehouse::create(['code' => 'MARKUP-W', 'name' => 'Gudang kedua', 'is_active' => true]);
        $this->travel(1)->minutes();
        $latest = $this->purchase('240000', $other->id);
        $this->travel(1)->minutes();
        $this->purchase('360000', post: false);
        $body = $this->body();
        $snapshot = fn () => DB::table('activity_log')->orderBy('id')->get()->toJson().DB::table('stock_balances')->orderBy('id')->get()->toJson()
            .DB::table('fifo_layers')->orderBy('id')->get()->toJson().DB::table('purchase_orders')->orderBy('id')->get()->toJson();
        $before = $snapshot();
        $this->postJson('/inventory/items/'.$this->item.'/price-preview', $body)->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')->assertJsonPath('prices.0.amount', '24000.00')
            ->assertJsonPath('reference.number', DB::table('purchase_orders')->where('id', $latest)->value('number'));
        $this->assertSame($before, $snapshot());
        $this->assertDatabaseCount('item_prices', 0);
    }

    public function test_no_purchase_and_unproven_receipt_return_no_reference(): void
    {
        $url = '/inventory/items/'.$this->item.'/price-preview';
        $body = $this->body();
        $this->postJson($url, $body)->assertOk()->assertExactJson(['reference' => null, 'prices' => []]);
        $id = $this->purchase('120000');
        $receipt = DB::table('purchase_orders')->where('id', $id)->value('stock_receipt_id');
        DB::table('stock_receipt_lines')->where('receipt_id', $receipt)->update(['total_value' => '1.000000']);
        $this->postJson($url, $body)->assertOk()->assertExactJson(['reference' => null, 'prices' => []]);
    }

    public function test_permissions_csrf_validation_and_inactive_master(): void
    {
        $this->purchase('120000');
        $url = '/inventory/items/'.$this->item.'/price-preview';
        $body = $this->body();
        $viewer = User::factory()->create()->givePermissionTo('inventory.view');
        $this->actingAs($viewer)->postJson($url, $body)->assertForbidden();
        $this->actingAs($this->manager);
        foreach (['-1', '1,5', '1.001', '1000000', '1e2'] as $percent) {
            $invalid = $body;
            $invalid['markups'][0]['percent'] = $percent;
            $this->postJson($url, $invalid)->assertUnprocessable();
        }
        $invalid = $body;
        $invalid['units'][0]['conversion_ratio'] = '2';
        $this->postJson($url, $invalid)->assertUnprocessable();
        DB::table('customer_levels')->where('id', $body['markups'][0]['customer_level_id'])->update(['is_active' => false]);
        $this->postJson($url, $body)->assertUnprocessable();
        $this->app->detectEnvironment(fn () => 'local');
        try {
            $this->postJson($url, $body)->assertStatus(419);
        } finally {
            $this->app->detectEnvironment(fn () => 'testing');
        }
        $this->assertDatabaseCount('item_prices', 0);
    }

    public function test_any_number_of_active_level_names_are_used_without_installer_requirement(): void
    {
        $this->purchase('120000');
        $body = $this->body();
        foreach (['REGULAR' => 'Reguler', 'RESELLER' => 'Reseller', 'MEMBER_PLUS' => 'Member Plus'] as $code => $name) {
            $level = CustomerLevel::create(['code' => $code, 'name' => $name]);
            $body['markups'][] = ['customer_level_id' => $level->id, 'percent' => '0'];
        }
        $this->postJson('/inventory/items/'.$this->item.'/price-preview', $body)->assertOk()->assertJsonCount(8, 'prices');
        $this->withoutVite()->get('/inventory/items')->assertOk()->assertInertia(fn ($p) => $p->has('priceLevels', 4));
        $this->assertDatabaseCount('item_prices', 0);
        $this->assertDatabaseMissing('customer_levels', ['code' => 'INSTALLER']);
    }
}
