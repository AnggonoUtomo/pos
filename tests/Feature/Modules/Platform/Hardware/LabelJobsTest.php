<?php

namespace Tests\Feature\Modules\Platform\Hardware;

use App\Models\User;
use App\Modules\Inventory\Items\Database\Seeders\ItemsDemoSeeder;
use App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder;
use App\Modules\Platform\Hardware\Database\Seeders\HardwareAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LabelJobsTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;

    private array $input;

    protected function setUp(): void
    {
        parent::setUp();
        config(['pos_hardware.enabled' => true, 'pos_hardware.printing_enabled' => true]);
        $this->seed([UnitsDemoSeeder::class, ItemsDemoSeeder::class, HardwareAccessSeeder::class]);
        Permission::findOrCreate('inventory.view', 'web');
        $this->operator = User::factory()->create()->givePermissionTo(['platform.hardware.view', 'platform.hardware.manage', 'platform.hardware.print', 'platform.hardware.reprint', 'inventory.view']);
        $this->actingAs($this->operator);
        $profile = $this->postJson('/platform/hardware/printers', ['name' => 'Label', 'printer_name' => 'CT420D QA', 'gateway_host' => 'localhost',
            'width_mm' => 50, 'height_mm' => 30, 'dpi' => 203, 'orientation' => 'portrait', 'is_active' => true, 'version' => 0])->assertCreated()->json('profile');
        $item = DB::table('items')->where('sku', 'DEMO-CCTV-IP-2MP')->first();
        $this->input = ['printer_id' => $profile['id'], 'item_id' => $item->id, 'unit_id' => $item->base_unit_id,
            'kind' => 'product_label', 'copies' => 1, 'idempotency_key' => (string) Str::uuid()];
    }

    public function test_single_claim_idempotent_outcome_reprint_and_snapshot(): void
    {
        $job = $this->postJson('/platform/hardware/labels', $this->input)->assertCreated()->json('job');
        $this->assertSame($job['id'], $this->postJson('/platform/hardware/labels', $this->input)->assertCreated()->json('job.id'));
        $this->postJson('/platform/hardware/labels', array_replace($this->input, ['copies' => 2]))->assertConflict();
        $this->assertDatabaseCount('hardware_label_jobs', 1);
        $this->assertSame('DEMO-CCTV-IP-2MP', $job['snapshot']['sku']);
        $this->deleteJson('/platform/hardware/printers/'.$job['printer_id'], ['version' => 1])->assertConflict();
        $claim = $this->postJson('/platform/hardware/labels/'.$job['id'].'/dispatch')->assertOk()->json();
        $this->postJson('/platform/hardware/labels/'.$job['id'].'/dispatch')->assertConflict();
        $payload = ['attempt_id' => $claim['attempt_id'], 'token' => $claim['token'], 'status' => 'submitted', 'error_code' => null];
        $this->postJson('/platform/hardware/labels/'.$job['id'].'/outcome', array_replace($payload, ['token' => str_repeat('0', 64)]))->assertForbidden();
        $this->postJson('/platform/hardware/labels/'.$job['id'].'/outcome', $payload)->assertOk();
        $this->postJson('/platform/hardware/labels/'.$job['id'].'/outcome', $payload)->assertOk();
        $this->postJson('/platform/hardware/labels/'.$job['id'].'/outcome', array_replace($payload, ['status' => 'uncertain']))->assertConflict();
        $this->postJson('/platform/hardware/labels/'.$job['id'].'/reprint', ['reason' => '', 'idempotency_key' => (string) Str::uuid()])->assertUnprocessable();
        $reprint = $this->postJson('/platform/hardware/labels/'.$job['id'].'/reprint', ['reason' => 'Label rusak setelah diperiksa', 'idempotency_key' => (string) Str::uuid()])->assertCreated()->json('job');
        $this->assertSame($job['id'], $reprint['original_job_id']);
        $this->assertSame($job['snapshot'], $reprint['snapshot']);
        $logs = DB::table('activity_log')->where('log_name', 'platform.hardware')->pluck('properties')->implode('');
        $this->assertStringNotContainsString($claim['token'], $logs);
        $this->assertStringNotContainsString(hash('sha256', $claim['token']), $logs);
    }

    public function test_flags_permissions_and_foreign_payloads_cannot_send(): void
    {
        $this->postJson('/platform/hardware/labels', $this->input + ['raw' => 'PRINT'])->assertUnprocessable();
        config(['pos_hardware.printing_enabled' => false]);
        $this->postJson('/platform/hardware/labels', $this->input)->assertStatus(409);
        config(['pos_hardware.printing_enabled' => true]);
        $job = $this->postJson('/platform/hardware/labels', $this->input)->assertCreated()->json('job');
        $other = User::factory()->create()->givePermissionTo(['platform.hardware.view', 'platform.hardware.print', 'inventory.view']);
        $this->actingAs($other)->postJson('/platform/hardware/labels/'.$job['id'].'/dispatch')->assertForbidden();
        $this->getJson('/platform/hardware/labels')->assertOk()->assertJsonPath('meta.total', 0);
        $this->assertDatabaseCount('hardware_print_attempts', 0);
    }

    public function test_lost_ack_expiry_requires_explicit_reprint_and_preserves_business_data(): void
    {
        $before = DB::table('stock_balances')->count();
        $job = $this->postJson('/platform/hardware/labels', $this->input)->assertCreated()->json('job');
        $claim = $this->postJson('/platform/hardware/labels/'.$job['id'].'/dispatch')->assertOk()->json();
        $this->travel(3)->minutes();
        $this->postJson('/platform/hardware/labels/'.$job['id'].'/dispatch')->assertConflict();
        $this->postJson('/platform/hardware/labels/'.$job['id'].'/outcome', ['attempt_id' => $claim['attempt_id'], 'token' => $claim['token'], 'status' => 'submitted'])->assertConflict();
        $this->postJson('/platform/hardware/labels/'.$job['id'].'/reprint', ['reason' => 'Hasil fisik diperiksa, label tidak ada', 'idempotency_key' => (string) Str::uuid()])->assertCreated();
        $this->assertSame('uncertain', DB::table('hardware_label_jobs')->where('id', $job['id'])->value('status'));
        $this->assertSame($before, DB::table('stock_balances')->count());
        $this->assertDatabaseCount('hardware_print_attempts', 1);
    }
}
