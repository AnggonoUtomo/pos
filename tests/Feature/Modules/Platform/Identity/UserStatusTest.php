<?php

namespace Tests\Feature\Modules\Platform\Identity;

use App\Models\User;
use App\Modules\Platform\Identity\Database\Seeders\IdentityPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserStatusTest extends TestCase
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

    private function changeStatus(int $id, bool $active)
    {
        return $this->patchJson('/platform/identity/users/'.$id.'/status', ['is_active' => $active]);
    }

    public function test_existing_defaults_are_active_and_version_zero(): void
    {
        $user = User::factory()->create()->fresh();
        $this->assertTrue($user->is_active);
        $this->assertSame(0, $user->access_version);
    }

    public function test_deactivation_retains_account_access_and_revokes_tokens_atomically(): void
    {
        $actor = $this->superUser();
        $target = User::factory()->create();
        $beforePassword = $target->password;
        DB::table('password_reset_tokens')->insert(['email' => $target->email, 'token' => 'ephemeral', 'created_at' => now()]);
        $this->actingAs($actor);
        $this->changeStatus($target->id, false)->assertOk();
        $target->refresh();
        $this->assertFalse($target->is_active);
        $this->assertSame(1, $target->access_version);
        $this->assertSame($beforePassword, $target->password);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $target->email]);
        $this->changeStatus($target->id, false)->assertOk();
        $this->assertSame(1, $target->fresh()->access_version);
        $this->changeStatus($target->id, true)->assertOk();
        $this->assertTrue($target->fresh()->is_active);
        $this->assertSame(1, $target->fresh()->access_version);
        $this->assertSame(2, Activity::where('event', 'identity.user_status_changed')->count());
    }

    public function test_self_deactivation_and_last_active_super_cannot_be_bypassed(): void
    {
        $actor = $this->superUser();
        $inactive = $this->superUser();
        $inactive->forceFill(['is_active' => false])->save();
        $ordinary = User::factory()->create();
        $ordinary->givePermissionTo(['platform.identity.users.manage', 'platform.identity.manage']);
        $this->actingAs($actor);
        $this->changeStatus($actor->id, false)->assertUnprocessable();
        $this->actingAs($ordinary);
        $this->changeStatus($actor->id, false)->assertForbidden();
        $this->putJson('/platform/identity/users/'.$actor->id.'/roles', ['roles' => []])->assertForbidden();
        $this->actingAs($actor)->putJson('/platform/identity/users/'.$actor->id.'/roles', ['roles' => []])->assertUnprocessable();
        $this->deleteJson('/settings/profile', ['password' => 'password'])->assertUnprocessable();
        $this->assertTrue($actor->fresh()->is_active);
    }

    public function test_super_can_disable_another_super_but_not_the_last_active_one(): void
    {
        $actor = $this->superUser();
        $other = $this->superUser();
        $this->actingAs($actor);
        $this->changeStatus($other->id, false)->assertOk();
        $this->changeStatus($actor->id, false)->assertUnprocessable();
        $this->changeStatus($other->id, true)->assertOk();
        $this->assertSame(2, Activity::where('event', 'identity.user_status_changed')->count());
    }

    public function test_status_permission_validation_and_missing_target(): void
    {
        $actor = User::factory()->create();
        $target = User::factory()->create();
        $this->actingAs($actor);
        $this->changeStatus($target->id, false)->assertForbidden();
        $actor->givePermissionTo('platform.identity.users.manage');
        $this->patchJson('/platform/identity/users/'.$target->id.'/status', ['is_active' => 'yes'])->assertUnprocessable();
        $this->changeStatus(999999, false)->assertNotFound();
        $this->changeStatus($target->id, false)->assertOk();
    }

    public function test_audit_failure_rolls_back_status_version_remember_and_reset_tokens(): void
    {
        $this->actingAs($this->superUser());
        $target = User::factory()->create();
        $before = $target->fresh()->getAttributes();
        DB::table('password_reset_tokens')->insert(['email' => $target->email, 'token' => 'ephemeral', 'created_at' => now()]);
        Activity::saving(function (): void {
            throw new RuntimeException('Audit unavailable');
        });
        $this->changeStatus($target->id, false)->assertServerError();
        $this->assertTrue($before === $target->fresh()->getAttributes());
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $target->email]);
        $this->assertDatabaseCount('activity_log', 0);
    }

    public function test_inactive_account_cannot_login_even_with_correct_password(): void
    {
        $password = bin2hex(random_bytes(16));
        $target = User::factory()->create(['password' => Hash::make($password)]);
        $target->forceFill(['is_active' => false])->save();
        $this->post('/login', ['email' => $target->email, 'password' => $password, 'remember' => true])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_inactive_and_old_session_are_rejected_even_after_reactivation(): void
    {
        $target = User::factory()->create();
        $this->actingAs($target)->withSession(['identity.access_version' => 0]);
        $target->forceFill(['is_active' => false, 'access_version' => 1])->save();
        $this->getJson('/dashboard')->assertUnauthorized();
        $this->assertGuest();
        $target->forceFill(['is_active' => true])->save();
        $this->actingAs($target)->withSession(['identity.access_version' => 0]);
        $this->getJson('/dashboard')->assertUnauthorized();
        $this->assertGuest();
    }

    public function test_fresh_login_after_activation_uses_current_version(): void
    {
        $password = bin2hex(random_bytes(16));
        $target = User::factory()->create(['password' => Hash::make($password)]);
        $target->forceFill(['access_version' => 2])->save();
        $this->post('/login', ['email' => $target->email, 'password' => $password])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($target);
        $this->assertSame(2, session('identity.access_version'));
        $this->get('/dashboard')->assertOk();
    }

    public function test_users_view_permission_allows_only_users_tab_without_credentials(): void
    {
        $actor = User::factory()->create();
        $actor->givePermissionTo('platform.identity.users.view');
        $this->actingAs($actor)->get('/platform/identity?tab=users')->assertInertia(fn (Assert $page) => $page
            ->where('records.data.0.is_active', true)->where('filters.tab', 'users')
            ->missing('records.data.0.password')->missing('records.data.0.access_version'));
        $this->get('/platform/identity?tab=roles')->assertForbidden();
        $this->get('/platform/identity')->assertInertia(fn (Assert $page) => $page->where('filters.tab', 'users'));
    }

    public function test_migration_preserves_existing_account_credentials_and_defaults(): void
    {
        $migration = require app_path('Modules/Platform/Identity/Database/Migrations/2026_10_11_000001_add_account_status_to_users_table.php');
        $migration->down();
        $user = User::factory()->create()->fresh();
        $before = $user->getAttributes();
        $migration->up();
        $after = $user->fresh();
        $this->assertTrue($after->is_active);
        $this->assertSame(0, $after->access_version);
        $this->assertTrue($before === array_diff_key($after->getAttributes(), array_flip(['is_active', 'access_version'])));
    }

    public function test_status_veto_is_not_success_and_edit_email_removes_old_reset_token(): void
    {
        $this->actingAs($this->superUser());
        $user = User::factory()->create();
        DB::table('password_reset_tokens')->insert(['email' => $user->email, 'token' => 'ephemeral', 'created_at' => now()]);
        $this->putJson('/platform/identity/users/'.$user->id, ['name' => $user->name, 'email' => 'new-email@example.test'])->assertOk();
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        $this->assertTrue($user->remember_token !== $user->fresh()->remember_token);
        User::saving(fn (User $model) => $model->id === $user->id ? false : null);
        $this->changeStatus($user->id, false)->assertServerError();
        $this->assertTrue($user->fresh()->is_active);
        $this->assertSame(0, $user->fresh()->access_version);
        $this->assertSame(0, Activity::where('event', 'identity.user_status_changed')->count());
    }

    public function test_remember_recaller_is_revoked_and_new_recaller_works_after_activation(): void
    {
        $admin = $this->superUser();
        $password = bin2hex(random_bytes(16));
        $user = User::factory()->create(['password' => Hash::make($password)]);
        $response = $this->post('/login', ['email' => $user->email, 'password' => $password, 'remember' => true]);
        $cookie = collect($response->headers->getCookies())->first(fn ($cookie) => str_starts_with($cookie->getName(), 'remember_web_'));
        $this->assertTrue($cookie !== null);
        session()->flush();
        Auth::forgetGuards();
        $this->withUnencryptedCookie($cookie->getName(), $cookie->getValue())->get('/dashboard')->assertOk();
        $this->assertAuthenticatedAs($user);
        $this->actingAs($admin);
        $this->changeStatus($user->id, false)->assertOk();
        $this->changeStatus($user->id, true)->assertOk();
        session()->flush();
        Auth::forgetGuards();
        $this->withUnencryptedCookie($cookie->getName(), $cookie->getValue())->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
        $response = $this->post('/login', ['email' => $user->email, 'password' => $password, 'remember' => true]);
        $newCookie = collect($response->headers->getCookies())->first(fn ($value) => str_starts_with($value->getName(), 'remember_web_'));
        $this->assertTrue($newCookie !== null);
        session()->flush();
        Auth::forgetGuards();
        $this->withUnencryptedCookie($newCookie->getName(), $newCookie->getValue())->get('/dashboard')->assertOk();
        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, session('identity.access_version'));
    }

    public function test_recaller_loaded_before_deactivation_cannot_promote_old_version(): void
    {
        $user = User::factory()->create()->fresh();
        $this->actingAs($user)->withSession(['identity.access_version' => 0]);
        (new \ReflectionProperty(Auth::guard('web'), 'viaRemember'))->setValue(Auth::guard('web'), true);
        // Simulate a valid recall loaded before a concurrent disable/reactivate.
        DB::table('users')->where('id', $user->id)->update(['is_active' => true, 'access_version' => 1]);
        $this->getJson('/dashboard')->assertUnauthorized();
        $this->assertGuest();
    }
}
