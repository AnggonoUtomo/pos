<?php

namespace Tests\Feature\Modules\Inventory\Stock;

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

class StockReadTest extends StockTestCase
{
    public function test_workspace_and_reads_use_stock_snapshot_and_permission(): void
    {
        $this->withoutVite();
        $id = $this->draft();
        $this->get('/inventory/stock')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('inventory/stock/index')->where('filters.tab', 'openings')->has('records.data', 1)
            ->where('records.data.0.lines.0.base_quantity', '24.000000')->has('options.items', 1));
        $this->getJson('/inventory/stock/data?tab=balances')->assertOk()->assertJsonPath('meta.total', 0);
        $this->postJson("/inventory/stock/openings/{$id}/post")->assertOk();
        $this->getJson('/inventory/stock/data?tab=balances')->assertOk()->assertJsonPath('data.0.quantity', '24.000000')->assertJsonPath('data.0.base_unit_name', 'Buah');
        $this->getJson('/inventory/stock/data?tab=movements')->assertOk()->assertJsonPath('data.0.type', 'opening')->assertJsonPath('data.0.number', 'SA-'.$id);
        $this->getJson('/inventory/stock/data?tab=movements&search=NO-MATCH')->assertOk()->assertJsonPath('meta.total', 0);
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('inventory.stock.view');
        $this->actingAs($viewer)->get('/inventory/stock')->assertOk();
        $none = User::factory()->create();
        $this->actingAs($none)->get('/inventory/stock')->assertForbidden();
        $this->getJson('/inventory/stock/data')->assertForbidden();
    }

    public function test_filter_validation_pagination_and_deleted_drafts(): void
    {
        $id = $this->draft();
        $this->deleteJson("/inventory/stock/openings/{$id}")->assertOk();
        $this->getJson('/inventory/stock/data')->assertOk()->assertJsonPath('meta.total', 0);
        $this->getJson('/inventory/stock/data?trashed=1')->assertOk()->assertJsonPath('meta.total', 1);
        foreach (['tab=wrong', 'page=0', 'warehouse_id=wrong', 'status=wrong', 'trashed=bad'] as $query) {
            $this->getJson('/inventory/stock/data?'.$query)->assertUnprocessable();
        }
        $this->getJson('/inventory/stock/data?page=2147483647')->assertOk()->assertJsonPath('data', []);
    }
}
