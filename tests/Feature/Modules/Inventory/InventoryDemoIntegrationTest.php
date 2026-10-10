<?php

namespace Tests\Feature\Modules\Inventory;

use App\Models\User;
use App\Modules\Inventory\Items\Database\Seeders\ItemsAccessSeeder;
use App\Modules\Inventory\Items\Database\Seeders\ItemsDemoSeeder;
use App\Modules\Inventory\Items\Infrastructure\Persistence\Models\Item;
use App\Modules\Inventory\Items\Infrastructure\Persistence\Models\ItemUnit;
use App\Modules\Inventory\Units\Database\Seeders\UnitsAccessSeeder;
use App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder;
use App\Modules\Inventory\Units\Infrastructure\Persistence\Models\Unit;
use App\Modules\Inventory\Warehouses\Database\Seeders\WarehousesAccessSeeder;
use App\Modules\Inventory\Warehouses\Database\Seeders\WarehousesDemoSeeder;
use App\Modules\Inventory\Warehouses\Infrastructure\Persistence\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InventoryDemoIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function seedInventory(): void
    {
        foreach ([WarehousesAccessSeeder::class, UnitsAccessSeeder::class, ItemsAccessSeeder::class,
            WarehousesDemoSeeder::class, UnitsDemoSeeder::class, ItemsDemoSeeder::class] as $seeder) {
            $this->seed($seeder);
        }
    }

    private function snapshot(): array
    {
        return array_map(fn (string $table) => DB::table($table)->orderBy('id')->get()->toJson(),
            ['warehouses', 'units', 'items', 'item_units', 'activity_log', 'permissions', 'roles', 'users']);
    }

    public function test_complete_demo_is_relational_audited_readable_and_does_not_grant_access(): void
    {
        $role = Role::findOrCreate('observer', 'web');
        $role->givePermissionTo([
            Permission::findOrCreate('existing.view', 'web'),
            Permission::findOrCreate('inventory.view', 'web'),
        ]);
        $user = User::factory()->create();
        $user->assignRole($role);
        $this->seedInventory();
        $this->assertDatabaseCount('warehouses', 2);
        $this->assertDatabaseCount('units', 7);
        $this->assertDatabaseCount('items', 3);
        $this->assertDatabaseCount('item_units', 7);
        $this->assertDatabaseCount('activity_log', 19);
        $this->assertDatabaseCount('permissions', 3);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('roles', 1);
        $this->assertSame(['existing.view', 'inventory.view'], $role->fresh()->permissions->pluck('name')->sort()->values()->all());
        $this->assertFalse($user->fresh()->can('inventory.manage'));
        $this->assertTrue(Activity::query()->get()->every(fn ($log) => Str::isUlid($log->subject_id) && $log->subject !== null));
        $this->actingAs($user);
        foreach (['warehouses' => 2, 'units' => 7, 'items' => 3] as $module => $count) {
            $this->getJson("/inventory/{$module}/data")->assertOk()->assertJsonPath('meta.total', $count);
            $this->postJson("/inventory/{$module}", [])->assertForbidden();
        }
        $before = $this->snapshot();
        $this->seedInventory();
        $this->assertSame($before, $this->snapshot());
    }

    public function test_integrated_repeat_preserves_user_edits_inactive_and_soft_deleted_masters(): void
    {
        $this->seedInventory();
        Warehouse::query()->where('code', 'WH-MAIN')->sole()->update(['name' => 'Gudang User', 'is_active' => false]);
        Warehouse::query()->where('code', 'WH-SECONDARY')->sole()->delete();
        Unit::query()->where('code', 'PCS')->sole()->update(['symbol' => 'bh', 'is_active' => false]);
        Unit::query()->where('code', 'BOX')->sole()->delete();
        Item::query()->where('sku', 'DEMO-WATER-600')->sole()->update(['name' => 'Barang User', 'is_active' => false]);
        Item::query()->where('sku', 'DEMO-TEA-250')->sole()->delete();
        ItemUnit::query()->where('barcode', 'DEMO-RICE-G')->sole()->update(['barcode' => 'USER-RICE-G', 'is_sale_unit' => false]);
        ItemUnit::query()->where('barcode', 'DEMO-RICE-KG')->sole()->delete();
        $before = $this->snapshot();
        $this->seedInventory();
        $this->assertSame($before, $this->snapshot());
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('roles', 0);
        $this->assertDatabaseCount('role_has_permissions', 0);
        $this->assertDatabaseCount('model_has_roles', 0);
        $this->assertDatabaseCount('model_has_permissions', 0);
    }
}
