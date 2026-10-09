<?php

namespace Tests\Feature\Modules\Inventory\Warehouses;

use App\Models\User;
use App\Modules\Inventory\Warehouses\Database\Seeders\WarehousesAccessSeeder;
use App\Modules\Inventory\Warehouses\Database\Seeders\WarehousesDemoSeeder;
use App\Modules\Inventory\Warehouses\Infrastructure\Persistence\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WarehousesSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seed_is_idempotent_and_preserves_user_edits_and_deleted_rows(): void
    {
        $this->seed(WarehousesDemoSeeder::class);
        $ids = Warehouse::query()->orderBy('code')->pluck('id')->all();
        $main = Warehouse::query()->where('code', 'WH-MAIN')->sole();
        $main->update(['name' => 'Gudang Milik User', 'is_active' => false]);
        $secondary = Warehouse::query()->where('code', 'WH-SECONDARY')->sole();
        $secondary->delete();

        $this->seed(WarehousesDemoSeeder::class);

        $this->assertSame($ids, Warehouse::withTrashed()->orderBy('code')->pluck('id')->all());
        $this->assertSame('Gudang Milik User', $main->fresh()->name);
        $this->assertFalse($main->fresh()->is_active);
        $this->assertSoftDeleted($secondary);
        $this->assertDatabaseCount('warehouses', 2);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_demo_seed_refuses_production_before_writing_data(): void
    {
        $this->app->instance('env', 'production');

        try {
            app(WarehousesDemoSeeder::class)->run();
            $this->fail('Demo seed must refuse production.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('local/testing', $exception->getMessage());
            $this->assertDatabaseCount('warehouses', 0);
        }
    }

    public function test_access_seed_is_idempotent_and_does_not_grant_roles_or_permissions(): void
    {
        $existing = Permission::findOrCreate('existing.view', 'web');
        $role = Role::findOrCreate('warehouse-observer', 'web');
        $role->givePermissionTo($existing);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->seed(WarehousesAccessSeeder::class);
        $this->seed(WarehousesAccessSeeder::class);

        $this->assertDatabaseHas('permissions', ['name' => 'inventory.view', 'guard_name' => 'web']);
        $this->assertDatabaseHas('permissions', ['name' => 'inventory.manage', 'guard_name' => 'web']);
        $this->assertDatabaseCount('permissions', 3);
        $this->assertSame(['existing.view'], $role->fresh()->permissions->pluck('name')->all());
        $this->assertFalse($user->fresh()->hasPermissionTo('inventory.manage'));
        $this->assertDatabaseCount('roles', 1);
        $this->assertDatabaseCount('users', 1);
    }
}
