<?php

namespace Tests\Feature\Modules\Sales\Parties;

use App\Modules\Inventory\Items\Database\Seeders\ItemsDemoSeeder;
use App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder;
use App\Modules\Sales\Parties\Database\Seeders\ItemPricesDemoSeeder;
use App\Modules\Sales\Parties\Database\Seeders\PartiesDemoSeeder;
use App\Modules\Sales\Parties\Infrastructure\Persistence\Models\CustomerLevel;
use App\Modules\Sales\Parties\Infrastructure\Persistence\Models\ItemPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

class ItemPricesSeederTest extends TestCase
{
    use RefreshDatabase;

    private function prerequisites(): void
    {
        $this->seed([UnitsDemoSeeder::class, ItemsDemoSeeder::class, PartiesDemoSeeder::class]);
    }

    public function test_demo_is_exact_idempotent_and_preserves_changed_and_trashed_prices(): void
    {
        $this->prerequisites();
        $this->seed(ItemPricesDemoSeeder::class);
        $this->assertDatabaseCount('item_prices', 54);
        $this->assertSame(54, DB::table('activity_log')->where('log_name', 'sales.item_prices')->count());
        $this->assertSame('1525000.00', ItemPrice::where('amount', '1525000.00')->firstOrFail()->amount);
        $row = ItemPrice::firstOrFail();
        $row->update(['amount' => '123.45']);
        $row->delete();
        $before = DB::table('item_prices')->orderBy('id')->get()->toJson();
        $audit = DB::table('activity_log')->count();
        $this->seed(ItemPricesDemoSeeder::class);
        $this->assertSame($before, DB::table('item_prices')->orderBy('id')->get()->toJson());
        $this->assertSame($audit, DB::table('activity_log')->count());
        foreach (['users', 'roles', 'role_has_permissions', 'model_has_roles'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
    }

    public function test_unknown_level_refuses_before_any_prices_are_written(): void
    {
        $this->prerequisites();
        CustomerLevel::create(['code' => 'CUSTOM', 'name' => 'Custom']);
        try {
            $this->seed(ItemPricesDemoSeeder::class);
            $this->fail('Unknown demo factor must not be invented.');
        } catch (LogicException) {
            $this->assertDatabaseCount('item_prices', 0);
        }
    }

    public function test_late_missing_item_rolls_back_earlier_demo_prices_and_audit(): void
    {
        $this->prerequisites();
        DB::table('items')->where('sku', 'DEMO-CCTV-KIT')->update(['deleted_at' => now()]);
        $before = DB::table('activity_log')->count();
        try {
            $this->seed(ItemPricesDemoSeeder::class);
            $this->fail('Missing last prerequisite must rollback batch.');
        } catch (LogicException) {
            $this->assertDatabaseCount('item_prices', 0);
            $this->assertSame($before, DB::table('activity_log')->count());
        }
    }

    public function test_demo_refuses_missing_prerequisite_and_production(): void
    {
        try {
            $this->seed(ItemPricesDemoSeeder::class);
            $this->fail('Demo prerequisites required.');
        } catch (LogicException) {
            $this->assertDatabaseCount('item_prices', 0);
        }
        $this->app['env'] = 'production';
        try {
            app(ItemPricesDemoSeeder::class)->run();
            $this->fail('Demo production forbidden.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('local/testing', $exception->getMessage());
        } finally {
            $this->app['env'] = 'testing';
        }
    }
}
