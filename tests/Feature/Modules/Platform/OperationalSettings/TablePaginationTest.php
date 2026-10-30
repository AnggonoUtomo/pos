<?php

namespace Tests\Feature\Modules\Platform\OperationalSettings;

use App\Models\User;
use App\Modules\Platform\OperationalSettings\Application\Contracts\OperationalSettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TablePaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_override_and_settings_are_independent(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('super-system', 'web'));
        for ($i = 1; $i <= 32; $i++) {
            DB::table('units')->insert(['id' => (string) str()->ulid(), 'code' => 'QA-'.$i, 'name' => 'Satuan '.$i, 'symbol' => 's'.$i, 'is_active' => true]);
        }
        $this->actingAs($user)->getJson('/inventory/units/data')->assertOk()
            ->assertJsonPath('meta.per_page', 10)->assertJsonCount(10, 'data')->assertJsonPath('meta.last_page', 4);
        $this->getJson('/inventory/units/data?per_page=25&page=2')->assertOk()
            ->assertJsonPath('meta.per_page', 25)->assertJsonCount(7, 'data');
        $this->assertDatabaseHas('operational_settings', ['table_per_page' => 10]);
        $settings = app(OperationalSettingsRepository::class)->read();
        $this->putJson('/platform/operational-settings', [...$settings, 'table_per_page' => 50])->assertSuccessful();
        $this->getJson('/inventory/units/data')->assertOk()->assertJsonPath('meta.per_page', 50)->assertJsonCount(32, 'data');
        $this->assertDatabaseHas('activity_log', ['log_name' => 'platform.operational-settings', 'event' => 'updated']);
    }

    public function test_invalid_sizes_and_unauthorized_setting_updates_are_rejected(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('super-system', 'web'));
        foreach (['0', '-1', '11', '100000', 'all', '10.5', '10&per_page[]=25'] as $value) {
            $this->actingAs($user)->getJson('/inventory/units/data?per_page='.$value)->assertUnprocessable()->assertJsonValidationErrors('per_page');
        }
        $settings = app(OperationalSettingsRepository::class)->read();
        $this->putJson('/platform/operational-settings', [...$settings, 'table_per_page' => 11])->assertUnprocessable();
        $this->actingAs(User::factory()->create())->putJson('/platform/operational-settings', [...$settings, 'table_per_page' => 25])->assertForbidden();
        $this->assertDatabaseHas('operational_settings', ['table_per_page' => 10, 'version' => 0]);
    }

    public function test_override_is_consumed_by_all_json_list_workspaces(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('super-system', 'web'));
        $this->actingAs($user);
        foreach (['inventory/units', 'inventory/warehouses', 'inventory/items', 'inventory/stock', 'inventory/transfers',
            'sales/customers', 'sales/customer-levels', 'sales/returns', 'sales/invoices',
            'purchasing/suppliers', 'purchasing/purchase-orders', 'purchasing/returns',
            'finance/payment-accounts', 'finance/payment-methods', 'finance/payments/history', 'finance/cashbook'] as $path) {
            $root = $path === 'sales/invoices' ? 'records.meta.per_page' : 'meta.per_page';
            $this->getJson('/'.$path.'/data?per_page=50')->assertOk()->assertJsonPath($root, 50);
        }
        $this->getJson('/pos/shifts?per_page=100')->assertOk()->assertJsonPath('meta.per_page', 100);
        $this->getJson('/reports/operations/data?per_page=25')->assertOk()->assertJsonPath('stock.meta.per_page', 25);
        $this->assertDatabaseHas('operational_settings', ['table_per_page' => 10]);
    }

    public function test_stale_version_does_not_overwrite_default(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('super-system', 'web'));
        $settings = app(OperationalSettingsRepository::class)->read();
        $this->actingAs($user)->putJson('/platform/operational-settings', [...$settings, 'table_per_page' => 25])->assertSuccessful();
        $this->putJson('/platform/operational-settings', [...$settings, 'table_per_page' => 100])->assertUnprocessable()->assertJsonValidationErrors('version');
        $this->assertDatabaseHas('operational_settings', ['table_per_page' => 25]);
    }
}
