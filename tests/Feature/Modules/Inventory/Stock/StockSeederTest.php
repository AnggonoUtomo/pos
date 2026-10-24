<?php

namespace Tests\Feature\Modules\Inventory\Stock;

use App\Modules\Inventory\Items\Database\Seeders\ItemsDemoSeeder;
use App\Modules\Inventory\Stock\Application\Actions\ManageOpening;
use App\Modules\Inventory\Stock\Application\Actions\PostOpening;
use App\Modules\Inventory\Stock\Database\Seeders\StockAccessSeeder;
use App\Modules\Inventory\Stock\Database\Seeders\StockDemoSeeder;
use App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder;
use App\Modules\Inventory\Warehouses\Database\Seeders\WarehousesDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class StockSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_permission_and_relational_demo_seeders_are_idempotent_without_accounts(): void
    {
        $this->seed([WarehousesDemoSeeder::class, UnitsDemoSeeder::class, ItemsDemoSeeder::class, StockAccessSeeder::class, StockDemoSeeder::class]);
        $count = Activity::count();
        $this->seed([StockAccessSeeder::class, StockDemoSeeder::class]);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('roles', 0);
        $this->assertDatabaseCount('permissions', 4);
        $this->assertDatabaseCount('stock_openings', 1);
        $this->assertDatabaseCount('stock_movements', 5);
        $this->assertDatabaseCount('fifo_layers', 5);
        $this->assertSame($count, Activity::count());
        $nym = DB::table('items')->where('sku', 'DEMO-ELEC-NYM-3X15')->value('id');
        $this->assertDatabaseHas('stock_balances', ['item_id' => $nym, 'quantity' => '150.000000', 'total_value' => '2250000.000000']);
        $this->assertDatabaseHas('fifo_layers', ['item_id' => $nym, 'remaining_quantity' => '150.000000', 'remaining_value' => '2250000.000000']);
    }

    public function test_legacy_marker_does_not_suppress_new_demo_or_rewrite_old_document(): void
    {
        $this->seed([WarehousesDemoSeeder::class, UnitsDemoSeeder::class, ItemsDemoSeeder::class]);
        $legacy = app(ManageOpening::class)->create([
            'warehouse_id' => DB::table('warehouses')->where('code', 'WH-SECONDARY')->value('id'),
            'notes' => 'DEMO: opening-stock-v1',
            'lines' => [[
                'item_id' => DB::table('items')->where('sku', 'DEMO-CCTV-IP-2MP')->value('id'),
                'unit_id' => DB::table('units')->where('code', 'UNIT')->value('id'),
                'quantity' => '1', 'unit_cost' => '500000',
            ]],
        ]);
        app(PostOpening::class)->handle($legacy);
        $before = DB::table('stock_openings')->where('id', $legacy)->first();
        $movements = DB::table('stock_movements')->where('warehouse_id', $before->warehouse_id)->get()->toJson();
        $this->seed(StockDemoSeeder::class);
        $this->assertEquals($before, DB::table('stock_openings')->where('id', $legacy)->first());
        $this->assertSame($movements, DB::table('stock_movements')->where('warehouse_id', $before->warehouse_id)->get()->toJson());
        $this->assertDatabaseCount('stock_openings', 2);
        $this->assertDatabaseCount('fifo_layers', 6);
    }
}
