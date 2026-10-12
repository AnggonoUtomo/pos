<?php

namespace Tests\Feature\Modules\Inventory\Units;

use App\Models\User;
use App\Modules\Inventory\Units\Database\Seeders\UnitsAccessSeeder;
use App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder;
use App\Modules\Inventory\Units\Infrastructure\Persistence\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UnitsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seed_is_idempotent_and_preserves_edited_and_deleted_units(): void
    {
        $this->seed(UnitsDemoSeeder::class);
        $ids = Unit::query()->orderBy('code')->pluck('id')->all();
        $pcs = Unit::query()->where('code', 'PCS')->sole();
        $pcs->update(['name' => 'Buah User', 'symbol' => 'bh', 'is_active' => false]);
        $box = Unit::query()->where('code', 'BOX')->sole();
        $box->delete();

        $this->seed(UnitsDemoSeeder::class);

        $this->assertSame($ids, Unit::withTrashed()->orderBy('code')->pluck('id')->all());
        $this->assertSame('Buah User', $pcs->fresh()->name);
        $this->assertSame('bh', $pcs->fresh()->symbol);
        $this->assertFalse($pcs->fresh()->is_active);
        $this->assertSoftDeleted($box);
        $this->assertDatabaseCount('units', 8);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_demo_seed_refuses_production_before_writing(): void
    {
        $this->app->instance('env', 'production');

        try {
            app(UnitsDemoSeeder::class)->run();
            $this->fail('Demo seed must refuse production.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('local/testing', $exception->getMessage());
            $this->assertDatabaseCount('units', 0);
        }
    }

    public function test_access_seed_is_idempotent_and_preserves_existing_access(): void
    {
        $role = Role::findOrCreate('observer', 'web');
        $role->givePermissionTo(Permission::findOrCreate('existing.view', 'web'));
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->seed(UnitsAccessSeeder::class);
        $this->seed(UnitsAccessSeeder::class);

        $this->assertDatabaseHas('permissions', ['name' => 'inventory.view', 'guard_name' => 'web']);
        $this->assertDatabaseHas('permissions', ['name' => 'inventory.manage', 'guard_name' => 'web']);
        $this->assertDatabaseCount('permissions', 3);
        $this->assertSame(['existing.view'], $role->fresh()->permissions->pluck('name')->all());
        $this->assertFalse($user->fresh()->hasPermissionTo('inventory.manage'));
        $this->assertDatabaseCount('roles', 1);
        $this->assertDatabaseCount('users', 1);
    }
}
