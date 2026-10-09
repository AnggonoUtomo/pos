<?php

namespace Tests\Feature\Modules\Inventory\Items;

use App\Models\User;
use App\Modules\Inventory\Items\Database\Seeders\ItemsAccessSeeder;
use App\Modules\Inventory\Items\Database\Seeders\ItemsDemoSeeder;
use App\Modules\Inventory\Items\Domain\ValueObjects\ConversionRatio;
use App\Modules\Inventory\Items\Infrastructure\Persistence\Models\Item;
use App\Modules\Inventory\Items\Infrastructure\Persistence\Models\ItemUnit;
use App\Modules\Inventory\Units\Application\Contracts\UnitLookup;
use App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ItemsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_relations_conversions_and_seed_repeat_are_valid(): void
    {
        $this->seed(UnitsDemoSeeder::class);
        $this->seed(ItemsDemoSeeder::class);
        $ids = Item::query()->orderBy('sku')->pluck('id')->all();
        $this->seed(ItemsDemoSeeder::class);
        $this->assertSame($ids, Item::query()->orderBy('sku')->pluck('id')->all());
        $this->assertDatabaseCount('items', 3);
        $this->assertDatabaseCount('item_units', 7);
        $this->assertDatabaseCount('activity_log', 17);
        $this->assertSame(7, ItemUnit::query()->whereNotNull('barcode')->distinct()->count('barcode'));
        $box = app(UnitLookup::class)->findActiveByCode('BOX')->id;
        $kg = app(UnitLookup::class)->findActiveByCode('KG')->id;
        $water = Item::query()->where('sku', 'DEMO-WATER-600')->sole();
        $tea = Item::query()->where('sku', 'DEMO-TEA-250')->sole();
        $rice = Item::query()->where('sku', 'DEMO-RICE')->sole();
        $this->assertSame('12.000000', $water->units()->where('unit_id', $box)->sole()->conversion_ratio);
        $this->assertSame('24.000000', $tea->units()->where('unit_id', $box)->sole()->conversion_ratio);
        $ratio = $rice->units()->where('unit_id', $kg)->sole()->conversion_ratio;
        $this->assertSame('125.000000', ConversionRatio::fromString($ratio)->toBaseQuantity('0.125'));
        foreach (Item::query()->get() as $item) {
            $this->assertSame('1.000000', $item->units()->where('unit_id', $item->base_unit_id)->sole()->conversion_ratio);
        }
    }

    public function test_seed_preserves_edits_inactive_and_soft_deleted_data(): void
    {
        $this->seed(UnitsDemoSeeder::class);
        $this->seed(ItemsDemoSeeder::class);
        $water = Item::query()->where('sku', 'DEMO-WATER-600')->sole();
        $water->update(['name' => 'Barang User', 'is_active' => false]);
        $water->units()->firstOrFail()->update(['barcode' => 'USER-BARCODE', 'is_purchase_unit' => false]);
        $tea = Item::query()->where('sku', 'DEMO-TEA-250')->sole();
        $tea->units()->firstOrFail()->delete();
        $tea->delete();
        $items = Item::withTrashed()->orderBy('id')->get()->toArray();
        $units = ItemUnit::withTrashed()->orderBy('id')->get()->toArray();
        $audits = Activity::query()->count();
        $this->seed(ItemsDemoSeeder::class);
        $this->assertSame($items, Item::withTrashed()->orderBy('id')->get()->toArray());
        $this->assertSame($units, ItemUnit::withTrashed()->orderBy('id')->get()->toArray());
        $this->assertSame($audits, Activity::query()->count());
    }

    public function test_missing_unit_rolls_back_entire_demo_batch_and_audit(): void
    {
        $this->seed(UnitsDemoSeeder::class);
        DB::table('units')->where('code', 'KG')->update(['is_active' => false]);
        try {
            $this->seed(ItemsDemoSeeder::class);
            $this->fail('Missing active unit must fail.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('KG', $exception->getMessage());
            $this->assertDatabaseCount('items', 0);
            $this->assertDatabaseCount('item_units', 0);
            $this->assertDatabaseCount('activity_log', 7);
        }
    }

    public function test_seed_without_unit_prerequisites_does_not_create_units(): void
    {
        try {
            $this->seed(ItemsDemoSeeder::class);
            $this->fail('Seed must require existing Units.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('PCS', $exception->getMessage());
            $this->assertDatabaseCount('items', 0);
            $this->assertDatabaseCount('units', 0);
            $this->assertDatabaseCount('activity_log', 0);
        }
    }

    public function test_demo_refuses_production_before_writing(): void
    {
        $this->app['env'] = 'production';
        try {
            app(ItemsDemoSeeder::class)->run();
            $this->fail('Demo must refuse production.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('local/testing', $exception->getMessage());
            $this->assertDatabaseCount('items', 0);
            $this->assertDatabaseCount('item_units', 0);
            $this->assertDatabaseCount('units', 0);
            $this->assertDatabaseCount('activity_log', 0);
        } finally {
            $this->app['env'] = 'testing';
        }
    }

    public function test_access_seed_is_idempotent_without_changing_user_access(): void
    {
        $user = User::factory()->create();
        $role = Role::findOrCreate('cashier', 'web');
        $user->assignRole($role);
        $this->seed(ItemsAccessSeeder::class);
        $this->seed(ItemsAccessSeeder::class);
        $this->assertSame(['inventory.manage', 'inventory.view'], Permission::query()->orderBy('name')->pluck('name')->all());
        $this->assertSame(['cashier'], $user->fresh()->getRoleNames()->all());
        $this->assertCount(0, $user->fresh()->getAllPermissions());
        $this->assertDatabaseCount('role_has_permissions', 0);
        $this->assertDatabaseCount('model_has_permissions', 0);
    }
}
