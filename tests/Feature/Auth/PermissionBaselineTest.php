<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Modules\Platform\Identity\Database\Seeders\IdentityAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionBaselineTest extends TestCase
{
    use RefreshDatabase;

    public function test_identity_access_seed_creates_super_system_role_permissions_and_user(): void
    {
        $this->seed(IdentityAccessSeeder::class);

        $user = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->assertTrue(Role::query()->where('name', 'super-system')->exists());
        $this->assertTrue(Permission::query()->where('name', 'platform.identity.users.view')->exists());
        $this->assertTrue($user->hasRole('super-system'));
        $this->assertTrue($user->can('platform.identity.users.view'));
    }

    public function test_permission_middleware_allows_only_users_with_permission(): void
    {
        Route::middleware(['web', 'auth', 'permission:platform.identity.users.view'])
            ->get('/_test/identity-permission', fn () => response('OK'))
            ->name('test.identity-permission');

        $plainUser = User::factory()->create();

        $this->actingAs($plainUser)
            ->get('/_test/identity-permission')
            ->assertForbidden();

        $this->seed(IdentityAccessSeeder::class);
        $superSystem = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($superSystem)
            ->get('/_test/identity-permission')
            ->assertOk();
    }

    public function test_activity_log_can_record_identity_event(): void
    {
        $user = User::factory()->create();

        activity('identity')
            ->causedBy($user)
            ->performedOn($user)
            ->event('identity.test_event')
            ->withProperties(['source' => 'permission_baseline_test'])
            ->log('Identity audit baseline event');

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'identity',
            'event' => 'identity.test_event',
            'subject_type' => User::class,
            'subject_id' => $user->getKey(),
            'causer_type' => User::class,
            'causer_id' => $user->getKey(),
        ]);
    }
}
