<?php

namespace Tests\Feature\Modules\Inventory\Items;

use App\Models\User;
use App\Modules\Inventory\Items\Database\Seeders\ItemClassificationsDemoSeeder;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Modules\Inventory\Stock\StockTestCase;

class ItemClassificationsTest extends StockTestCase
{
    public function test_master_list_search_status_and_shared_pagination(): void
    {
        $this->withoutVite();
        for ($number = 1; $number <= 12; $number++) {
            $this->postJson('/inventory/categories', ['code' => sprintf('PAGE-%02d', $number), 'name' => 'Kategori halaman', 'is_active' => true])->assertCreated();
        }
        $this->get('/inventory/categories?search=halaman&per_page=10&page=2')->assertOk()->assertInertia(fn ($page) => $page
            ->component('inventory/items/categories')->has('records.data', 2)->where('records.meta.total', 12)->where('records.meta.per_page', 10));
        $this->get('/inventory/categories?search=halaman&status=inactive')->assertOk()->assertInertia(fn ($page) => $page->has('records.data', 0));
    }

    public function test_demo_is_explicit_idempotent_and_preserves_edits_deleted_rows_and_existing_items(): void
    {
        $seeder = ItemClassificationsDemoSeeder::class;
        $this->seed($seeder);
        DB::table('item_categories')->where('code', 'DEMO-CCTV')->update(['name' => 'Nama user', 'deleted_at' => now()]);
        $this->seed($seeder);
        $this->assertDatabaseCount('item_categories', 2);
        $this->assertDatabaseCount('item_brands', 2);
        $this->assertDatabaseHas('item_categories', ['code' => 'DEMO-CCTV', 'name' => 'Nama user']);
        $this->assertSoftDeleted('item_categories', ['code' => 'DEMO-CCTV']);
        $this->assertDatabaseHas('items', ['id' => $this->item, 'category_id' => null, 'brand_id' => null]);
        $this->assertDatabaseCount('items', 1);
    }

    public function test_category_and_brand_crud_audit_reserved_codes_and_permissions(): void
    {
        foreach (['categories' => 'item_categories', 'brands' => 'item_brands'] as $kind => $table) {
            $url = '/inventory/'.$kind;
            $input = ['code' => 'QA-MASTER', 'name' => 'Master QA', 'is_active' => true];
            $id = $this->postJson($url, $input)->assertCreated()->json('id');
            $this->assertMatchesRegularExpression('/^[0-9a-hjkmnp-tv-z]{26}$/i', $id);
            $this->postJson($url, $input)->assertUnprocessable()->assertJsonValidationErrors('code');
            $this->putJson($url.'/'.$id, [...$input, 'name' => 'Nama baru'])->assertOk();
            $this->deleteJson($url.'/'.$id)->assertOk();
            $this->assertSoftDeleted($table, ['id' => $id]);
            $this->postJson($url, $input)->assertUnprocessable();
            $this->postJson($url.'/'.$id.'/restore')->assertOk();
            $this->assertDatabaseHas($table, ['id' => $id, 'name' => 'Nama baru', 'deleted_at' => null]);
            foreach (['created', 'updated', 'deleted', 'restored'] as $event) {
                $this->assertDatabaseHas('activity_log', ['log_name' => 'inventory.'.$table, 'subject_id' => $id, 'event' => $event]);
            }
        }
        $this->actingAs(User::factory()->create());
        foreach (['categories', 'brands'] as $kind) {
            $this->getJson('/inventory/'.$kind)->assertForbidden();
            $this->postJson('/inventory/'.$kind, [])->assertForbidden();
        }
    }

    public function test_used_masters_cannot_be_deleted_or_deactivated_even_for_deleted_items(): void
    {
        foreach (['categories' => 'category_id', 'brands' => 'brand_id'] as $kind => $field) {
            $input = ['code' => 'USED', 'name' => 'Dipakai', 'is_active' => true];
            $id = $this->postJson('/inventory/'.$kind, $input)->assertCreated()->json('id');
            DB::table('items')->where('id', $this->item)->update([$field => $id, 'deleted_at' => now()]);
            $this->deleteJson('/inventory/'.$kind.'/'.$id)->assertUnprocessable();
            $this->putJson('/inventory/'.$kind.'/'.$id, [...$input, 'is_active' => false])->assertUnprocessable();
            $this->putJson('/inventory/'.$kind.'/'.$id, [...$input, 'name' => 'Rename aman'])->assertOk();
        }
        $this->assertDatabaseCount('stock_movements', 0);
    }
}
