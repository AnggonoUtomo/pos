<?php

namespace Tests\Feature\Modules\Platform\Identity;

use App\Models\User;
use App\Modules\Platform\Identity\Application\Actions\ManageUsers;
use App\Modules\Platform\Identity\Database\Seeders\IdentityPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(IdentityPermissionsSeeder::class);
    }

    private function superUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('super-system', 'web'));

        return $user;
    }

    private function payload(array $extra = []): array
    {
        $password = bin2hex(random_bytes(16));

        return array_replace(['name' => 'Kasir Baru', 'email' => 'new@example.test',
            'password' => $password, 'password_confirmation' => $password, 'roles' => []], $extra);
    }

    public function test_creation_is_hashed_and_allowlisted_with_atomic_role_assignment(): void
    {
        $actor = $this->superUser();
        Role::findOrCreate('cashier', 'web');
        $payload = $this->payload(['email' => ' NEW@EXAMPLE.TEST ', 'roles' => ['cashier'],
            'id' => $actor->id, 'guard_name' => 'api', 'email_verified_at' => now(), 'remember_token' => 'ignored']);
        $this->actingAs($actor)->postJson('/platform/identity/users', $payload)->assertCreated();
        $user = User::where('email', 'new@example.test')->firstOrFail();
        $this->assertNotSame($actor->id, $user->id);
        $this->assertTrue(Hash::check($payload['password'], $user->password));
        $this->assertTrue($user->hasRole('cashier', 'web'));
        $this->assertNull($user->remember_token);
        $this->assertNull($user->email_verified_at);
        $audit = Activity::where('event', 'identity.user_created')->firstOrFail();
        $this->assertEquals(['before' => [], 'after' => ['roles' => ['cashier']]], $audit->properties->all());
        $this->assertEquals($actor->id, $audit->causer_id);
        $this->assertEquals($user->id, $audit->subject_id);
    }

    public function test_guest_and_operation_permissions_are_enforced(): void
    {
        $this->postJson('/platform/identity/users', $this->payload())->assertUnauthorized();
        $actor = User::factory()->create();
        $this->actingAs($actor)->postJson('/platform/identity/users', $this->payload())->assertForbidden();
        $actor->givePermissionTo('platform.identity.users.create');
        $this->postJson('/platform/identity/users', $this->payload())->assertCreated();
        $target = User::where('email', 'new@example.test')->firstOrFail();
        $this->putJson('/platform/identity/users/'.$target->id, ['name' => 'Changed', 'email' => $target->email])->assertForbidden();
    }

    public function test_role_selection_requires_manage_and_cannot_escalate(): void
    {
        $actor = User::factory()->create();
        $actor->givePermissionTo('platform.identity.users.create');
        Role::findOrCreate('cashier', 'web');
        $this->actingAs($actor)->postJson('/platform/identity/users', $this->payload(['roles' => ['cashier']]))->assertForbidden();
        $this->assertDatabaseCount('users', 1);
        $actor->givePermissionTo('platform.identity.manage');
        $this->superUser();
        $this->postJson('/platform/identity/users', $this->payload(['roles' => ['super-system']]))->assertForbidden();
        Permission::findOrCreate('inventory.manage', 'web');
        Role::findOrCreate('inventory-manager', 'web')->givePermissionTo('inventory.manage');
        $this->postJson('/platform/identity/users', $this->payload(['roles' => ['inventory-manager']]))->assertForbidden();
        $this->postJson('/platform/identity/users', $this->payload(['roles' => ['cashier']]))->assertCreated();
    }

    public function test_invalid_and_case_insensitive_duplicate_email_and_password_are_rejected(): void
    {
        $this->actingAs($this->superUser());
        User::factory()->create(['email' => 'Existing@Example.Test']);
        $this->postJson('/platform/identity/users', $this->payload(['email' => 'existing@example.test']))->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->postJson('/platform/identity/users', $this->payload(['name' => '   ', 'email' => 'bad', 'password_confirmation' => 'wrong']))->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'password']);
        $this->postJson('/platform/identity/users', $this->payload(['password' => 'short', 'password_confirmation' => 'short']))->assertUnprocessable();
        $this->postJson('/platform/identity/users', $this->payload(['roles' => ['unknown']]))->assertUnprocessable();
        $this->assertDatabaseCount('activity_log', 0);
    }

    public function test_edit_preserves_credentials_and_access_and_audits_only_changed_fields(): void
    {
        $this->actingAs($this->superUser());
        $target = User::factory()->create();
        $password = $target->password;
        Role::findOrCreate('cashier', 'web');
        $target->assignRole('cashier');
        $this->putJson('/platform/identity/users/'.$target->id, ['name' => ' New Name ', 'email' => ' CHANGED@EXAMPLE.TEST ',
            'password' => 'ignored', 'roles' => ['super-system'], 'is_active' => false])->assertOk();
        $target->refresh();
        $this->assertSame('New Name', $target->name);
        $this->assertSame('changed@example.test', $target->email);
        $this->assertSame($password, $target->password);
        $this->assertTrue($target->hasRole('cashier'));
        $this->assertFalse($target->hasRole('super-system'));
        $this->assertNull($target->email_verified_at);
        $audit = Activity::where('event', 'identity.user_updated')->firstOrFail();
        $this->assertEquals(['before' => [], 'after' => ['changed_fields' => ['name', 'email']]], $audit->properties->all());
        $this->putJson('/platform/identity/users/'.$target->id, ['name' => $target->name, 'email' => $target->email])->assertOk();
        $this->assertSame(1, Activity::where('event', 'identity.user_updated')->count());
    }

    public function test_operator_cannot_take_over_super_or_higher_privilege_accounts(): void
    {
        $super = $this->superUser();
        $actor = User::factory()->create();
        $actor->givePermissionTo('platform.identity.users.update');
        Permission::findOrCreate('inventory.manage', 'web');
        $higher = User::factory()->create();
        $higher->givePermissionTo('inventory.manage');
        $otherGuard = User::factory()->create();
        $otherGuard->roles()->attach(Role::findOrCreate('api-manager', 'api'));
        $this->actingAs($actor);
        foreach ([$super, $higher, $otherGuard] as $target) {
            $this->putJson('/platform/identity/users/'.$target->id, ['name' => 'Changed', 'email' => 'takeover@example.test'])->assertForbidden();
            $this->assertSame($target->email, $target->fresh()->email);
        }
        $ordinary = User::factory()->create();
        $this->putJson('/platform/identity/users/'.$ordinary->id, ['name' => 'Changed', 'email' => $ordinary->email])->assertOk();
    }

    public function test_audit_failure_rolls_back_create_roles_and_profile(): void
    {
        $this->actingAs($this->superUser());
        Role::findOrCreate('cashier', 'web');
        $target = User::factory()->create();
        Activity::saving(function (): void {
            throw new RuntimeException('Audit unavailable');
        });
        $this->postJson('/platform/identity/users', $this->payload(['roles' => ['cashier']]))->assertServerError();
        $this->assertDatabaseMissing('users', ['email' => 'new@example.test']);
        $this->putJson('/platform/identity/users/'.$target->id, ['name' => 'Changed', 'email' => $target->email])->assertServerError();
        $this->assertSame($target->name, $target->fresh()->name);
        $this->assertDatabaseCount('activity_log', 0);
    }

    public function test_missing_overflow_identifiers_and_duplicate_update_do_not_mutate(): void
    {
        $this->actingAs($this->superUser());
        $target = User::factory()->create();
        $this->putJson('/platform/identity/users/9999999999999999999999999999', ['name' => 'X', 'email' => 'x@example.test'])->assertNotFound();
        $this->putJson('/platform/identity/users/999999', ['name' => 'X', 'email' => 'x@example.test'])->assertNotFound();
        $other = User::factory()->create();
        $this->putJson('/platform/identity/users/'.$target->id, ['name' => 'X', 'email' => strtoupper($other->email)])->assertUnprocessable();
        $this->assertDatabaseCount('activity_log', 0);
    }

    public function test_application_entrypoint_checks_permission_without_http(): void
    {
        $actor = User::factory()->create();
        $this->expectException(AuthorizationException::class);
        app(ManageUsers::class)->create($actor->id, $this->payload());
    }
}
