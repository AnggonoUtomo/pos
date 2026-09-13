<?php

namespace Tests\Feature\Modules\Platform\Identity;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use App\Modules\Platform\Identity\Application\Actions\SyncRolePermissions;
use App\Modules\Platform\Identity\Application\Actions\SyncUserRoles;
use App\Modules\Platform\Identity\Database\Seeders\IdentityAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class IdentityAccessActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_user_roles_records_identity_activity_log(): void
    {
        $actor = User::factory()->create();
        $user = User::factory()->create();
        Role::findOrCreate('staff', 'web');
        Role::findOrCreate('manager', 'web');
        $user->assignRole('staff');

        app(SyncUserRoles::class)->handle($actor, $user, ['manager']);

        $this->assertTrue($user->fresh()->hasRole('manager'));
        $this->assertFalse($user->fresh()->hasRole('staff'));

        $activity = Activity::query()
            ->where('event', 'identity.user_roles_synced')
            ->firstOrFail();

        $this->assertSame('identity', $activity->log_name);
        $this->assertSame($user->getKey(), $activity->subject_id);
        $this->assertSame($actor->getKey(), $activity->causer_id);
        $this->assertSame(['staff'], $activity->properties->get('before')['roles']);
        $this->assertSame(['manager'], $activity->properties->get('after')['roles']);
    }

    public function test_sync_role_permissions_records_identity_activity_log(): void
    {
        $actor = User::factory()->create();
        $role = Role::findOrCreate('cashier', 'web');
        Permission::findOrCreate('sales.pos.invoices.create', 'web');
        Permission::findOrCreate('sales.pos.invoices.post', 'web');
        $role->givePermissionTo('sales.pos.invoices.create');

        app(SyncRolePermissions::class)->handle($actor, $role, [
            'sales.pos.invoices.post',
        ]);

        $role->refresh();

        $this->assertTrue($role->hasPermissionTo('sales.pos.invoices.post'));
        $this->assertFalse($role->hasPermissionTo('sales.pos.invoices.create'));

        $activity = Activity::query()
            ->where('event', 'identity.role_permissions_synced')
            ->firstOrFail();

        $this->assertSame('identity', $activity->log_name);
        $this->assertSame($role->getKey(), $activity->subject_id);
        $this->assertSame($actor->getKey(), $activity->causer_id);
        $this->assertSame(['sales.pos.invoices.create'], $activity->properties->get('before')['permissions']);
        $this->assertSame(['sales.pos.invoices.post'], $activity->properties->get('after')['permissions']);
    }

    public function test_inertia_shared_auth_contains_permission_maps_and_super_flag(): void
    {
        $this->seed(IdentityAccessSeeder::class);
        $user = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $request = Request::create('/dashboard');
        $request->setUserResolver(fn () => $user);

        $shared = app(HandleInertiaRequests::class)->share($request);

        $this->assertTrue($shared['auth']['superSystem']);
        $this->assertTrue($shared['auth']['roles']['super-system']);
        $this->assertTrue($shared['auth']['permissions']['platform.identity.users.view']);
    }
}
