<?php

namespace Tests\Feature\Modules\Platform\Identity;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    private string $password;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->password = bin2hex(random_bytes(24));
    }

    private function user(bool $super = false): User
    {
        $user = User::factory()->create(['password' => $this->password]);
        if ($super) {
            $user->assignRole(Role::findOrCreate('super-system', 'web'));
        }

        return $user;
    }

    public function test_last_super_is_not_deleted_or_logged_out(): void
    {
        $user = $this->user(true);
        $before = $user->fresh()->getAttributes();
        $this->actingAs($user)->withSession(['account-marker' => 'keep'])
            ->deleteJson('/settings/profile', ['password' => $this->password])
            ->assertUnprocessable()->assertJsonValidationErrors('account');
        $this->assertTrue($before === $user->fresh()->getAttributes());
        $this->assertTrue($user->fresh()->hasRole('super-system', 'web'));
        $this->assertAuthenticated();
        $this->assertSame('keep', session('account-marker'));
        $this->assertDatabaseCount('activity_log', 0);
    }

    public function test_ordinary_user_can_delete_only_their_own_account_with_audit(): void
    {
        $user = $this->user();
        $other = $this->user();
        $otherBefore = $other->fresh()->getAttributes();
        $role = Role::findOrCreate('staff', 'web');
        $user->assignRole($role);
        $user->givePermissionTo(Permission::findOrCreate('inventory.view', 'web'));
        $this->actingAs($user)->withSession(['account-marker' => 'keep'])
            ->delete('/settings/profile', ['password' => $this->password, 'id' => $other->id])
            ->assertSessionHasNoErrors()->assertRedirect('/');
        $this->assertGuest();
        $this->assertFalse(session()->has('account-marker'));
        $this->assertNull($user->fresh());
        $this->assertTrue($otherBefore === $other->fresh()->getAttributes());
        $this->assertDatabaseMissing('model_has_roles', ['model_id' => $user->id]);
        $this->assertDatabaseMissing('model_has_permissions', ['model_id' => $user->id]);
        $log = Activity::where('event', 'identity.account_deleted')->sole();
        $this->assertSame('identity', $log->log_name);
        $this->assertSame($user->id, (int) $log->causer_id);
        $this->assertSame($user->id, (int) $log->subject_id);
        $this->assertSame(['before' => ['roles' => ['staff']], 'after' => ['deleted' => true]], $log->properties->all());
    }

    public function test_super_can_delete_self_when_another_super_remains(): void
    {
        $user = $this->user(true);
        $remaining = $this->user(true);
        $this->actingAs($user)->delete('/settings/profile', ['password' => $this->password])
            ->assertSessionHasNoErrors()->assertRedirect('/');
        $this->assertGuest();
        $this->assertNull($user->fresh());
        $this->assertTrue($remaining->fresh()->hasRole('super-system', 'web'));
        $this->assertSame(1, User::role('super-system', 'web')->count());
        $this->assertDatabaseCount('activity_log', 1);
    }

    public function test_current_password_is_still_required_before_guard_or_audit(): void
    {
        $user = $this->user(true);
        $this->actingAs($user)->deleteJson('/settings/profile', ['password' => bin2hex(random_bytes(24))])
            ->assertUnprocessable()->assertJsonValidationErrors('password')
            ->assertJsonMissingValidationErrors('account');
        $this->assertNotNull($user->fresh());
        $this->assertAuthenticated();
        $this->assertDatabaseCount('activity_log', 0);
    }

    public function test_audit_failure_keeps_account_pivots_and_session(): void
    {
        $user = $this->user();
        $user->assignRole(Role::findOrCreate('staff', 'web'));
        Activity::saving(function (): void {
            throw new RuntimeException('Audit unavailable');
        });
        $this->actingAs($user)->withSession(['account-marker' => 'keep'])
            ->deleteJson('/settings/profile', ['password' => $this->password])->assertServerError();
        $this->assertTrue($user->fresh()->hasRole('staff', 'web'));
        $this->assertAuthenticated();
        $this->assertSame('keep', session('account-marker'));
        $this->assertDatabaseCount('activity_log', 0);
    }

    public function test_delete_failure_rolls_back_audit_and_keeps_session(): void
    {
        $user = $this->user();
        $user->assignRole(Role::findOrCreate('staff', 'web'));
        User::deleting(function (): void {
            throw new RuntimeException('Deletion unavailable');
        });
        $this->actingAs($user)->withSession(['account-marker' => 'keep'])
            ->deleteJson('/settings/profile', ['password' => $this->password])->assertServerError();
        $this->assertTrue($user->fresh()->hasRole('staff', 'web'));
        $this->assertAuthenticated();
        $this->assertSame('keep', session('account-marker'));
        $this->assertDatabaseCount('activity_log', 0);
    }

    public function test_delete_veto_is_not_reported_as_success(): void
    {
        $user = $this->user();
        $user->assignRole(Role::findOrCreate('staff', 'web'));
        User::deleting(fn () => false);
        $this->actingAs($user)->deleteJson('/settings/profile', ['password' => $this->password])->assertServerError();
        $this->assertTrue($user->fresh()->hasRole('staff', 'web'));
        $this->assertAuthenticated();
        $this->assertDatabaseCount('activity_log', 0);
    }

    public function test_guard_uses_fresh_roles_not_stale_auth_relation(): void
    {
        $user = $this->user();
        $user->load('roles');
        $this->assertCount(0, $user->roles);
        $user->fresh()->assignRole(Role::findOrCreate('super-system', 'web'));
        $this->actingAs($user)->deleteJson('/settings/profile', ['password' => $this->password])
            ->assertUnprocessable()->assertJsonValidationErrors('account');
        $this->assertTrue($user->fresh()->hasRole('super-system', 'web'));
        $this->assertAuthenticated();
    }

    public function test_guest_cannot_delete_an_account(): void
    {
        $this->delete('/settings/profile', ['password' => $this->password])->assertRedirect('/login');
        $this->assertDatabaseCount('activity_log', 0);
    }
}
