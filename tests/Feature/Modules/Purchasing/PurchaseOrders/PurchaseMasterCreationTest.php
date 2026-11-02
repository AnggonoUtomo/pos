<?php

namespace Tests\Feature\Modules\Purchasing\PurchaseOrders;

use App\Models\User;
use App\Modules\Sales\Parties\Infrastructure\Persistence\Models\CustomerLevel;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\Feature\Modules\Inventory\Stock\StockTestCase;

class PurchaseMasterCreationTest extends StockTestCase
{
    public function test_purchase_manager_can_resolve_active_choice_but_create_options_require_inventory_manage(): void
    {
        Permission::findOrCreate('purchasing.purchase-orders.manage', 'web');
        $user = User::factory()->create()->givePermissionTo('purchasing.purchase-orders.manage');
        $this->actingAs($user)->getJson('/purchasing/purchase-orders/master-choice/item/'.$this->item)->assertOk()
            ->assertJsonPath('id', $this->item)->assertJsonFragment(['unit_id' => $this->box]);
        $this->getJson('/purchasing/purchase-orders/master-options')->assertForbidden();
        $this->actingAs($this->manager->givePermissionTo('purchasing.purchase-orders.manage'))
            ->getJson('/purchasing/purchase-orders/master-options')->assertOk()->assertJsonCount(2, 'units');
        $this->assertDatabaseCount('purchase_orders', 0);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_inline_master_endpoints_keep_create_permissions_and_do_not_save_a_purchase(): void
    {
        $this->manager->givePermissionTo(Permission::findOrCreate('purchasing.purchase-orders.manage', 'web'));
        $this->manager->givePermissionTo(Permission::findOrCreate('purchasing.suppliers.manage', 'web'));
        $supplier = $this->postJson('/purchasing/suppliers', ['code' => 'INLINE-S', 'name' => 'Supplier baru', 'is_active' => true])->assertCreated()->json('id');
        $this->getJson('/purchasing/purchase-orders/master-choice/supplier/'.$supplier)->assertOk()->assertExactJson(['id' => $supplier, 'code' => 'INLINE-S', 'name' => 'Supplier baru']);
        $level = CustomerLevel::create(['code' => 'MEMBER', 'name' => 'Member']);
        $input = ['sku' => 'INLINE-ITEM', 'name' => 'Barang baru', 'base_unit_id' => $this->base, 'is_active' => true,
            'units' => [['unit_id' => $this->base, 'conversion_ratio' => '1', 'is_sale_unit' => true, 'is_purchase_unit' => true]],
            'prices' => [['unit_id' => $this->base, 'customer_level_id' => $level->id, 'amount' => '10000']]];
        $item = $this->postJson('/inventory/items', $input)->assertCreated()->json('id');
        $this->getJson('/purchasing/purchase-orders/master-choice/item/'.$item)->assertOk()->assertJsonPath('sku', 'INLINE-ITEM');
        $this->postJson('/inventory/items', $input)->assertUnprocessable();
        $viewer = User::factory()->create()->givePermissionTo('purchasing.purchase-orders.manage');
        $this->actingAs($viewer)->postJson('/purchasing/suppliers', [])->assertForbidden();
        $this->postJson('/inventory/items', $input)->assertForbidden();
        foreach (['purchase_orders', 'stock_movements', 'fifo_layers', 'payment_records'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
    }

    public function test_choice_rejects_inactive_or_nonpurchase_master_and_readonly_users(): void
    {
        $this->manager->givePermissionTo(Permission::findOrCreate('purchasing.purchase-orders.manage', 'web'));
        $url = '/purchasing/purchase-orders/master-choice/item/'.$this->item;
        DB::table('item_units')->where('item_id', $this->item)->update(['is_purchase_unit' => false]);
        $this->getJson($url)->assertUnprocessable();
        DB::table('items')->where('id', $this->item)->update(['is_active' => false]);
        $this->getJson($url)->assertUnprocessable();
        $this->actingAs(User::factory()->create())->getJson($url)->assertForbidden();
        $this->getJson('/purchasing/purchase-orders/master-options')->assertForbidden();
    }
}
