<?php

namespace Tests\Feature\Modules\Inventory\Items;

use App\Modules\Inventory\Items\Application\Contracts\ItemLabelCatalog;
use App\Modules\Inventory\Items\Database\Seeders\ItemsDemoSeeder;
use App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ItemLabelCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_labels_include_purchase_only_units_without_changing_sale_catalog(): void
    {
        $this->seed([UnitsDemoSeeder::class, ItemsDemoSeeder::class]);
        $item = DB::table('items')->where('sku', 'DEMO-ELEC-MCB-6A')->first();
        $box = DB::table('units')->where('code', 'BOX')->value('id');
        DB::table('item_units')->where('item_id', $item->id)->where('unit_id', $box)
            ->update(['barcode' => '0012345', 'is_sale_unit' => false]);
        $catalog = app(ItemLabelCatalog::class);
        $label = $catalog->find($item->id, $box);
        $this->assertSame('0012345', $label->barcode);
        $this->assertSame($box, $label->unitId);
        $this->assertCount(1, $catalog->search('0012345', 1)['data']);
        $this->assertEmpty($catalog->search('%', 1)['data']);
        DB::table('units')->where('id', $box)->update(['is_active' => false]);
        $this->assertNull($catalog->find($item->id, $box));
        DB::table('items')->where('id', $item->id)->update(['deleted_at' => now()]);
        $this->assertNull($catalog->find($item->id, $item->base_unit_id));
    }
}
