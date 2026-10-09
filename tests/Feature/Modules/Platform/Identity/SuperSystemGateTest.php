<?php

namespace Tests\Feature\Modules\Platform\Identity;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuperSystemGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_system_bypasses_permission_without_explicit_grant(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('super-system', 'web'));
        $this->assertTrue(Gate::forUser($user)->allows('inventory.manage'));
        $this->assertTrue(Gate::forUser($user)->allows('inventory.view'));
        $this->assertCount(0, $user->getAllPermissions());
    }

    public function test_other_users_keep_normal_spatie_permission_rules(): void
    {
        Permission::findOrCreate('inventory.view', 'web');
        Permission::findOrCreate('inventory.manage', 'web');
        $user = User::factory()->create();
        $user->givePermissionTo('inventory.view');
        $this->assertTrue(Gate::forUser($user)->allows('inventory.view'));
        $this->assertFalse(Gate::forUser($user)->allows('inventory.manage'));
        $this->assertFalse(Gate::allows('inventory.manage'));
    }
}
