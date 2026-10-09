<?php

namespace Tests\Feature\Modules\Inventory\Units;

use App\Modules\Inventory\Units\Infrastructure\Persistence\Models\Unit;
use App\Modules\Inventory\Units\ServiceProvider;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class UnitsScaffoldTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_is_discovered_and_unit_has_ulid_and_active_default(): void
    {
        $this->assertNotNull($this->app->getProvider(ServiceProvider::class));
        $unit = Unit::query()->create(['code' => 'PCS', 'name' => 'Buah', 'symbol' => 'pcs']);

        $this->assertTrue(Str::isUlid($unit->id));
        $this->assertTrue($unit->fresh()->is_active);
        $this->assertDatabaseHas('units', ['id' => $unit->id, 'symbol' => 'pcs']);
    }

    public function test_soft_delete_and_restore_preserve_unit_identity(): void
    {
        $unit = Unit::query()->create(['code' => 'PCS', 'name' => 'Buah', 'symbol' => 'pcs']);
        $id = $unit->id;
        $unit->delete();

        $this->assertSoftDeleted($unit);
        $this->assertNull(Unit::query()->find($id));
        $unit->restore();
        $this->assertSame($id, Unit::query()->sole()->id);
    }

    public function test_duplicate_code_is_rejected(): void
    {
        Unit::query()->create(['code' => 'PCS', 'name' => 'Buah', 'symbol' => 'pcs']);

        $this->expectException(QueryException::class);
        Unit::query()->create(['code' => 'PCS', 'name' => 'Duplikat', 'symbol' => 'pcs']);
    }

    public function test_soft_deleted_code_cannot_be_reused(): void
    {
        Unit::query()->create(['code' => 'PCS', 'name' => 'Buah', 'symbol' => 'pcs'])->delete();

        $this->expectException(QueryException::class);
        Unit::query()->create(['code' => 'PCS', 'name' => 'Duplikat', 'symbol' => 'pcs']);
    }
}
