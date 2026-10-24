<?php

namespace Tests\Feature\Modules\Inventory\Transfers;

use App\Modules\Inventory\Stock\Infrastructure\Persistence\Models\StockReceipt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

class TransferPostingTest extends TransferTestCase
{
    private function ready(): string
    {
        Permission::findOrCreate('inventory.transfers.post', 'web');
        $this->manager->givePermissionTo('inventory.transfers.post');
        $opening = $this->draft();
        $this->postJson("/inventory/stock/openings/{$opening}/post")->assertOk();

        return $this->postJson('/inventory/transfers', $this->transfer())->assertCreated()->json('id');
    }

    public function test_posting_conserves_quantity_cost_and_replays_without_duplicate_movements(): void
    {
        $id = $this->ready();
        $this->postJson("/inventory/transfers/{$id}/post", ['version' => 1])->assertOk();
        $this->assertDatabaseHas('stock_transfers', ['id' => $id, 'status' => 'posted', 'version' => 2]);
        $this->assertEquals(6, DB::table('stock_balances')->where('warehouse_id', $this->warehouse)->value('quantity'));
        $this->assertEquals(18, DB::table('stock_balances')->where('warehouse_id', $this->destination)->value('quantity'));
        $this->assertEquals(180000, DB::table('stock_balances')->where('warehouse_id', $this->destination)->value('total_value'));
        $this->assertDatabaseCount('stock_movements', 3);
        $this->postJson("/inventory/transfers/{$id}/post", ['version' => 1])->assertOk();
        $this->assertDatabaseCount('stock_movements', 3);
        $this->postJson("/inventory/transfers/{$id}/post", ['version' => 9])->assertUnprocessable();
        $this->deleteJson("/inventory/transfers/{$id}", ['version' => 2])->assertUnprocessable();
        $this->assertDatabaseHas('activity_log', ['log_name' => 'inventory.transfers', 'event' => 'posted']);
    }

    public function test_insufficient_stock_stale_version_and_permission_do_not_mutate_inventory(): void
    {
        $id = $this->ready();
        $this->postJson("/inventory/transfers/{$id}/post", ['version' => 9])->assertUnprocessable();
        DB::table('stock_transfer_lines')->where('transfer_id', $id)->update(['base_quantity' => '25.000000', 'quantity' => '2.083334']);
        $this->postJson("/inventory/transfers/{$id}/post", ['version' => 1])->assertUnprocessable();
        $this->manager->revokePermissionTo('inventory.transfers.post');
        $this->postJson("/inventory/transfers/{$id}/post", ['version' => 1])->assertForbidden();
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertDatabaseCount('stock_issues', 0);
    }

    public function test_destination_failure_rolls_back_source_and_document(): void
    {
        $id = $this->ready();
        StockReceipt::creating(fn () => false);
        try {
            $this->postJson("/inventory/transfers/{$id}/post", ['version' => 1])->assertStatus(500);
        } finally {
            StockReceipt::flushEventListeners();
        }
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertDatabaseHas('stock_transfers', ['id' => $id, 'status' => 'draft', 'version' => 1]);
        $this->assertEquals(24, DB::table('stock_balances')->where('warehouse_id', $this->warehouse)->value('quantity'));
    }

    public function test_missing_draft_lines_cannot_create_an_empty_posted_transfer(): void
    {
        $id = $this->ready();
        DB::table('stock_transfer_lines')->where('transfer_id', $id)->delete();
        $this->postJson("/inventory/transfers/{$id}/post", ['version' => 1])->assertUnprocessable();
        $this->assertDatabaseCount('stock_issues', 0);
        $this->assertDatabaseHas('stock_transfers', ['id' => $id, 'status' => 'draft']);
    }

    public function test_multiple_source_costs_and_zero_cost_segments_remain_separate_at_destination(): void
    {
        $id = $this->ready();
        $key = strtolower((string) Str::ulid());
        Permission::findOrCreate('inventory.stock.adjust', 'web');
        $this->manager->givePermissionTo('inventory.stock.adjust');
        $this->postJson('/inventory/stock/adjustments', ['idempotency_key' => $key, 'warehouse_id' => $this->warehouse,
            'item_id' => $this->item, 'direction' => 'in', 'quantity' => '2', 'unit_cost' => '20000', 'reason' => 'Barang ditemukan saat hitung fisik'])->assertCreated();
        // A zero-cost layer can legitimately result from a previous fractional return.
        DB::table('fifo_layers')->where('warehouse_id', $this->warehouse)->orderBy('id')->limit(1)->update(['original_value' => '0.000000', 'remaining_value' => '0.000000']);
        DB::table('stock_balances')->where('warehouse_id', $this->warehouse)->update(['total_value' => '40000.000000']);
        DB::table('stock_transfer_lines')->where('transfer_id', $id)->update(['base_quantity' => '26.000000']);
        $this->postJson("/inventory/transfers/{$id}/post", ['version' => 1])->assertOk();
        $layers = DB::table('fifo_layers')->where('warehouse_id', $this->destination)->orderBy('id')->get();
        $this->assertCount(2, $layers);
        $this->assertEquals(0, $layers[0]->original_value);
        $this->assertEquals(40000, $layers[1]->original_value);
        $this->assertEquals(26, DB::table('stock_balances')->where('warehouse_id', $this->destination)->value('quantity'));
        $this->assertSame(2, DB::table('stock_receipt_lines')->whereNotNull('origin_fifo_layer_id')->count());
    }
}
