<?php

namespace Tests\Feature\Modules\Platform\CompanySettings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CompanySettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_update_requires_permission_version_and_audit(): void
    {
        foreach (['view', 'manage'] as $action) {
            Permission::findOrCreate('platform.company-settings.'.$action, 'web');
        }
        $user = User::factory()->create()->givePermissionTo('platform.company-settings.view');
        $this->actingAs($user)->putJson('/platform/company-settings', ['name' => 'Toko CCTV QA', 'version' => 0])->assertForbidden();
        $user->givePermissionTo('platform.company-settings.manage');
        $input = ['name' => 'Toko CCTV QA', 'address' => 'Alamat toko', 'phone' => '021-0000000', 'receipt_footer' => 'Terima kasih', 'version' => 0];
        $this->putJson('/platform/company-settings', $input)->assertOk();
        $this->assertDatabaseHas('company_settings', ['name' => 'Toko CCTV QA', 'version' => 1]);
        $this->assertDatabaseHas('activity_log', ['log_name' => 'platform.company-settings', 'event' => 'updated']);
        $this->putJson('/platform/company-settings', $input)->assertUnprocessable();
        $this->putJson('/platform/company-settings', array_replace($input, ['version' => 1]))->assertOk();
        $this->assertSame(1, DB::table('activity_log')->where('log_name', 'platform.company-settings')->count());
        $this->putJson('/platform/company-settings', array_replace($input, ['version' => 1, 'name' => '']))->assertUnprocessable();
    }
}
