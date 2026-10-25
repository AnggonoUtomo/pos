<?php

namespace Tests\Feature\Modules\Platform\OperationalSettings;

use App\Models\User;
use App\Modules\Platform\OperationalSettings\Application\Actions\RunManualBackup;
use App\Modules\Platform\OperationalSettings\Application\Contracts\BackupExecutor;
use App\Modules\Platform\OperationalSettings\Application\Contracts\OperationalSettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ManualBackupTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_manager_can_run_backup_and_payload_cannot_choose_path(): void
    {
        $this->mock(BackupExecutor::class)->shouldNotReceive('run');
        $this->postJson('/platform/operational-settings/backup')->assertUnauthorized();
        $this->actingAs(User::factory()->create())->postJson('/platform/operational-settings/backup')->assertForbidden();
        $this->actingAs($this->manager())->postJson('/platform/operational-settings/backup', ['path' => '../public'])
            ->assertUnprocessable();
    }

    public function test_manual_success_is_audited_repeats_without_changing_daily_schedule_or_ledger(): void
    {
        $file = 'pos-20261025-140000-1234abcd.sql';
        $this->mock(BackupExecutor::class)->shouldReceive('run')->twice()->andReturn($file);
        $user = $this->manager();
        for ($i = 0; $i < 2; $i++) {
            $this->actingAs($user)->postJson('/platform/operational-settings/backup')->assertOk()
                ->assertJsonPath('status', 'succeeded')->assertJsonPath('file', $file);
        }
        $this->assertDatabaseCount('operational_backup_runs', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseCount('payment_records', 0);
        $this->assertDatabaseHas('operational_settings', ['backup_enabled' => false, 'backup_time' => '21:00', 'version' => 0]);
        $this->assertSame(2, DB::table('activity_log')->where('event', 'backup.manual.succeeded')->count());
        $this->assertDatabaseHas('activity_log', ['event' => 'backup.manual.succeeded', 'causer_id' => $user->id]);
        $status = app(OperationalSettingsRepository::class)->status();
        $this->assertSame('succeeded', $status['last_manual']['status']);
        $this->assertSame($file, $status['last_manual']['file']);
        $this->assertNull($status['last_run']);
    }

    public function test_failure_is_safe_and_is_not_reported_as_success(): void
    {
        $this->mock(BackupExecutor::class)->shouldReceive('run')->once()->andThrow(new \RuntimeException('PRIVATE_CREDENTIAL'));
        $response = $this->actingAs($this->manager())->postJson('/platform/operational-settings/backup');
        $response->assertUnprocessable()->assertJsonValidationErrors('backup');
        $this->assertStringNotContainsString('PRIVATE_CREDENTIAL', $response->getContent());
        $this->assertStringNotContainsString('PRIVATE_CREDENTIAL', DB::table('activity_log')->pluck('properties')->implode(' '));
        $this->assertDatabaseHas('activity_log', ['event' => 'backup.manual.failed']);
        $this->assertSame('failed', app(OperationalSettingsRepository::class)->status()['last_manual']['status']);
    }

    private function manager(): User
    {
        Permission::findOrCreate('platform.operational-settings.manage', 'web');

        return User::factory()->create()->givePermissionTo('platform.operational-settings.manage');
    }

    public function test_audit_veto_prevents_dump_execution(): void
    {
        $this->mock(BackupExecutor::class)->shouldNotReceive('run');
        $user = $this->manager();
        Activity::saving(fn () => false);
        try {
            $this->expectException(\RuntimeException::class);
            app(RunManualBackup::class)->handle($user->id);
        } finally {
            Activity::flushEventListeners();
        }
    }
}
