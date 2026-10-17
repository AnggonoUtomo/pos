<?php

namespace Tests\Feature\Modules\Inventory\Stock;

use App\Modules\Inventory\Items\Application\Contracts\ItemStockLookup;
use App\Modules\Inventory\Items\Database\Seeders\ItemsDemoSeeder;
use App\Modules\Inventory\Stock\Application\Actions\ManageOpening;
use App\Modules\Inventory\Stock\Application\Actions\PostOpening;
use App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder;
use App\Modules\Inventory\Warehouses\Application\Contracts\WarehouseLookup;
use App\Modules\Inventory\Warehouses\Database\Seeders\WarehousesDemoSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StockIssueSchemaTest extends TestCase
{
    use DatabaseMigrations;

    public function test_issue_schema_is_additive_and_posted_records_have_no_soft_delete(): void
    {
        foreach (['stock_issues', 'stock_issue_lines', 'fifo_consumptions'] as $table) {
            $this->assertTrue(Schema::hasTable($table));
            $this->assertFalse(Schema::hasColumn($table, 'deleted_at'));
        }
        $this->seed([WarehousesDemoSeeder::class, UnitsDemoSeeder::class, ItemsDemoSeeder::class]);
        $warehouse = app(WarehouseLookup::class)->choices()[0];
        $item = app(ItemStockLookup::class)->choices()[0];
        $id = app(ManageOpening::class)->create(['warehouse_id' => $warehouse->id, 'lines' => [
            ['item_id' => $item->id, 'unit_id' => $item->baseUnitId, 'quantity' => '24', 'unit_cost' => '1'],
        ]]);
        app(PostOpening::class)->handle($id);
        $this->assertDatabaseHas('stock_movements', ['type' => 'opening', 'quantity' => '24.000000', 'issue_line_id' => null]);
        $migration = require app_path('Modules/Inventory/Stock/Database/Migrations/2026_10_16_000001_add_stock_issues.php');
        $opening = DB::table('stock_movements')->get()->toJson();
        $migration->down();
        $this->assertFalse(Schema::hasTable('stock_issues'));
        $this->assertDatabaseCount('stock_movements', 1);
        $migration->up();
        $this->assertEquals(json_decode($opening, flags: JSON_THROW_ON_ERROR), DB::table('stock_movements')->get()->all());
    }
}
