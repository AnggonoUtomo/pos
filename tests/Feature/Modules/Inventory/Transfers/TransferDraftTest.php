<?php

namespace Tests\Feature\Modules\Inventory\Transfers;

use App\Models\User;
use App\Modules\Inventory\Transfers\Database\Seeders\TransfersAccessSeeder;
use App\Modules\Inventory\Warehouses\Infrastructure\Persistence\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;
use Tests\Feature\Modules\Inventory\Stock\StockTestCase;

class TransferDraftTest extends StockTestCase
{
    protected string $destination;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TransfersAccessSeeder::class);
        $this->manager->givePermissionTo(['inventory.transfers.view', 'inventory.transfers.manage']);
        $this->destination = Warehouse::create(['code' => 'DST', 'name' => 'Gudang Tujuan', 'is_active' => true])->id;
    }

    protected function transfer(): array
    {
        return ['source_warehouse_id' => $this->warehouse, 'destination_warehouse_id' => $this->destination,
            'notes' => 'Catatan internal', 'lines' => [['item_id' => $this->item, 'unit_id' => $this->box, 'quantity' => '1.5']]];
    }

    public function test_save_uses_authoritative_snapshot_and_noop_does_not_change_version_or_lines(): void
    {
        $id = $this->postJson('/inventory/transfers', $this->transfer())->assertCreated()->json('id');
        $this->assertDatabaseHas('stock_transfers', ['id' => $id, 'version' => 1, 'status' => 'draft']);
        $this->assertDatabaseHas('stock_transfer_lines', ['transfer_id' => $id, 'quantity' => '1.500000', 'base_quantity' => '18.000000']);
        $before = DB::table('stock_transfer_lines')->get()->toJson();
        $this->putJson('/inventory/transfers/'.$id, $this->transfer() + ['version' => 1])->assertOk();
        $this->assertSame($before, DB::table('stock_transfer_lines')->get()->toJson());
        $this->assertSame(1, Activity::where('log_name', 'inventory.transfers')->count());
        $this->assertSame($this->manager->id, Activity::where('log_name', 'inventory.transfers')->sole()->causer_id);
        DB::table('warehouses')->where('id', $this->warehouse)->update(['name' => 'Berubah', 'is_active' => false]);
        DB::table('items')->where('id', $this->item)->update(['name' => 'Diubah', 'is_active' => false]);
        $this->getJson('/inventory/transfers/data')->assertOk()->assertJsonPath('data.0.source_warehouse_name', 'Gudang QA')
            ->assertJsonPath('data.0.lines.0.item_name', 'Barang QA')->assertJsonPath('data.0.lines.0.quantity', '1.500000');
        $this->putJson('/inventory/transfers/'.$id, $this->transfer() + ['version' => 1])->assertUnprocessable();
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseCount('fifo_layers', 0);
        $this->assertDatabaseCount('payment_receipts', 0);
    }

    public function test_invalid_warehouses_quantities_units_duplicates_and_forgery_are_rejected(): void
    {
        $payloads = [];
        $payloads[] = array_replace($this->transfer(), ['destination_warehouse_id' => strtoupper($this->warehouse)]);
        $payloads[] = $this->transfer() + ['status' => 'posted'];
        $payloads[] = array_replace($this->transfer(), ['lines' => []]);
        $payloads[] = array_replace($this->transfer(), ['lines' => array_fill(0, 2, $this->transfer()['lines'][0])]);
        foreach (['0', '-1', '1,5', '1e3', '0.0000001', '99999999999999'] as $quantity) {
            $p = $this->transfer();
            $p['lines'][0]['quantity'] = $quantity;
            $payloads[] = $p;
        }
        $p = $this->transfer();
        $p['lines'][0]['base_quantity'] = '1';
        $payloads[] = $p;
        $p = $this->transfer();
        $p['lines'][0]['unit_id'] = $this->destination;
        $payloads[] = $p;
        foreach ($payloads as $payload) {
            $this->postJson('/inventory/transfers', $payload)->assertUnprocessable();
            $this->assertDatabaseCount('stock_transfers', 0);
        }
        DB::table('warehouses')->where('id', $this->destination)->update(['is_active' => false]);
        $this->postJson('/inventory/transfers', $this->transfer())->assertUnprocessable();
    }

    public function test_update_has_version_guard_and_can_change_warehouse_pair_while_draft(): void
    {
        $id = $this->postJson('/inventory/transfers', $this->transfer())->assertCreated()->json('id');
        $changed = array_replace($this->transfer(), ['source_warehouse_id' => $this->destination, 'destination_warehouse_id' => $this->warehouse, 'notes' => 'Diganti', 'version' => 1]);
        $this->putJson('/inventory/transfers/'.$id, $changed)->assertOk();
        $this->assertDatabaseHas('stock_transfers', ['id' => $id, 'version' => 2, 'source_warehouse_id' => $this->destination]);
        $this->putJson('/inventory/transfers/'.$id, $changed)->assertUnprocessable();
        $this->putJson('/inventory/transfers/'.$id, $this->transfer())->assertUnprocessable();
        $this->assertSame(['created', 'updated'], Activity::where('log_name', 'inventory.transfers')->orderBy('id')->pluck('event')->all());
    }

    public function test_permissions_do_not_imply_each_other_and_options_are_restricted(): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('inventory.transfers.view');
        $this->actingAs($viewer)->getJson('/inventory/transfers/data')->assertOk();
        $this->getJson('/inventory/transfers/options')->assertForbidden();
        $this->postJson('/inventory/transfers', $this->transfer())->assertForbidden();
        $manager = User::factory()->create();
        $manager->givePermissionTo('inventory.transfers.manage');
        $this->actingAs($manager)->getJson('/inventory/transfers/data')->assertForbidden();
        $this->getJson('/inventory/transfers/options')->assertOk()->assertJsonCount(1, 'items');
        $this->postJson('/inventory/transfers', $this->transfer())->assertCreated();
        $this->app->detectEnvironment(fn () => 'local');
        $this->postJson('/inventory/transfers', $this->transfer())->assertStatus(419);
        $this->app->detectEnvironment(fn () => 'testing');
    }

    public function test_query_filters_are_literal_paginated_and_do_not_mutate_audit(): void
    {
        $this->postJson('/inventory/transfers', $this->transfer())->assertCreated();
        $before = Activity::count();
        $this->getJson('/inventory/transfers/data?search=%25')->assertOk()->assertJsonPath('meta.total', 0);
        $this->getJson('/inventory/transfers/data?source_warehouse_id='.$this->warehouse)->assertOk()->assertJsonPath('meta.total', 1);
        $this->getJson('/inventory/transfers/data?destination_warehouse_id='.$this->warehouse)->assertOk()->assertJsonPath('meta.total', 0);
        $this->getJson('/inventory/transfers/data?trashed=1')->assertOk()->assertJsonPath('meta.total', 0);
        $this->getJson('/inventory/transfers/data?page=0')->assertUnprocessable();
        $this->assertSame($before, Activity::count());
    }
}
