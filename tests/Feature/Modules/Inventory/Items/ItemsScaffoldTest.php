<?php

namespace Tests\Feature\Modules\Inventory\Items;

use App\Modules\Inventory\Items\Application\Actions\CreateItem;
use App\Modules\Inventory\Items\Application\DTOs\CreateItemData;
use App\Modules\Inventory\Items\Application\DTOs\ItemUnitData;
use App\Modules\Inventory\Items\Infrastructure\Persistence\Models\Item;
use App\Modules\Inventory\Items\Infrastructure\Persistence\Models\ItemUnit;
use App\Modules\Inventory\Units\Application\Contracts\UnitLookup;
use App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ItemsScaffoldTest extends TestCase
{
    use RefreshDatabase;

    public function test_schema_has_ulids_soft_deletes_and_restricts_orphan_units(): void
    {
        $this->seed(UnitsDemoSeeder::class);
        $pcs = app(UnitLookup::class)->findActiveByCode('PCS')->id;
        $id = app(CreateItem::class)->handle(new CreateItemData('A', 'Barang A', $pcs));
        $item = Item::query()->findOrFail($id);
        $relation = $item->units()->sole();
        $this->assertTrue(Str::isUlid($relation->id));
        $relation->delete();
        $item->delete();
        $this->assertSoftDeleted('items', ['id' => $id]);
        $this->assertSoftDeleted('item_units', ['id' => $relation->id]);
        $this->assertSame(0, Item::query()->count());
        $this->assertSame(0, ItemUnit::query()->count());

        $this->expectException(QueryException::class);
        DB::table('units')->where('id', $pcs)->delete();
    }

    public function test_sku_stays_reserved_after_soft_delete(): void
    {
        $this->seed(UnitsDemoSeeder::class);
        $pcs = app(UnitLookup::class)->findActiveByCode('PCS')->id;
        $id = app(CreateItem::class)->handle(new CreateItemData('A', 'Barang A', $pcs));
        Item::query()->findOrFail($id)->delete();
        $this->expectException(QueryException::class);
        app(CreateItem::class)->handle(new CreateItemData('A', 'Barang Baru', $pcs));
    }

    public function test_barcode_stays_reserved_after_soft_delete(): void
    {
        $this->seed(UnitsDemoSeeder::class);
        $pcs = app(UnitLookup::class)->findActiveByCode('PCS')->id;
        $id = app(CreateItem::class)->handle(new CreateItemData('A', 'Barang A', $pcs, [new ItemUnitData($pcs, '1', 'RESERVED')]));
        Item::query()->findOrFail($id)->units()->sole()->delete();
        $this->expectException(QueryException::class);
        app(CreateItem::class)->handle(new CreateItemData('B', 'Barang Baru', $pcs, [new ItemUnitData($pcs, '1', 'RESERVED')]));
    }
}
