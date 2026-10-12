<?php

namespace Tests\Feature\Modules\Inventory\Stock;

use App\Modules\Inventory\Items\Database\Seeders\ItemsDemoSeeder;
use App\Modules\Inventory\Stock\Database\Seeders\StockAccessSeeder;
use App\Modules\Inventory\Stock\Database\Seeders\StockDemoSeeder;
use App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder;
use App\Modules\Inventory\Warehouses\Database\Seeders\WarehousesDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->assertDatabaseCount('permissions', 3);
        $this->assertDatabaseCount('stock_openings', 1);
        $this->assertDatabaseCount('stock_movements', 3);
        $this->assertDatabaseCount('fifo_layers', 3);
        $this->assertSame($count, Activity::count());
    }
}
