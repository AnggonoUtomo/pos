<?php

namespace Tests\Feature\Modules\Platform\AuditLog;

use App\Models\User;
use App\Modules\Inventory\Units\Infrastructure\Persistence\Models\Unit;
use App\Modules\Platform\AuditLog\Database\Seeders\AuditLogAccessSeeder;
use App\Modules\Platform\AuditLog\Database\Seeders\AuditLogDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuditLogViewerTest extends TestCase
{
    use RefreshDatabase;

    private User $viewer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Permission::findOrCreate('platform.audit-log.view', 'web');
        $this->viewer = User::factory()->create(['name' => 'Operator QA']);
        $this->viewer->givePermissionTo('platform.audit-log.view');
    }

    private function record(array $patch = []): int
    {
        return DB::table('activity_log')->insertGetId(array_replace([
            'log_name' => 'inventory.units', 'description' => 'hidden-description-marker', 'event' => 'updated',
            'subject_type' => Unit::class, 'subject_id' => '01m65j1ts3sq8fjhctxqn02dq7',
            'causer_type' => User::class, 'causer_id' => $this->viewer->id,
            'properties' => json_encode(['attributes' => ['code' => 'MTR', 'name' => 'Meter baru', 'password' => 'hidden-password-marker'],
                'old' => ['name' => 'Meter lama'], 'token' => 'hidden-token-marker', 'file' => 'hidden-file-marker']),
            'created_at' => '2026-10-30 14:00:00', 'updated_at' => '2026-10-30 14:00:00',
        ], $patch));
    }

    public function test_authorization_covers_page_data_and_detail_and_no_mutation_routes_exist(): void
    {
        $id = $this->record();
        $this->get('/platform/audit-log')->assertRedirect('/login');
        $this->getJson('/platform/audit-log/data')->assertUnauthorized();
        $this->getJson('/platform/audit-log/'.$id)->assertUnauthorized();
        $user = User::factory()->create();
        foreach (['/platform/audit-log', '/platform/audit-log/data', '/platform/audit-log/'.$id] as $url) {
            $this->actingAs($user)->getJson($url)->assertForbidden();
        }
        $this->actingAs($this->viewer)->get('/platform/audit-log')->assertOk();
        $this->postJson('/platform/audit-log')->assertStatus(405);
        $this->deleteJson('/platform/audit-log/'.$id)->assertStatus(405);
        $this->putJson('/platform/audit-log/'.$id)->assertStatus(405);
        $user->assignRole(Role::findOrCreate('super-system', 'web'));
        $this->actingAs($user)->getJson('/platform/audit-log/data')->assertOk();
    }

    public function test_detail_and_list_are_whitelisted_and_reads_never_write_audit(): void
    {
        $id = $this->record();
        $before = DB::table('activity_log')->orderBy('id')->get()->toJson();
        $this->actingAs($this->viewer);
        $detail = $this->getJson('/platform/audit-log/'.$id)->assertOk()->assertJsonPath('actor_name', 'Operator QA')
            ->assertJsonPath('module_name', 'Satuan')->assertJsonPath('reference', 'MTR')
            ->assertJsonPath('before.name', 'Meter lama')->assertJsonPath('after.name', 'Meter baru')->json();
        $this->assertArrayNotHasKey('password', $detail['after']);
        foreach (['/platform/audit-log/data', '/platform/audit-log', '/platform/audit-log/'.$id] as $url) {
            $response = $this->getJson($url)->assertOk();
            foreach (['hidden-password-marker', 'hidden-token-marker', 'hidden-file-marker', 'hidden-description-marker', Unit::class] as $hidden) {
                $this->assertStringNotContainsString($hidden, $response->getContent());
            }
        }
        $this->assertSame($before, DB::table('activity_log')->orderBy('id')->get()->toJson());
    }

    public function test_package_attribute_changes_and_manual_before_after_are_supported_without_nested_payloads(): void
    {
        $this->actingAs($this->viewer);
        $unit = Unit::create(['code' => 'QA', 'name' => 'Lama', 'symbol' => 'qa', 'is_active' => true]);
        $unit->update(['name' => 'Baru']);
        $id = DB::table('activity_log')->where('subject_id', $unit->id)->where('event', 'updated')->value('id');
        $this->getJson('/platform/audit-log/'.$id)->assertOk()->assertJsonPath('before.name', 'Lama')->assertJsonPath('after.name', 'Baru');
        $manual = $this->record(['properties' => json_encode(['before' => ['roles' => ['cashier']],
            'after' => ['roles' => ['supervisor'], 'name' => ['token' => 'nested-marker'], 'email' => 'contact-marker'],
            'notes' => 'notes-marker'])]);
        $result = $this->getJson('/platform/audit-log/'.$manual)->assertOk()->assertJsonPath('after.roles.0', 'supervisor');
        foreach (['nested-marker', 'contact-marker', 'notes-marker'] as $hidden) {
            $this->assertStringNotContainsString($hidden, $result->getContent());
        }
    }

    public function test_literal_search_filter_options_and_global_pagination(): void
    {
        for ($i = 0; $i < 12; $i++) {
            $this->record();
        }
        $literal = $this->record(['properties' => json_encode(['number' => 'DOC-100%_!']), 'causer_type' => null, 'causer_id' => null]);
        $this->actingAs($this->viewer)->getJson('/platform/audit-log/data')->assertOk()->assertJsonPath('meta.total', 13)->assertJsonCount(10, 'data');
        $this->getJson('/platform/audit-log/data?per_page=25')->assertOk()->assertJsonCount(13, 'data');
        $this->getJson('/platform/audit-log/data?search='.urlencode('%_!'))->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.id', (string) $literal);
        $this->getJson('/platform/audit-log/data?search=hidden-token-marker')->assertOk()->assertJsonPath('meta.total', 0);
        $this->getJson('/platform/audit-log/data?module=inventory.units&event=updated&actor_id='.$this->viewer->id)->assertOk()->assertJsonPath('meta.total', 12);
        $this->getJson('/platform/audit-log/data?actor_id=system')->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.actor_name', 'Sistem');
        $this->getJson('/platform/audit-log/999999')->assertNotFound();
    }

    public static function timezones(): array
    {
        return [['UTC'], ['Asia/Jakarta']];
    }

    #[DataProvider('timezones')]
    public function test_wib_day_boundaries_use_database_timezone_and_end_is_exclusive(string $timezone): void
    {
        config(['app.timezone' => $timezone]);
        foreach (['2026-10-29 23:59:59', '2026-10-30 00:00:00', '2026-10-30 23:59:59', '2026-10-31 00:00:00'] as $time) {
            $stored = Carbon::parse($time, 'Asia/Jakarta')->setTimezone($timezone)->format('Y-m-d H:i:s');
            $this->record(['created_at' => $stored, 'updated_at' => $stored]);
        }
        $this->actingAs($this->viewer)->getJson('/platform/audit-log/data?date_from=2026-10-30&date_to=2026-10-30')
            ->assertOk()->assertJsonPath('meta.total', 2)->assertJsonPath('data.0.occurred_at', '2026-10-30T16:59:59.000000Z');
    }

    public function test_invalid_filters_are_rejected_and_actor_missing_does_not_hide_history(): void
    {
        $id = $this->record(['causer_id' => 999999]);
        $this->actingAs($this->viewer)->getJson('/platform/audit-log/'.$id)->assertOk()->assertJsonPath('actor_name', 'Pengguna tidak tersedia');
        foreach (['page=0', 'per_page=11', 'search[]=bad', 'date_from=bad', 'date_from=2026-10-31&date_to=2026-10-30', 'actor_id=bad', 'module[]=bad', 'sort=properties'] as $query) {
            $this->getJson('/platform/audit-log/data?'.$query)->assertUnprocessable();
        }
    }

    public function test_deleted_and_manual_snapshots_remain_searchable_by_safe_reference(): void
    {
        $id = $this->record(['properties' => '{}', 'attribute_changes' => json_encode(['old' => ['code' => 'DEL-100%_!']]), 'event' => 'deleted']);
        $this->actingAs($this->viewer)->getJson('/platform/audit-log/data?search='.urlencode('DEL-100%_!'))
            ->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.id', (string) $id);
        $manual = $this->record(['properties' => json_encode(['after' => ['number' => 'MANUAL-1']])]);
        $this->getJson('/platform/audit-log/data?search=MANUAL-1')->assertOk()->assertJsonPath('data.0.id', (string) $manual);
    }

    public function test_access_catalog_is_idempotent_without_changing_role_grants_and_demo_is_noop(): void
    {
        $before = DB::table('model_has_permissions')->get()->toJson();
        $roles = DB::table('role_has_permissions')->get()->toJson();
        $logs = DB::table('activity_log')->count();
        $this->seed(AuditLogAccessSeeder::class);
        $this->seed(AuditLogAccessSeeder::class);
        $this->seed(AuditLogDemoSeeder::class);
        $this->assertSame(1, Permission::where('name', 'platform.audit-log.view')->where('guard_name', 'web')->count());
        $this->assertSame($before, DB::table('model_has_permissions')->get()->toJson());
        $this->assertSame($roles, DB::table('role_has_permissions')->get()->toJson());
        $this->assertSame($logs, DB::table('activity_log')->count());
    }
}
