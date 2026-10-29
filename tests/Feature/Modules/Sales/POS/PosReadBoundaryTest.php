<?php

namespace Tests\Feature\Modules\Sales\POS;

use App\Modules\Inventory\Items\Application\Contracts\SaleItemCatalog;
use App\Modules\Inventory\Items\Database\Seeders\ItemsDemoSeeder;
use App\Modules\Inventory\Stock\Application\Contracts\StockAvailability;
use App\Modules\Inventory\Stock\Database\Seeders\StockDemoSeeder;
use App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder;
use App\Modules\Inventory\Warehouses\Database\Seeders\WarehousesDemoSeeder;
use App\Modules\Sales\Parties\Application\Contracts\CustomerSalesLookup;
use App\Modules\Sales\Parties\Database\Seeders\ItemPricesDemoSeeder;
use App\Modules\Sales\Parties\Database\Seeders\PartiesDemoSeeder;
use App\Modules\Sales\POS\Database\Seeders\POSAccessSeeder;
use App\Modules\Sales\POS\Database\Seeders\POSDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PosReadBoundaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([WarehousesDemoSeeder::class, UnitsDemoSeeder::class, ItemsDemoSeeder::class,
            PartiesDemoSeeder::class, ItemPricesDemoSeeder::class, StockDemoSeeder::class]);
    }

    public function test_search_barcode_batch_and_stock_are_real_and_read_only(): void
    {
        $before = DB::table('activity_log')->count();
        $catalog = app(SaleItemCatalog::class);
        $rows = $catalog->search('DEMO-CCTV-IP-2MP-BOX', 1);
        $this->assertCount(1, $rows['data']);
        $item = $rows['data'][0];
        $this->assertSame('Kamera IP 2MP', $item->name);
        $this->assertCount(2, $item->units);
        $this->assertSame('1.000000', collect($item->units)->firstWhere('id', $item->baseUnitId)->ratio);
        $this->assertSame($item->id, $catalog->findMany([$item->id])[$item->id]->id);
        $warehouse = DB::table('warehouses')->where('code', 'WH-MAIN')->value('id');
        $availability = app(StockAvailability::class)->forItems($warehouse, [$item->id]);
        $this->assertSame('8.000000', $availability[$item->id]);
        $this->assertSame([], $catalog->search('%', 1)['data']);
        $this->assertSame($before, DB::table('activity_log')->count());
    }

    public function test_inactive_deleted_and_non_sale_data_are_not_published(): void
    {
        $catalog = app(SaleItemCatalog::class);
        $item = $catalog->search('DEMO-CCTV-IP-2MP', 1)['data'][0];
        DB::table('item_units')->where('item_id', $item->id)->update(['is_sale_unit' => false]);
        $this->assertSame([], $catalog->findMany([$item->id]));
        $this->assertSame([], $catalog->search('DEMO-CCTV-IP-2MP', 1)['data']);
        DB::table('item_units')->where('item_id', $item->id)->update(['is_sale_unit' => true]);
        DB::table('units')->where('id', $item->baseUnitId)->update(['is_active' => false]);
        $this->assertSame([], $catalog->findMany([$item->id]));
        $customer = app(CustomerSalesLookup::class)->choices()[0];
        $this->assertFalse(property_exists($customer, 'phone'));
        DB::table('customer_levels')->where('id', $customer->levelId)->update(['is_active' => false]);
        $this->assertNull(app(CustomerSalesLookup::class)->findActive($customer->id));
    }

    public function test_permission_demo_seeders_do_not_grant_or_write_transactions(): void
    {
        $before = DB::table('activity_log')->count();
        $this->seed([POSAccessSeeder::class, POSAccessSeeder::class, POSDemoSeeder::class]);
        $this->assertDatabaseCount('permissions', 5);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('model_has_permissions', 0);
        $this->assertSame($before, DB::table('activity_log')->count());
        $this->app['env'] = 'production';
        try {
            app(POSDemoSeeder::class)->run();
            $this->fail('Demo only local/testing.');
        } catch (\LogicException) {
            $this->assertSame($before, DB::table('activity_log')->count());
        } finally {
            $this->app['env'] = 'testing';
        }
    }

    public function test_catalog_paginates_without_publishing_deleted_masters(): void
    {
        $source = DB::table('items')->first();
        for ($i = 0; $i < 26; $i++) {
            $id = strtolower((string) Str::ulid());
            DB::table('items')->insert(['id' => $id, 'sku' => sprintf('QA-PAGE-%02d', $i), 'name' => 'Pagination',
                'base_unit_id' => $source->base_unit_id, 'is_active' => true]);
            DB::table('item_units')->insert(['id' => strtolower((string) Str::ulid()), 'item_id' => $id,
                'unit_id' => $source->base_unit_id, 'conversion_ratio' => '1', 'is_sale_unit' => true, 'is_purchase_unit' => true]);
        }
        $catalog = app(SaleItemCatalog::class);
        $this->assertCount(25, $catalog->search('QA-PAGE', 1)['data']);
        $this->assertTrue($catalog->search('QA-PAGE', 1)['has_more']);
        $this->assertCount(1, $catalog->search('QA-PAGE', 2)['data']);
        DB::table('items')->where('sku', 'QA-PAGE-25')->update(['deleted_at' => now()]);
        $this->assertSame([], $catalog->search('QA-PAGE', 2)['data']);
    }

    public function test_barcode_of_inactive_or_deleted_unit_does_not_match_catalog(): void
    {
        $catalog = app(SaleItemCatalog::class);
        $box = DB::table('units')->where('code', 'BOX')->value('id');
        DB::table('units')->where('id', $box)->update(['is_active' => false]);
        $this->assertSame([], $catalog->search('DEMO-CCTV-IP-2MP-BOX', 1)['data']);
        $this->assertCount(1, $catalog->search('DEMO-CCTV-IP-2MP', 1)['data']);
    }
}
