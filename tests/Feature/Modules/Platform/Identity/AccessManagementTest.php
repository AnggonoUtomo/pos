<?php

namespace Tests\Feature\Modules\Platform\Identity;

use App\Models\User;
use App\Modules\Platform\Identity\Application\Actions\SyncUserRoles;
use App\Modules\Platform\Identity\Database\Seeders\IdentityPermissionsSeeder;
use App\Modules\Platform\Identity\Database\Seeders\IdentityRolesDemoSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccessManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function superUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('super-system', 'web'));

        return $user;
    }

    private function permission(string $name): void
    {
        Permission::findOrCreate($name, 'web');
    }

    public function test_guest_and_readonly_permissions_are_enforced(): void
    {
        $this->get('/platform/identity')->assertRedirect('/login');
        $viewer = User::factory()->create();
        $this->actingAs($viewer)->get('/platform/identity')->assertForbidden();
        $this->permission('platform.identity.view');
        $viewer->givePermissionTo('platform.identity.view');
        $this->get('/platform/identity')->assertOk();
        $this->postJson('/platform/identity/roles', ['name' => 'staff'])->assertForbidden();
    }

    public function test_role_crud_and_permission_sync_are_audited(): void
    {
        $actor = $this->superUser();
        $this->permission('inventory.view');
        $this->actingAs($actor)->postJson('/platform/identity/roles', ['name' => ' Staff ', 'guard_name' => 'api'])
            ->assertCreated();
        $role = Role::findByName('staff', 'web');
        $this->putJson('/platform/identity/roles/'.$role->id, ['name' => 'warehouse-staff'])->assertOk();
        $this->putJson('/platform/identity/roles/'.$role->id.'/permissions', ['permissions' => ['inventory.view']])->assertOk();
        $this->assertTrue($role->fresh()->hasPermissionTo('inventory.view'));
        $this->putJson('/platform/identity/roles/'.$role->id.'/permissions', ['permissions' => ['inventory.view']])->assertOk();
        $this->assertSame(1, Activity::where('event', 'identity.role_permissions_synced')->count());
        $this->deleteJson('/platform/identity/roles/'.$role->id)->assertOk();
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
        $this->assertSame(4, Activity::where('log_name', 'identity')->count());
        $this->assertSame(4, Activity::where('causer_id', $actor->id)->count());
    }

    public function test_reserved_and_assigned_roles_are_protected(): void
    {
        $actor = $this->superUser();
        $super = Role::findByName('super-system', 'web');
        $this->actingAs($actor)->postJson('/platform/identity/roles', ['name' => 'SUPER-ADMIN'])->assertUnprocessable();
        $this->putJson('/platform/identity/roles/'.$super->id, ['name' => 'renamed'])->assertUnprocessable();
        $this->deleteJson('/platform/identity/roles/'.$super->id)->assertUnprocessable();
        $this->putJson('/platform/identity/roles/'.$super->id.'/permissions', ['permissions' => []])->assertUnprocessable();
        $role = Role::findOrCreate('staff', 'web');
        User::factory()->create()->assignRole($role);
        $this->deleteJson('/platform/identity/roles/'.$role->id)->assertUnprocessable();
        $this->assertDatabaseCount('activity_log', 0);
    }

    public function test_user_role_sync_preserves_accounts_and_last_super(): void
    {
        $actor = $this->superUser();
        $user = User::factory()->create();
        $before = $user->fresh()->getAttributes();
        Role::findOrCreate('staff', 'web');
        $this->actingAs($actor)->putJson('/platform/identity/users/'.$user->id.'/roles', ['roles' => ['staff']])->assertOk();
        $this->assertTrue($user->fresh()->hasRole('staff'));
        $this->assertTrue($before === $user->fresh()->getAttributes());
        $this->putJson('/platform/identity/users/'.$actor->id.'/roles', ['roles' => []])->assertUnprocessable();
        $this->assertTrue($actor->fresh()->hasRole('super-system'));
        $this->putJson('/platform/identity/users/'.$user->id.'/roles', ['roles' => ['super-system']])->assertOk();
        $this->putJson('/platform/identity/users/'.$actor->id.'/roles', ['roles' => []])->assertOk();
        $this->assertFalse($actor->fresh()->hasRole('super-system'));
        $this->assertTrue($user->fresh()->hasRole('super-system'));
    }

    public function test_manager_cannot_escalate_permissions_or_super_assignment(): void
    {
        foreach (['platform.identity.manage', 'inventory.view', 'inventory.manage'] as $name) {
            $this->permission($name);
        }
        $actor = User::factory()->create();
        $actor->givePermissionTo(['platform.identity.manage', 'inventory.view']);
        $role = Role::findOrCreate('staff', 'web');
        $privileged = Role::findOrCreate('inventory-manager', 'web');
        $privileged->givePermissionTo('inventory.manage');
        $super = $this->superUser();
        $user = User::factory()->create();
        $this->actingAs($actor)->putJson('/platform/identity/roles/'.$role->id.'/permissions', ['permissions' => ['inventory.manage']])->assertForbidden();
        $this->putJson('/platform/identity/roles/'.$role->id.'/permissions', ['permissions' => ['inventory.view']])->assertOk();
        $this->putJson('/platform/identity/users/'.$user->id.'/roles', ['roles' => ['super-system']])->assertForbidden();
        $this->putJson('/platform/identity/users/'.$super->id.'/roles', ['roles' => ['staff']])->assertForbidden();
        $this->putJson('/platform/identity/users/'.$user->id.'/roles', ['roles' => ['inventory-manager']])->assertForbidden();
        $this->putJson('/platform/identity/users/'.$user->id.'/roles', ['roles' => ['staff']])->assertOk();
    }

    public function test_validation_guard_scope_and_not_found(): void
    {
        $this->actingAs($this->superUser());
        $role = Role::findOrCreate('staff', 'web');
        $api = Role::findOrCreate('api-staff', 'api');
        $this->postJson('/platform/identity/roles', ['name' => 'staff'])->assertUnprocessable();
        $this->postJson('/platform/identity/roles', ['name' => 'bad name'])->assertUnprocessable();
        $this->putJson('/platform/identity/roles/'.$role->id.'/permissions', ['permissions' => ['made.up']])->assertUnprocessable();
        $this->putJson('/platform/identity/roles/'.$api->id, ['name' => 'changed'])->assertNotFound();
        $this->deleteJson('/platform/identity/roles/999999')->assertNotFound();
        $this->get('/platform/identity?tab=invalid')->assertRedirect();
        $this->assertDatabaseCount('activity_log', 0);
    }

    public function test_audit_failure_rolls_back_mutation(): void
    {
        $this->actingAs($this->superUser());
        Activity::saving(function (): void {
            throw new RuntimeException('Audit unavailable');
        });
        $this->postJson('/platform/identity/roles', ['name' => 'staff'])->assertServerError();
        $this->assertDatabaseMissing('roles', ['name' => 'staff']);
    }

    public function test_pages_filter_and_paginate_without_credentials(): void
    {
        $actor = $this->superUser();
        foreach (range(1, 27) as $index) {
            Role::findOrCreate('staff-'.$index, 'web');
        }
        Role::findOrCreate('api-only', 'api');
        $this->actingAs($actor)->get('/platform/identity')->assertInertia(fn (Assert $page) => $page
            ->component('platform/identity/index', false)->where('records.meta.total', 28)->has('records.data', 25));
        $this->get('/platform/identity?tab=roles&search=staff-27')->assertInertia(fn (Assert $page) => $page
            ->where('records.meta.total', 1)->where('records.data.0.name', 'staff-27'));
        $this->get('/platform/identity?tab=users')->assertInertia(fn (Assert $page) => $page
            ->where('records.data.0.id', $actor->id)->missing('records.data.0.password')->missing('records.data.0.remember_token'));
    }

    public function test_permission_and_role_demo_seeders_are_safe_and_idempotent(): void
    {
        foreach (['inventory.view', 'inventory.manage', 'sales.pos.invoices.create'] as $name) {
            $this->permission($name);
        }
        $this->seed(IdentityPermissionsSeeder::class);
        $this->seed(IdentityRolesDemoSeeder::class);
        $role = Role::findByName('inventory-viewer', 'web');
        $role->syncPermissions([]);
        $this->seed(IdentityPermissionsSeeder::class);
        $this->seed(IdentityRolesDemoSeeder::class);
        $this->assertDatabaseCount('users', 0);
        $this->assertCount(0, $role->fresh()->permissions);
        $this->assertDatabaseHas('permissions', ['name' => 'platform.identity.manage']);
        $this->assertDatabaseCount('roles', 3);
    }

    public function test_unknown_duplicate_assignments_and_reserved_rename_are_rejected(): void
    {
        $this->actingAs($this->superUser());
        $role = Role::findOrCreate('staff', 'web');
        $user = User::factory()->create();
        $this->putJson('/platform/identity/roles/'.$role->id, ['name' => 'super-system'])->assertUnprocessable();
        $this->putJson('/platform/identity/users/'.$user->id.'/roles', ['roles' => ['unknown']])->assertUnprocessable();
        $this->putJson('/platform/identity/users/'.$user->id.'/roles', ['roles' => ['staff', 'staff']])->assertUnprocessable();
        $this->permission('inventory.view');
        $this->putJson('/platform/identity/roles/'.$role->id.'/permissions', ['permissions' => ['inventory.view', ' inventory.view ']])->assertUnprocessable();
        $this->assertDatabaseCount('activity_log', 0);
    }

    public function test_sync_wrappers_require_application_authorization(): void
    {
        $actor = User::factory()->create();
        $user = User::factory()->create();
        Role::findOrCreate('staff', 'web');
        $this->expectException(AuthorizationException::class);
        app(SyncUserRoles::class)->handle($actor, $user, ['staff']);
    }

    public function test_demo_refuses_production_and_missing_permission_rolls_back(): void
    {
        try {
            $this->seed(IdentityRolesDemoSeeder::class);
            $this->fail('Missing prerequisite must fail.');
        } catch (PermissionDoesNotExist) {
            $this->assertDatabaseCount('roles', 0);
        }
        $this->app->instance('env', 'production');
        $this->expectException(\LogicException::class);
        $this->seed(IdentityRolesDemoSeeder::class);
    }
}
