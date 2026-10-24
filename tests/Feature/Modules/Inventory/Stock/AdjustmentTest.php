<?php

namespace Tests\Feature\Modules\Inventory\Stock;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

class AdjustmentTest extends StockTestCase
{
    private function input(string $direction = 'in'): array
    {
        Permission::findOrCreate('inventory.stock.adjust', 'web');
        $this->manager->givePermissionTo('inventory.stock.adjust');

        return ['idempotency_key' => strtolower((string) Str::ulid()), 'warehouse_id' => $this->warehouse,
            'item_id' => $this->item, 'direction' => $direction, 'quantity' => '2.5',
            'unit_cost' => $direction === 'in' ? '10000' : null, 'reason' => 'Selisih hasil hitung fisik'];
    }

    public function test_adjustment_posts_base_units_and_fifo_once_with_mandatory_reason(): void
    {
        $input = $this->input();
        $this->postJson('/inventory/stock/adjustments', $input)->assertCreated();
        $this->postJson('/inventory/stock/adjustments', $input)->assertCreated();
        $this->assertDatabaseCount('stock_adjustments', 1);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertEquals(25000, DB::table('stock_balances')->value('total_value'));
        $out = $this->input('out');
        $this->postJson('/inventory/stock/adjustments', $out)->assertCreated();
        $this->assertEquals(0, DB::table('stock_balances')->value('quantity'));
        $this->postJson('/inventory/stock/adjustments', $this->input('out'))->assertUnprocessable();
        $this->postJson('/inventory/stock/adjustments', array_replace($input, ['reason' => '']))->assertUnprocessable();
        $this->postJson('/inventory/stock/adjustments', array_replace($input, ['quantity' => '1']))->assertUnprocessable();
        $this->assertDatabaseHas('activity_log', ['log_name' => 'inventory.stock', 'event' => 'adjusted']);
    }

    public function test_adjust_permission_is_separate_and_cost_cannot_be_chosen_for_outgoing(): void
    {
        $input = $this->input();
        $this->manager->revokePermissionTo('inventory.stock.adjust');
        $this->postJson('/inventory/stock/adjustments', $input)->assertForbidden();
        $this->manager->givePermissionTo('inventory.stock.adjust');
        $this->postJson('/inventory/stock/adjustments', array_replace($input, ['direction' => 'out']))->assertUnprocessable();
        $this->assertDatabaseCount('stock_adjustments', 0);
    }
}
