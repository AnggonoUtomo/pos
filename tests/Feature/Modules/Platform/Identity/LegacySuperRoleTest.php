<?php

namespace Tests\Feature\Modules\Platform\Identity;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use App\Modules\Platform\Identity\Application\Actions\MigrateLegacySuperRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LegacySuperRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_does_not_change_access(): void
    {
        $role = Role::findOrCreate('super-admin', 'web');
        $this->artisan('identity:migrate-super-role')->assertSuccessful();
        $this->assertSame('super-admin', $role->fresh()->name);
        $this->assertDatabaseMissing('roles', ['name' => 'super-system', 'guard_name' => 'web']);
        $this->assertDatabaseCount('activity_log', 0);
    }

    public function test_apply_preserves_identity_and_relations_and_restores_inventory_access(): void
    {
        $role = Role::findOrCreate('super-admin', 'web');
        $permission = Permission::findOrCreate('platform.identity.users.view', 'web');
        $role->givePermissionTo($permission);
        $user = User::factory()->create();
        $user->assignRole($role);
        $attributes = $user->fresh()->getAttributes();
        $userPivots = DB::table('model_has_roles')->get()->toArray();
        $permissionPivots = DB::table('role_has_permissions')->get()->toArray();
        $this->actingAs($user)->getJson(route('inventory.items.data'))->assertForbidden();

        $this->artisan('identity:migrate-super-role', ['--apply' => true])->assertSuccessful();

        $this->assertSame('super-system', $role->fresh()->name);
        $this->assertTrue($attributes === $user->fresh()->getAttributes(), 'User attributes must remain unchanged.');
        $this->assertEquals($userPivots, DB::table('model_has_roles')->get()->toArray());
        $this->assertEquals($permissionPivots, DB::table('role_has_permissions')->get()->toArray());
        $this->assertDatabaseCount('roles', 1);
        $this->assertCount(1, $role->fresh()->permissions);
        $this->assertTrue(Gate::forUser($user->fresh())->allows('inventory.manage'));
        $this->actingAs($user->fresh())->getJson(route('inventory.items.data'))->assertOk();
        $request = Request::create('/dashboard');
        $request->setUserResolver(fn () => $user->fresh());
        $shared = app(HandleInertiaRequests::class)->share($request);
        $this->assertTrue($shared['auth']['superSystem']);
        $this->assertTrue($shared['auth']['roles']['super-system']);
        $activity = Activity::where('event', 'identity.legacy_super_role_migrated')->sole();
        $this->assertTrue($activity->subject->is($role));
        $this->assertSame('super-admin', $activity->properties['before']['name']);
        $this->assertSame('super-system', $activity->properties['after']['name']);
    }

    public function test_repeat_and_existing_canonical_role_are_no_ops(): void
    {
        Role::findOrCreate('super-admin', 'web');
        $this->assertTrue(app(MigrateLegacySuperRole::class)->handle());
        $this->assertFalse(app(MigrateLegacySuperRole::class)->handle());
        $this->assertDatabaseCount('activity_log', 1);
        $this->assertDatabaseCount('roles', 1);
    }

    public function test_conflicting_roles_fail_without_merging_or_changing_access(): void
    {
        Role::findOrCreate('super-admin', 'web');
        Role::findOrCreate('super-system', 'web');
        $this->artisan('identity:migrate-super-role', ['--apply' => true])->assertFailed();
        $this->assertDatabaseHas('roles', ['name' => 'super-admin', 'guard_name' => 'web']);
        $this->assertDatabaseCount('roles', 2);
        $this->assertDatabaseCount('activity_log', 0);
    }

    public function test_other_guards_and_users_do_not_gain_access(): void
    {
        Role::findOrCreate('super-admin', 'api');
        $user = User::factory()->create();
        $this->assertFalse(app(MigrateLegacySuperRole::class)->handle());
        $this->assertDatabaseHas('roles', ['name' => 'super-admin', 'guard_name' => 'api']);
        $this->assertFalse(Gate::forUser($user)->allows('inventory.manage'));
        $this->assertDatabaseCount('activity_log', 0);
    }

    public function test_audit_failure_rolls_back_rename(): void
    {
        $role = Role::findOrCreate('super-admin', 'web');
        Activity::saving(function (): void {
            throw new RuntimeException('Audit unavailable');
        });
        try {
            app(MigrateLegacySuperRole::class)->handle();
            $this->fail('Expected audit failure');
        } catch (RuntimeException $exception) {
            $this->assertSame('Audit unavailable', $exception->getMessage());
        }
        $this->assertSame('super-admin', $role->fresh()->name);
        $this->assertDatabaseCount('activity_log', 0);
    }
}
