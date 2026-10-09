<?php

namespace Tests\Feature\Modules\Inventory\Warehouses;

use App\Modules\Inventory\Warehouses\Infrastructure\Persistence\Models\Warehouse;
use App\Modules\Inventory\Warehouses\ServiceProvider;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class WarehousesScaffoldTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_is_discovered_and_warehouse_uses_ulid_and_active_default(): void
    {
        $this->assertNotNull($this->app->getProvider(ServiceProvider::class));
        $warehouse = Warehouse::query()->create(['code' => 'WH-TEST', 'name' => 'Gudang Test']);

        $this->assertTrue(Str::isUlid($warehouse->id));
        $this->assertTrue($warehouse->fresh()->is_active);
        $this->assertDatabaseHas('warehouses', ['id' => $warehouse->id, 'code' => 'WH-TEST']);
    }

    public function test_soft_delete_hides_warehouse_and_restore_preserves_identity(): void
    {
        $warehouse = Warehouse::query()->create(['code' => 'WH-TEST', 'name' => 'Gudang Test']);
        $id = $warehouse->id;
        $warehouse->delete();

        $this->assertSoftDeleted($warehouse);
        $this->assertNull(Warehouse::query()->find($id));

        $warehouse->restore();
        $this->assertSame($id, Warehouse::query()->sole()->id);
    }

    public function test_duplicate_code_is_rejected(): void
    {
        Warehouse::query()->create(['code' => 'WH-TEST', 'name' => 'Gudang Test']);

        $this->expectException(QueryException::class);
        Warehouse::query()->create(['code' => 'WH-TEST', 'name' => 'Gudang Duplikat']);
    }

    public function test_soft_deleted_code_cannot_be_reused(): void
    {
        Warehouse::query()->create(['code' => 'WH-TEST', 'name' => 'Gudang Test'])->delete();

        $this->expectException(QueryException::class);
        Warehouse::query()->create(['code' => 'WH-TEST', 'name' => 'Gudang Duplikat']);
    }
}
