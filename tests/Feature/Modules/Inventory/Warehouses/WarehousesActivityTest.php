<?php

namespace Tests\Feature\Modules\Inventory\Warehouses;

use App\Models\User;
use App\Modules\Inventory\Warehouses\Database\Seeders\WarehousesDemoSeeder;
use App\Modules\Inventory\Warehouses\Infrastructure\Persistence\Models\Warehouse;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class WarehousesActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_supports_both_numeric_and_ulid_subjects(): void
    {
        $this->assertContains(Schema::getColumnType('activity_log', 'subject_id'), ['varchar', 'nvarchar']);
        $actor = User::factory()->create();
        $this->actingAs($actor);
        activity('identity')->performedOn($actor)->event('test')->log('Numeric subject');
        $warehouse = Warehouse::query()->create(['code' => 'WH-AUDIT', 'name' => 'Gudang Audit']);

        $numericLog = Activity::query()->where('log_name', 'identity')->sole();
        $warehouseLog = Activity::query()->where('log_name', 'inventory.warehouses')->sole();
        $this->assertTrue($numericLog->subject->is($actor));
        $this->assertTrue($warehouseLog->subject->is($warehouse));
        $this->assertTrue($warehouseLog->causer->is($actor));
    }

    public function test_lifecycle_changes_are_audited_and_unchanged_saves_are_skipped(): void
    {
        $warehouse = Warehouse::query()->create(['code' => 'WH-AUDIT', 'name' => 'Gudang Audit']);
        $warehouse->update(['name' => 'Gudang Diperbarui', 'is_active' => false]);
        $updated = Activity::query()->where('event', 'updated')->sole();
        $this->assertSame('Gudang Audit', $updated->attribute_changes['old']['name']);
        $this->assertSame('Gudang Diperbarui', $updated->attribute_changes['attributes']['name']);

        $warehouse->save();
        $this->assertDatabaseCount('activity_log', 2);
        $warehouse->delete();
        $warehouse->restore();

        $this->assertSame(
            ['created', 'updated', 'deleted', 'restored'],
            Activity::query()->orderBy('id')->pluck('event')->all(),
        );
    }

    public function test_repeated_demo_seed_does_not_generate_duplicate_audit(): void
    {
        $this->seed(WarehousesDemoSeeder::class);
        $this->seed(WarehousesDemoSeeder::class);

        $this->assertDatabaseCount('warehouses', 2);
        $this->assertDatabaseCount('activity_log', 2);
    }

    public function test_subject_migration_preserves_existing_audit_and_safe_rollback(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->unsignedBigInteger('subject_id')->nullable()->change();
        });
        $actor = User::factory()->create();
        activity('identity')->performedOn($actor)->event('test')->log('Existing audit');
        $existing = Activity::query()->sole();

        $migration = require database_path('migrations/2026_10_09_000001_support_ulid_activity_log_subjects.php');
        $migration->up();

        $this->assertSame('Existing audit', $existing->fresh()->description);
        $this->assertTrue($existing->fresh()->subject->is($actor));
        $warehouse = Warehouse::query()->create(['code' => 'WH-MIGRATION', 'name' => 'Gudang Migration']);
        $migration->down();

        $this->assertTrue(Activity::query()->where('log_name', 'inventory.warehouses')->sole()->subject->is($warehouse));
        $this->assertDatabaseCount('activity_log', 2);
    }
}
