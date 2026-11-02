<?php

namespace Tests\Feature\Modules\Inventory\Items;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Modules\Inventory\Stock\StockTestCase;

class ItemClassificationsTest extends StockTestCase
{
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
