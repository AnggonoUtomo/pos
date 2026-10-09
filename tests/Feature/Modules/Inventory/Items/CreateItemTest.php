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
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CreateItemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UnitsDemoSeeder::class);
    }

    public function test_each_item_has_its_own_box_ratio_and_automatic_base_row(): void
    {
        $pcs = app(UnitLookup::class)->findActiveByCode('PCS')->id;
        $box = app(UnitLookup::class)->findActiveByCode('BOX')->id;
        $a = app(CreateItem::class)->handle(new CreateItemData('A', 'Barang A', $pcs, [new ItemUnitData($box, '12', 'BOX-A')]));
        $b = app(CreateItem::class)->handle(new CreateItemData('B', 'Barang B', $pcs, [new ItemUnitData($box, '24', 'BOX-B', isPurchaseUnit: false)]));

        $this->assertTrue(Str::isUlid($a));
        $this->assertTrue(Item::query()->findOrFail($a)->is_active);
        $this->assertSame('1.000000', ItemUnit::query()->where('item_id', $a)->where('unit_id', $pcs)->sole()->conversion_ratio);
        $this->assertSame('12.000000', ItemUnit::query()->where('item_id', $a)->where('unit_id', $box)->sole()->conversion_ratio);
        $this->assertSame('24.000000', ItemUnit::query()->where('item_id', $b)->where('unit_id', $box)->sole()->conversion_ratio);
        $this->assertFalse(ItemUnit::query()->where('item_id', $b)->where('unit_id', $box)->sole()->is_purchase_unit);
        $this->assertDatabaseCount('item_units', 4);
    }

    public function test_base_ratio_must_be_one_and_duplicate_units_are_rejected(): void
    {
        $pcs = app(UnitLookup::class)->findActiveByCode('PCS')->id;
        foreach ([[new ItemUnitData($pcs, '2')], [new ItemUnitData($pcs, '1'), new ItemUnitData($pcs, '1')]] as $units) {
            try {
                app(CreateItem::class)->handle(new CreateItemData('BAD', 'Barang Invalid', $pcs, $units));
                $this->fail('Invalid unit configuration must be rejected.');
            } catch (InvalidArgumentException) {
                $this->assertDatabaseCount('items', 0);
                $this->assertDatabaseCount('item_units', 0);
            }
        }
    }

    #[DataProvider('unavailableUnits')]
    public function test_it_rejects_missing_inactive_or_deleted_unit(string $state): void
    {
        $unit = app(UnitLookup::class)->findActiveByCode('PCS')->id;
        if ($state === 'inactive') {
            DB::table('units')->where('id', $unit)->update(['is_active' => false]);
        } elseif ($state === 'deleted') {
            DB::table('units')->where('id', $unit)->update(['deleted_at' => now()]);
        } else {
            $unit = 'missing';
        }
        $this->expectException(InvalidArgumentException::class);
        app(CreateItem::class)->handle(new CreateItemData('BAD', 'Barang Invalid', $unit));
    }

    public static function unavailableUnits(): array
    {
        return [['missing'], ['inactive'], ['deleted']];
    }

    public function test_it_normalizes_text_and_empty_barcode(): void
    {
        $pcs = app(UnitLookup::class)->findActiveByCode('PCS')->id;
        $id = app(CreateItem::class)->handle(new CreateItemData(' A ', ' Barang A ', $pcs, [new ItemUnitData($pcs, '1', '  ')], isActive: false));
        $item = Item::query()->findOrFail($id);
        $this->assertSame('A', $item->sku);
        $this->assertSame('Barang A', $item->name);
        $this->assertFalse($item->is_active);
        $this->assertNull($item->units()->sole()->barcode);
    }

    #[DataProvider('invalidText')]
    public function test_it_rejects_invalid_text_before_persistence(string $sku, string $name, ?string $barcode): void
    {
        $pcs = app(UnitLookup::class)->findActiveByCode('PCS')->id;
        try {
            app(CreateItem::class)->handle(new CreateItemData($sku, $name, $pcs, [new ItemUnitData($pcs, '1', $barcode)]));
            $this->fail('Invalid text must fail.');
        } catch (InvalidArgumentException) {
            $this->assertDatabaseCount('items', 0);
            $this->assertDatabaseCount('item_units', 0);
        }
    }

    public static function invalidText(): array
    {
        return [[' ', 'A', null], [str_repeat('A', 65), 'A', null], ['A', '', null], ['A', str_repeat('A', 151), null], ['A', 'A', str_repeat('A', 65)]];
    }

    public function test_duplicate_barcode_rolls_back_item_relations_and_audit(): void
    {
        $pcs = app(UnitLookup::class)->findActiveByCode('PCS')->id;
        app(CreateItem::class)->handle(new CreateItemData('A', 'Barang A', $pcs, [new ItemUnitData($pcs, '1', 'SHARED')]));
        try {
            app(CreateItem::class)->handle(new CreateItemData('B', 'Barang B', $pcs, [new ItemUnitData($pcs, '1', 'SHARED')]));
            $this->fail('Duplicate barcode must fail.');
        } catch (QueryException) {
            $this->assertDatabaseCount('items', 1);
            $this->assertDatabaseCount('item_units', 1);
            $this->assertSame(2, DB::table('activity_log')->whereIn('log_name', ['inventory.items', 'inventory.item_units'])->count());
            $this->assertDatabaseMissing('items', ['sku' => 'B']);
        }
    }
}
