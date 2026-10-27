<?php

namespace Tests\Feature\Modules\Platform\Identity;

use App\Models\User;
use App\Modules\Platform\Identity\Database\Seeders\ProductionRolesSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductionRolesTest extends TestCase
{
    use RefreshDatabase;

    private function catalog(): void
    {
        foreach (array_unique(array_merge(...array_values(ProductionRolesSeeder::ROLE_PERMISSIONS))) as $name) {
            Permission::findOrCreate($name, 'web');
        }
    }

    public function test_production_roles_have_exact_permissions_without_creating_users_or_permissions(): void
    {
        $this->catalog();
        $count = Permission::count();
        $this->app->instance('env', 'production');
        $this->artisan('db:seed', ['--class' => ProductionRolesSeeder::class, '--force' => true])->assertSuccessful();

        $this->assertDatabaseCount('roles', 7);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('permissions', $count);
        $this->assertDatabaseCount('activity_log', 7);
        foreach (ProductionRolesSeeder::ROLE_PERMISSIONS as $name => $expected) {
            $role = Role::findByName($name, 'web');
            sort($expected);
            $this->assertSame($expected, $role->permissions()->orderBy('name')->pluck('name')->all());
            $audit = Activity::where('subject_id', (string) $role->id)->sole();
            $this->assertSame('identity.production_role_created', $audit->event);
            $this->assertNull($audit->causer_id);
            $this->assertSame($expected, $audit->properties['after']['permissions']);
        }
    }

    public function test_roles_enforce_operational_boundaries(): void
    {
        $this->catalog();
        $this->seed(ProductionRolesSeeder::class);
        $cashier = User::factory()->create();
        $cashier->assignRole('cashier-operational');
        $this->assertTrue(Gate::forUser($cashier)->allows('sales.pos.invoices.create'));
        $this->assertTrue(Gate::forUser($cashier)->allows('sales.pos.invoices.post'));
        foreach (['sales.pos.invoices.view', 'sales.returns.refund', 'inventory.stock.adjust', 'platform.identity.manage'] as $permission) {
            $this->assertFalse(Gate::forUser($cashier)->allows($permission));
        }
        $owner = User::factory()->create();
        $owner->assignRole('owner-supervisor');
        foreach (['inventory.stock.manage', 'inventory.stock.post', 'inventory.stock.adjust',
            'sales.returns.manage', 'sales.returns.post', 'purchasing.returns.post',
            'purchasing.purchase-orders.post', 'purchasing.purchase-orders.pay'] as $permission) {
            $this->assertTrue(Gate::forUser($owner)->allows($permission));
        }
        $this->assertFalse(Gate::forUser($owner)->allows('sales.returns.refund'));
        $finance = User::factory()->create();
        $finance->assignRole('finance-staff');
        $this->assertTrue(Gate::forUser($finance)->allows('sales.returns.refund'));
        $this->assertTrue(Gate::forUser($finance)->allows('purchasing.returns.refund'));
        $this->assertFalse(Gate::forUser($finance)->allows('purchasing.purchase-orders.post'));
        $warehouse = User::factory()->create();
        $warehouse->assignRole('warehouse-staff');
        $this->assertTrue(Gate::forUser($warehouse)->allows('inventory.transfers.post'));
        $this->assertFalse(Gate::forUser($warehouse)->allows('inventory.stock.adjust'));
        $this->actingAs($cashier)->getJson(route('inventory.items.data'))->assertForbidden();
        $this->actingAs($warehouse)->getJson(route('inventory.items.data'))->assertOk();
    }

    public function test_existing_users_grants_super_role_and_other_guards_are_preserved_and_repeat_is_no_op(): void
    {
        $this->catalog();
        $super = Role::findOrCreate('super-system', 'web');
        $custom = Role::findOrCreate('test', 'web');
        $api = Role::findOrCreate('cashier-operational', 'api');
        $direct = Permission::findOrCreate('existing.custom.view', 'web');
        $super->givePermissionTo($direct);
        $custom->givePermissionTo($direct);
        $user = User::factory()->create();
        $user->assignRole($custom);
        $user->givePermissionTo($direct);
        $before = $this->preserved();

        $this->seed(ProductionRolesSeeder::class);
        $this->seed(ProductionRolesSeeder::class);

        $this->assertSame($before, $this->preserved());
        $this->assertDatabaseCount('roles', 9);
        $this->assertDatabaseCount('activity_log', 6);
        $this->assertSame(['existing.custom.view'], $super->fresh()->permissions->pluck('name')->all());
        $this->assertSame(['existing.custom.view'], $custom->fresh()->permissions->pluck('name')->all());
        $this->assertCount(0, $api->fresh()->permissions);
        $role = Role::findByName('cashier-operational', 'web');
        $this->assertNotSame($api->id, $role->id);
        $this->assertFalse(Gate::forUser($user->fresh())->allows('sales.pos.invoices.post'));
        $user->assignRole($super);
        $this->assertTrue(Gate::forUser($user->fresh())->allows('some.future.permission'));
    }

    public function test_missing_permission_aborts_without_creating_partial_roles(): void
    {
        $this->catalog();
        Permission::findByName('sales.pos.invoices.post', 'web')->delete();
        $this->expectException(DomainException::class);
        try {
            $this->seed(ProductionRolesSeeder::class);
        } finally {
            $this->assertDatabaseCount('roles', 0);
            $this->assertDatabaseCount('activity_log', 0);
        }
    }

    public function test_conflicting_existing_role_is_not_silently_expanded_or_reduced(): void
    {
        $this->catalog();
        $role = Role::findOrCreate('cashier-operational', 'web');
        $role->givePermissionTo('inventory.stock.adjust');
        $before = $role->permissions->pluck('name')->all();
        $this->expectException(DomainException::class);
        try {
            $this->seed(ProductionRolesSeeder::class);
        } finally {
            $this->assertDatabaseCount('roles', 1);
            $this->assertDatabaseCount('activity_log', 0);
            $this->assertSame($before, $role->fresh()->permissions->pluck('name')->all());
        }
    }

    public function test_audit_failure_rolls_back_all_new_roles_and_grants(): void
    {
        $this->catalog();
        Activity::saving(function (): void {
            throw new RuntimeException('Audit unavailable');
        });
        try {
            $this->seed(ProductionRolesSeeder::class);
            $this->fail('Expected audit failure');
        } catch (RuntimeException $exception) {
            $this->assertSame('Audit unavailable', $exception->getMessage());
            $this->assertDatabaseCount('roles', 0);
            $this->assertDatabaseCount('role_has_permissions', 0);
            $this->assertDatabaseCount('activity_log', 0);
        } finally {
            Activity::flushEventListeners();
        }
    }

    private function preserved(): array
    {
        return [DB::table('users')->orderBy('id')->get()->toJson(),
            DB::table('model_has_roles')->orderBy('model_id')->orderBy('role_id')->get()->toJson(),
            DB::table('model_has_permissions')->orderBy('model_id')->orderBy('permission_id')->get()->toJson()];
    }
}
