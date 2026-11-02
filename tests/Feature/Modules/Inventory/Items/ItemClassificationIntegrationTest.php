<?php

namespace Tests\Feature\Modules\Inventory\Items;

use App\Models\User;
use App\Modules\Inventory\Items\Application\Actions\ManageItem;
use App\Modules\Inventory\Items\Application\Contracts\SaleItemCatalog;
use App\Modules\Inventory\Items\Application\DTOs\CreateItemData;
use App\Modules\Inventory\Items\Infrastructure\Persistence\Models\Item;
use App\Modules\Sales\Parties\Infrastructure\Persistence\Models\CustomerLevel;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\Feature\Modules\Inventory\Stock\StockTestCase;
use Tests\Fixtures\SalesReturnFixture;

class ItemClassificationIntegrationTest extends StockTestCase
{
    public function test_pos_filters_and_scan_keep_original_stock_price_and_invoice_fingerprints(): void
    {
        $this->manager->givePermissionTo(Permission::findOrCreate('sales.pos.invoices.create', 'web'));
        $invoice = SalesReturnFixture::seed();
        $before = SalesReturnFixture::fingerprint();
        $category = $this->postJson('/inventory/categories', ['code' => 'CCTV', 'name' => 'CCTV'])->assertCreated()->json('id');
        $other = $this->postJson('/inventory/categories', ['code' => 'ELEC', 'name' => 'Kelistrikan'])->assertCreated()->json('id');
        $item = $invoice['lines'][0]['item_id'];
        DB::table('items')->where('id', $item)->update(['category_id' => $category]);
        $context = ['warehouse_id' => $invoice['warehouse_id'], 'customer_id' => $invoice['customer_id'], 'search' => 'DEMO-CCTV-IP-2MP'];
        $this->getJson('/pos/catalog?'.http_build_query([...$context, 'category_id' => $category]))->assertOk()->assertJsonCount(1, 'items');
        $this->getJson('/pos/catalog?'.http_build_query([...$context, 'category_id' => $other]))->assertOk()->assertJsonCount(0, 'items');
        $this->getJson('/pos/catalog?'.http_build_query([...$context, 'category_id' => 'invalid']))->assertUnprocessable();
        $this->getJson('/pos/scan?'.http_build_query(['warehouse_id' => $invoice['warehouse_id'], 'customer_id' => $invoice['customer_id'], 'code' => 'DEMO-CCTV-IP-2MP']))->assertOk()->assertJsonPath('item.id', $item);
        $this->assertSame($before, SalesReturnFixture::fingerprint());
    }

    public function test_http_create_update_and_catalog_options_preserve_prices_and_permission_boundaries(): void
    {
        $this->withoutVite();
        $level = CustomerLevel::create(['code' => 'QA', 'name' => 'QA']);
        $category = $this->postJson('/inventory/categories', ['code' => 'CCTV', 'name' => 'CCTV'])->assertCreated()->json('id');
        $input = ['sku' => 'NEW', 'name' => 'Barang baru', 'base_unit_id' => $this->base, 'category_id' => $category,
            'units' => [['unit_id' => $this->base, 'conversion_ratio' => '1']],
            'prices' => [['unit_id' => $this->base, 'customer_level_id' => $level->id, 'amount' => '12345']]];
        $id = $this->postJson('/inventory/items', $input)->assertCreated()->json('id');
        unset($input['category_id']);
        $this->putJson('/inventory/items/'.$id, [...$input, 'is_active' => true])->assertOk();
        $this->assertDatabaseHas('items', ['id' => $id, 'category_id' => $category]);
        $this->assertDatabaseHas('item_prices', ['item_id' => $id, 'amount' => '12345.00']);
        $this->putJson('/inventory/items/'.$id, [...$input, 'is_active' => true, 'category_id' => null])->assertOk();
        $this->assertDatabaseHas('items', ['id' => $id, 'category_id' => null]);
        $reader = User::factory()->create()->givePermissionTo('inventory.view');
        $this->actingAs($reader)->get('/inventory/categories')->assertOk();
        $this->postJson('/inventory/categories', [])->assertForbidden();
    }

    public function test_optional_assignments_omit_preserves_and_explicit_null_clears(): void
    {
        $category = $this->postJson('/inventory/categories', ['code' => 'CCTV', 'name' => 'CCTV'])->assertCreated()->json('id');
        $brand = $this->postJson('/inventory/brands', ['code' => 'VISION', 'name' => 'Vision QA'])->assertCreated()->json('id');
        app(ManageItem::class)->update($this->item, new CreateItemData('QA', 'Barang QA', $this->base, classification: ['category_id' => $category, 'brand_id' => $brand]));
        $this->assertDatabaseHas('items', ['id' => $this->item, 'category_id' => $category, 'brand_id' => $brand]);
        app(ManageItem::class)->update($this->item, new CreateItemData('QA', 'Nama baru', $this->base));
        $this->assertDatabaseHas('items', ['id' => $this->item, 'category_id' => $category, 'brand_id' => $brand]);
        app(ManageItem::class)->update($this->item, new CreateItemData('QA', 'Nama baru', $this->base, classification: ['category_id' => null]));
        $this->assertDatabaseHas('items', ['id' => $this->item, 'category_id' => null, 'brand_id' => $brand]);
        $this->assertDatabaseHas('activity_log', ['subject_id' => $this->item, 'log_name' => 'inventory.items', 'event' => 'updated']);
    }

    public function test_inactive_wrong_kind_or_deleted_masters_rejected_without_mutation(): void
    {
        CustomerLevel::create(['code' => 'QA', 'name' => 'QA']);
        $brand = $this->postJson('/inventory/brands', ['code' => 'INACTIVE', 'name' => 'Nonaktif', 'is_active' => false])->assertCreated()->json('id');
        foreach (['category_id', 'brand_id'] as $field) {
            $this->putJson('/inventory/items/'.$this->item, ['sku' => 'QA', 'name' => 'Barang QA', 'base_unit_id' => $this->base, 'is_active' => true,
                $field => $brand])->assertUnprocessable()->assertJsonValidationErrors($field);
        }
        $this->assertDatabaseHas('items', ['id' => $this->item, 'category_id' => null, 'brand_id' => null]);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_catalog_and_admin_filter_by_category_and_brand_without_hiding_unclassified_items(): void
    {
        $category = $this->postJson('/inventory/categories', ['code' => 'CCTV', 'name' => 'CCTV'])->assertCreated()->json('id');
        $brand = $this->postJson('/inventory/brands', ['code' => 'VISION', 'name' => 'Vision QA'])->assertCreated()->json('id');
        Item::findOrFail($this->item)->update(['category_id' => $category, 'brand_id' => $brand]);
        $catalog = app(SaleItemCatalog::class);
        $this->assertCount(1, $catalog->search('QA', 1, ['category_id' => $category, 'brand_id' => $brand])['data']);
        $other = $this->postJson('/inventory/categories', ['code' => 'ELEC', 'name' => 'Kelistrikan'])->assertCreated()->json('id');
        $this->assertSame([], $catalog->search('QA', 1, ['category_id' => $other])['data']);
        $this->get('/inventory/items?category_id='.$other)->assertOk()->assertInertia(fn ($page) => $page->has('records.data', 0));
        $this->get('/inventory/items?category_id='.$category.'&brand_id='.$brand)->assertOk()->assertInertia(fn ($page) => $page->has('records.data', 1));
        Item::findOrFail($this->item)->update(['category_id' => null, 'brand_id' => null]);
        $this->assertCount(1, $catalog->search('QA', 1)['data']);
        $this->assertDatabaseCount('fifo_layers', 0);
    }
}
