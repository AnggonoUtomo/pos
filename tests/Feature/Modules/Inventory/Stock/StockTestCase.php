<?php

namespace Tests\Feature\Modules\Inventory\Stock;

use App\Models\User;
use App\Modules\Inventory\Items\Application\Actions\CreateItem;
use App\Modules\Inventory\Items\Application\DTOs\CreateItemData;
use App\Modules\Inventory\Items\Application\DTOs\ItemUnitData;
use App\Modules\Inventory\Units\Infrastructure\Persistence\Models\Unit;
use App\Modules\Inventory\Warehouses\Infrastructure\Persistence\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

abstract class StockTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $manager;

    protected string $warehouse;

    protected string $item;

    protected string $base;

    protected string $box;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['inventory.stock.view', 'inventory.stock.manage', 'inventory.stock.post', 'inventory.view', 'inventory.manage'] as $name) {
            Permission::findOrCreate($name, 'web');
        }
        $this->manager = User::factory()->create();
        $this->manager->givePermissionTo(Permission::all());
        $this->actingAs($this->manager);
        $this->warehouse = Warehouse::create(['code' => 'QA', 'name' => 'Gudang QA', 'is_active' => true])->id;
        $this->base = Unit::create(['code' => 'PCS', 'name' => 'Buah', 'symbol' => 'pcs', 'is_active' => true])->id;
        $this->box = Unit::create(['code' => 'BOX', 'name' => 'Dus', 'symbol' => 'dus', 'is_active' => true])->id;
        $this->item = app(CreateItem::class)->handle(new CreateItemData('QA', 'Barang QA', $this->base, [
            new ItemUnitData($this->base, '1'), new ItemUnitData($this->box, '12'),
        ]));
    }

    protected function payload(): array
    {
        return ['warehouse_id' => $this->warehouse, 'notes' => 'Stok awal QA', 'lines' => [
            ['item_id' => $this->item, 'unit_id' => $this->box, 'quantity' => '2', 'unit_cost' => '120000'],
        ]];
    }

    protected function draft(): string
    {
        return $this->postJson('/inventory/stock/openings', $this->payload())->assertCreated()->json('id');
    }
}
