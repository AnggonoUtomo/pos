<?php

namespace Tests\Feature\Modules\Inventory\Units;

use App\Models\User;
use App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder;
use App\Modules\Inventory\Units\Infrastructure\Persistence\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class UnitsActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_unit_lifecycle_is_audited_with_actor_and_ulid_subject(): void
    {
        $actor = User::factory()->create();
        $this->actingAs($actor);
        $unit = Unit::query()->create(['code' => 'PCS', 'name' => 'Buah', 'symbol' => 'pcs']);
        $created = Activity::query()->where('log_name', 'inventory.units')->sole();
        $this->assertTrue($created->subject->is($unit));
        $this->assertTrue($created->causer->is($actor));

        $unit->update(['name' => 'Buah User', 'symbol' => 'bh', 'is_active' => false]);
        $updated = Activity::query()->where('event', 'updated')->sole();
        $this->assertSame('pcs', $updated->attribute_changes['old']['symbol']);
        $this->assertSame('bh', $updated->attribute_changes['attributes']['symbol']);
        $unit->save();
        $this->assertDatabaseCount('activity_log', 2);

        $unit->delete();
        $unit->restore();

        $this->assertSame(
            ['created', 'updated', 'deleted', 'restored'],
            Activity::query()->orderBy('id')->pluck('event')->all(),
        );
    }

    public function test_seed_repeat_does_not_add_audit_or_change_units(): void
    {
        $this->seed(UnitsDemoSeeder::class);
        $this->seed(UnitsDemoSeeder::class);

        $this->assertDatabaseCount('units', 8);
        $this->assertDatabaseCount('activity_log', 8);
        $this->assertSame(['inventory.units'], Activity::query()->distinct()->pluck('log_name')->all());
    }
}
