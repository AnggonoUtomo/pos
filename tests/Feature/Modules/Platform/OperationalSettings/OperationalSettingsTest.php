<?php

namespace Tests\Feature\Modules\Platform\OperationalSettings;

use App\Models\User;
use App\Modules\Platform\OperationalSettings\Application\Actions\RunScheduledBackup;
use App\Modules\Platform\OperationalSettings\Application\Actions\UpdateOperationalSettings;
use App\Modules\Platform\OperationalSettings\Application\Contracts\BackupExecutor;
use App\Modules\Platform\OperationalSettings\Application\Contracts\OperationalSettingsRepository;
use App\Modules\Platform\OperationalSettings\Database\Seeders\OperationalSettingsAccessSeeder;
use App\Modules\Platform\OperationalSettings\Database\Seeders\OperationalSettingsDemoSeeder;
use App\Modules\Platform\OperationalSettings\Infrastructure\Support\ArtisanBackupExecutor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OperationalSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function input(array $changes = []): array
    {
        return array_replace(['receipt_paper' => 'a4', 'backup_enabled' => false, 'backup_time' => '21:00', 'version' => 0], $changes);
    }

    private function operator(bool $manage = false): User
    {
        Permission::findOrCreate('platform.operational-settings.view', 'web');
        Permission::findOrCreate('platform.operational-settings.manage', 'web');
        $user = User::factory()->create()->givePermissionTo('platform.operational-settings.view');
        if ($manage) {
            $user->givePermissionTo('platform.operational-settings.manage');
        }

        return $user;
    }

    public function test_defaults_are_safe_and_authorization_is_enforced(): void
    {
        $this->get('/platform/operational-settings')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/platform/operational-settings')->assertForbidden();
        $this->actingAs($this->operator())->get('/platform/operational-settings')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('platform/operational-settings/index')->where('settings.receipt_paper', 'a4')
            ->where('settings.backup_enabled', false)->where('settings.backup_time', '21:00')->where('backup.last_run', null));
        $this->putJson('/platform/operational-settings', $this->input())->assertForbidden();
        $this->assertDatabaseCount('operational_backup_runs', 0);
    }

    public function test_update_has_version_allowlist_validation_and_noop_audit(): void
    {
        $this->actingAs($this->operator(true));
        $input = $this->input(['receipt_paper' => '58mm', 'backup_enabled' => true, 'backup_time' => '22:15']);
        $this->putJson('/platform/operational-settings', $input)->assertOk();
        $this->assertDatabaseHas('operational_settings', ['receipt_paper' => '58mm', 'version' => 1]);
        $this->assertDatabaseHas('activity_log', ['log_name' => 'platform.operational-settings', 'event' => 'updated']);
        $this->putJson('/platform/operational-settings', $input)->assertUnprocessable();
        $this->putJson('/platform/operational-settings', array_replace($input, ['version' => 1]))->assertOk();
        $this->assertSame(1, Activity::where('log_name', 'platform.operational-settings')->count());
        foreach ([['receipt_paper' => 'raw'], ['backup_time' => '24:00'], ['backup_time' => '9:00'], ['backup_enabled' => 'yes'], ['APP_KEY' => 'forbidden']] as $bad) {
            $this->putJson('/platform/operational-settings', array_replace($input, ['version' => 1], $bad))->assertUnprocessable();
        }
        $this->assertDatabaseCount('operational_backup_runs', 0);
    }

    public function test_super_system_can_prepare_settings_without_enabling_backup_or_new_grants(): void
    {
        $user = User::factory()->create()->assignRole(Role::findOrCreate('super-system', 'web'));
        $this->assertFalse($user->getAllPermissions()->contains('name', 'platform.operational-settings.manage'));
        $this->actingAs($user)->get('/platform/operational-settings')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('auth.superSystem', true)->where('settings.backup_enabled', false));
        $this->putJson('/platform/operational-settings', $this->input(['backup_time' => '18:45', 'receipt_paper' => '80mm']))->assertOk();
        $this->assertDatabaseHas('operational_settings', ['backup_time' => '18:45', 'backup_enabled' => false, 'receipt_paper' => '80mm']);
        $this->assertDatabaseCount('operational_backup_runs', 0);
        $this->assertDatabaseCount('model_has_permissions', 0);
        $this->assertDatabaseCount('role_has_permissions', 0);
    }

    public function test_audit_failure_rolls_back_settings(): void
    {
        Activity::saving(fn () => false);
        try {
            app(UpdateOperationalSettings::class)->handle($this->input(['receipt_paper' => '80mm']), null);
            $this->fail('Audit veto harus menolak penyimpanan.');
        } catch (\RuntimeException) {
            $this->assertDatabaseHas('operational_settings', ['receipt_paper' => 'a4', 'version' => 0]);
        } finally {
            Activity::flushEventListeners();
        }
    }

    public function test_printing_shared_props_expose_only_paper_to_authenticated_users(): void
    {
        app(UpdateOperationalSettings::class)->handle($this->input(['receipt_paper' => '80mm']), null);
        $this->get('/login')->assertInertia(fn (Assert $page) => $page->where('printing', null));
        $this->actingAs($this->operator())->get('/dashboard')->assertInertia(fn (Assert $page) => $page
            ->where('printing.paper', '80mm')->missing('printing.backup_enabled')->missing('printing.backup_time'));
    }

    public function test_disabled_and_not_due_schedules_do_not_execute_and_success_runs_once_per_wib_day(): void
    {
        $executor = $this->mock(BackupExecutor::class);
        $executor->shouldReceive('run')->once()->andReturn('pos-20261025-140000-1234abcd.sql');
        Carbon::setTestNow('2026-10-25 13:59:00 UTC');
        try {
            $this->assertSame('skipped', app(RunScheduledBackup::class)->handle());
            app(UpdateOperationalSettings::class)->handle($this->input(['backup_enabled' => true]), null);
            $this->assertSame('skipped', app(RunScheduledBackup::class)->handle());
            Carbon::setTestNow('2026-10-25 14:00:00 UTC');
            $this->assertSame('succeeded', app(RunScheduledBackup::class)->handle());
            $this->assertSame('skipped', app(RunScheduledBackup::class)->handle());
            $this->assertDatabaseHas('operational_backup_runs', ['scheduled_for' => '2026-10-25', 'status' => 'succeeded', 'attempts' => 1]);
            $this->assertDatabaseCount('operational_backup_runs', 1);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_failure_is_sanitized_and_retries_are_limited(): void
    {
        $this->mock(BackupExecutor::class)->shouldReceive('run')->times(3)->andThrow(new \RuntimeException('PRIVATE_CREDENTIAL_DO_NOT_LOG'));
        app(UpdateOperationalSettings::class)->handle($this->input(['backup_enabled' => true]), null);
        Carbon::setTestNow('2026-10-25 14:00:00 UTC');
        try {
            $this->assertSame('failed', app(RunScheduledBackup::class)->handle());
            $this->assertSame('skipped', app(RunScheduledBackup::class)->handle());
            Carbon::setTestNow('2026-10-25 14:10:00 UTC');
            $this->assertSame('failed', app(RunScheduledBackup::class)->handle());
            Carbon::setTestNow('2026-10-25 14:20:00 UTC');
            $this->assertSame('failed', app(RunScheduledBackup::class)->handle());
            Carbon::setTestNow('2026-10-25 15:20:00 UTC');
            $this->assertSame('skipped', app(RunScheduledBackup::class)->handle());
            $this->assertStringNotContainsString('PRIVATE_CREDENTIAL', json_encode(app(OperationalSettingsRepository::class)->status()));
            $this->assertDatabaseHas('operational_backup_runs', ['status' => 'failed', 'attempts' => 3, 'error_code' => 'backup_failed']);
            $this->assertStringNotContainsString('PRIVATE_CREDENTIAL', DB::table('activity_log')->pluck('properties')->implode(' '));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_claim_prevents_overlap_recovers_expired_lease_and_uses_current_day_only(): void
    {
        app(UpdateOperationalSettings::class)->handle($this->input(['backup_enabled' => true]), null);
        Carbon::setTestNow('2026-10-25 15:00:00 UTC');
        try {
            $repo = app(OperationalSettingsRepository::class);
            $first = $repo->claimDue();
            $this->assertNotNull($first);
            $this->assertNull($repo->claimDue());
            Carbon::setTestNow('2026-10-25 15:16:00 UTC');
            $second = $repo->claimDue();
            $this->assertNotNull($second);
            $this->assertNotSame($first['token'], $second['token']);
            $repo->finish($first, 'succeeded', 'old.sql');
            $this->assertDatabaseHas('operational_backup_runs', ['status' => 'running', 'attempts' => 2]);
            $repo->finish($second, 'succeeded', 'current.sql');
            Carbon::setTestNow('2026-10-28 15:16:00 UTC');
            $third = $repo->claimDue();
            $this->assertNotNull($third);
            $this->assertDatabaseCount('operational_backup_runs', 2);
            $this->assertDatabaseHas('operational_backup_runs', ['scheduled_for' => '2026-10-28']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_seeders_are_idempotent_preserve_settings_and_do_not_grant_access(): void
    {
        app(UpdateOperationalSettings::class)->handle($this->input(['receipt_paper' => '80mm']), null);
        foreach ([OperationalSettingsAccessSeeder::class, OperationalSettingsDemoSeeder::class] as $seeder) {
            $this->seed($seeder);
            $this->seed($seeder);
        }
        $this->assertSame(2, Permission::where('name', 'like', 'platform.operational-settings.%')->count());
        $this->assertDatabaseHas('operational_settings', ['receipt_paper' => '80mm', 'backup_enabled' => false]);
        $this->assertDatabaseCount('model_has_permissions', 0);
        $this->assertDatabaseCount('operational_backup_runs', 0);
        $this->artisan('pos:backup-due')->expectsOutput('Backup terjadwal: skipped')->assertSuccessful();
    }

    public function test_exhausted_interrupted_attempt_is_not_left_running_forever(): void
    {
        app(UpdateOperationalSettings::class)->handle($this->input(['backup_enabled' => true]), null);
        Carbon::setTestNow('2026-10-25 15:00:00 UTC');
        try {
            $repo = app(OperationalSettingsRepository::class);
            $repo->claimDue();
            DB::table('operational_backup_runs')->update(['attempts' => 3]);
            Carbon::setTestNow('2026-10-25 15:16:00 UTC');
            $this->assertNull($repo->claimDue());
            $this->assertDatabaseHas('operational_backup_runs', ['status' => 'failed', 'error_code' => 'backup_interrupted', 'attempts' => 3]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_executor_uses_existing_backup_command_and_returns_only_safe_basename(): void
    {
        Artisan::shouldReceive('call')->once()->with('pos:backup')->andReturn(0);
        Artisan::shouldReceive('output')->once()->andReturn("Backup tersimpan: backups/pos-20261025-140000-1234abcd.sql\n");
        $this->assertSame('pos-20261025-140000-1234abcd.sql', app(ArtisanBackupExecutor::class)->run());
    }

    public function test_executor_does_not_forward_sensitive_errors(): void
    {
        Artisan::shouldReceive('call')->once()->with('pos:backup')->andReturn(1);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Backup gagal.');
        app(ArtisanBackupExecutor::class)->run();
    }
}
