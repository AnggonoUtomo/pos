<?php

namespace Tests\Feature\Modules\Inventory\Stock;

use App\Models\User;
use App\Modules\Inventory\Items\Application\Actions\CreateItem;
use App\Modules\Inventory\Items\Application\DTOs\CreateItemData;
use App\Modules\Inventory\Warehouses\Infrastructure\Persistence\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

class StockPostingTest extends StockTestCase
{
    public function test_posting_is_exact_idempotent_and_posted_cannot_be_mutated(): void
    {
        $id = $this->draft();
        $url = "/inventory/stock/openings/{$id}/post";
        $this->postJson($url)->assertOk();
        $this->postJson($url)->assertOk();
        $this->assertDatabaseHas('stock_balances', ['warehouse_id' => $this->warehouse, 'item_id' => $this->item, 'quantity' => '24.000000', 'total_value' => '240000.000000']);
        $this->assertDatabaseHas('fifo_layers', ['remaining_quantity' => '24.000000', 'original_value' => '240000.000000', 'remaining_value' => '240000.000000']);
        foreach (['stock_balances', 'stock_movements', 'fifo_layers'] as $table) {
            $this->assertDatabaseCount($table, 1);
        }
        $this->assertSame(1, Activity::where('log_name', 'inventory.stock')->where('event', 'posted')->count());
        $this->putJson("/inventory/stock/openings/{$id}", $this->payload())->assertUnprocessable();
        $this->deleteJson("/inventory/stock/openings/{$id}")->assertUnprocessable();
        $this->postJson("/inventory/stock/openings/{$id}/restore")->assertNotFound();
        $this->assertDatabaseHas('stock_openings', ['id' => $id, 'status' => 'posted', 'posted_by' => $this->manager->id]);
    }

    public function test_second_opening_same_pair_is_rejected_but_other_warehouse_is_independent(): void
    {
        $id = $this->draft();
        $this->postJson("/inventory/stock/openings/{$id}/post")->assertOk();
        $second = $this->draft();
        $this->postJson("/inventory/stock/openings/{$second}/post")->assertUnprocessable();
        $other = Warehouse::create(['code' => 'SECOND', 'name' => 'Gudang lain', 'is_active' => true])->id;
        $payload = $this->payload();
        $payload['warehouse_id'] = $other;
        $third = $this->postJson('/inventory/stock/openings', $payload)->assertCreated()->json('id');
        $this->postJson("/inventory/stock/openings/{$third}/post")->assertOk();
        $this->assertDatabaseCount('stock_balances', 2);
        $this->assertDatabaseCount('fifo_layers', 2);
        $this->assertDatabaseHas('stock_openings', ['id' => $second, 'status' => 'draft']);
    }

    public function test_stale_conversion_and_inactive_master_are_rejected(): void
    {
        $id = $this->draft();
        DB::table('item_units')->where('item_id', $this->item)->where('unit_id', $this->box)->update(['conversion_ratio' => '24']);
        $this->postJson("/inventory/stock/openings/{$id}/post")->assertUnprocessable();
        $this->putJson("/inventory/stock/openings/{$id}", $this->payload())->assertOk();
        DB::table('items')->where('id', $this->item)->update(['is_active' => false]);
        $this->postJson("/inventory/stock/openings/{$id}/post")->assertUnprocessable();
        $this->assertDatabaseCount('stock_balances', 0);
    }

    public function test_post_audit_failure_rolls_back_every_line_and_retry_is_safe(): void
    {
        $item = app(CreateItem::class)->handle(new CreateItemData('OTHER', 'Barang kedua', $this->base));
        $payload = $this->payload();
        $payload['lines'][] = ['item_id' => $item, 'unit_id' => $this->base, 'quantity' => '1', 'unit_cost' => '7'];
        $id = $this->postJson('/inventory/stock/openings', $payload)->assertCreated()->json('id');
        Activity::creating(static function (Activity $activity): void {
            if ($activity->log_name === 'inventory.stock' && $activity->event === 'posted') {
                throw new \RuntimeException('Audit posting QA gagal.');
            }
        });
        try {
            $this->postJson("/inventory/stock/openings/{$id}/post")->assertStatus(500);
            foreach (['stock_balances', 'stock_movements', 'fifo_layers'] as $table) {
                $this->assertDatabaseCount($table, 0);
            }
            $this->assertDatabaseHas('stock_openings', ['id' => $id, 'status' => 'draft', 'posted_at' => null]);
        } finally {
            Activity::flushEventListeners();
        }
        $this->postJson("/inventory/stock/openings/{$id}/post")->assertOk();
        $this->assertDatabaseCount('fifo_layers', 2);
    }

    public function test_manage_permission_does_not_allow_post_and_super_still_obeys_business_rule(): void
    {
        $id = $this->draft();
        $editor = User::factory()->create();
        $editor->givePermissionTo('inventory.stock.manage');
        $this->actingAs($editor)->postJson("/inventory/stock/openings/{$id}/post")->assertForbidden();
        $editor->assignRole(Role::findOrCreate('super-system', 'web'));
        $this->postJson("/inventory/stock/openings/{$id}/post")->assertOk();
        $second = $this->draft();
        $this->postJson("/inventory/stock/openings/{$second}/post")->assertUnprocessable();
    }
}
