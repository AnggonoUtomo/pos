<?php

namespace Tests\Feature\Modules\Inventory\Units;

use App\Modules\Inventory\Units\Application\Contracts\UnitLookup;
use App\Modules\Inventory\Units\Application\DTOs\UnitData;
use App\Modules\Inventory\Units\Infrastructure\Persistence\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UnitLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_lookup_exposes_active_unit_as_public_dto_by_id_and_code(): void
    {
        $unit = Unit::query()->create(['code' => 'PCS', 'name' => 'Buah', 'symbol' => 'pcs']);
        $lookup = app(UnitLookup::class);
        $data = $lookup->findActiveByCode('PCS');

        $this->assertInstanceOf(UnitData::class, $data);
        $this->assertSame($unit->id, $data->id);
        $this->assertSame('pcs', $data->symbol);
        $this->assertEquals($data, DB::transaction(fn () => $lookup->findActiveById($unit->id, lock: true)));
    }

    public function test_lookup_hides_missing_inactive_and_deleted_units(): void
    {
        $unit = Unit::query()->create(['code' => 'PCS', 'name' => 'Buah', 'symbol' => 'pcs', 'is_active' => false]);
        $lookup = app(UnitLookup::class);
        $this->assertNull($lookup->findActiveById($unit->id));
        $this->assertNull($lookup->findActiveByCode('PCS'));
        $this->assertNull($lookup->findActiveById('missing'));
        $unit->update(['is_active' => true]);
        $unit->delete();
        $this->assertNull($lookup->findActiveByCode('PCS'));
    }
}
