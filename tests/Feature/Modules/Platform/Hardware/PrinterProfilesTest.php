<?php

namespace Tests\Feature\Modules\Platform\Hardware;

use App\Models\User;
use App\Modules\Platform\Hardware\Database\Seeders\HardwareAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PrinterProfilesTest extends TestCase
{
    use RefreshDatabase;

    private function input(): array
    {
        return ['name' => 'Label Gudang', 'printer_name' => 'CT420D QA', 'gateway_host' => 'localhost',
            'width_mm' => 50, 'height_mm' => 30, 'dpi' => 203, 'orientation' => 'portrait', 'is_active' => true, 'version' => 0];
    }

    public function test_profile_version_permission_soft_delete_restore_and_no_auto_grant(): void
    {
        config(['pos_hardware.enabled' => true]);
        $this->seed(HardwareAccessSeeder::class);
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/platform/hardware/printers', $this->input())->assertForbidden();
        $this->assertDatabaseCount('role_has_permissions', 0);
        $user->givePermissionTo('platform.hardware.manage');
        $created = $this->postJson('/platform/hardware/printers', $this->input())->assertCreated()->json('profile');
        $id = $created['id'];
        $this->assertSame(1, $created['version']);
        $this->patchJson('/platform/hardware/printers/'.$id, $this->input())->assertConflict();
        $this->patchJson('/platform/hardware/printers/'.$id, array_replace($this->input(), ['version' => 1, 'width_mm' => 60]))->assertOk();
        $this->deleteJson('/platform/hardware/printers/'.$id, ['version' => 2])->assertOk();
        $this->assertNotNull(DB::table('hardware_printers')->where('id', $id)->value('deleted_at'));
        $this->postJson('/platform/hardware/printers/'.$id.'/restore', ['version' => 3])->assertOk();
        $this->assertNull(DB::table('hardware_printers')->where('id', $id)->value('deleted_at'));
        $this->assertSame(4, DB::table('hardware_printers')->where('id', $id)->value('version'));
        $this->assertGreaterThan(0, DB::table('activity_log')->where('log_name', 'platform.hardware')->count());
    }

    public function test_unapproved_hosts_raw_fields_and_printing_defaults_are_rejected(): void
    {
        config(['pos_hardware.enabled' => true, 'pos_hardware.printing_enabled' => false]);
        $this->seed(HardwareAccessSeeder::class);
        $this->actingAs(User::factory()->create()->givePermissionTo('platform.hardware.manage'));
        foreach ([['gateway_host' => 'evil.example'], ['gateway_host' => 'http://localhost'], ['dpi' => 300], ['width_mm' => 101], ['command' => 'PRINT']] as $bad) {
            $this->postJson('/platform/hardware/printers', array_replace($this->input(), $bad))->assertUnprocessable();
        }
        config(['pos_hardware.enabled' => false]);
        $this->postJson('/platform/hardware/printers', $this->input())->assertNotFound();
        $this->assertDatabaseCount('hardware_printers', 0);
    }

    public function test_audit_failure_rolls_back_profile_creation(): void
    {
        config(['pos_hardware.enabled' => true]);
        $this->seed(HardwareAccessSeeder::class);
        $this->actingAs(User::factory()->create()->givePermissionTo('platform.hardware.manage'));
        DB::statement("CREATE TRIGGER hardware_audit_failure BEFORE INSERT ON activity_log BEGIN SELECT RAISE(ABORT, 'audit unavailable'); END");
        $this->postJson('/platform/hardware/printers', $this->input())->assertStatus(500);
        $this->assertDatabaseCount('hardware_printers', 0);
    }
}
