<?php

namespace Tests\Feature\Modules\Platform\Hardware;

use App\Models\User;
use App\Modules\Inventory\Items\Database\Seeders\ItemsDemoSeeder;
use App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder;
use App\Modules\Platform\Hardware\Database\Seeders\HardwareDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ScannerWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/platform/hardware/scanner';

    protected function setUp(): void
    {
        parent::setUp();
        config(['pos_hardware.enabled' => true]);
        Permission::findOrCreate('inventory.view', 'web');
        $this->seed([UnitsDemoSeeder::class, ItemsDemoSeeder::class]);
    }

    public function test_session_auth_and_permission_are_required_without_new_grants(): void
    {
        $this->getJson(self::URL.'/resolve?code=UNKNOWN')->assertUnauthorized();
        $this->get(self::URL)->assertRedirect('/login');
        $user = User::factory()->create();
        $this->actingAs($user)->getJson(self::URL.'/resolve?code=UNKNOWN')->assertForbidden();
        $this->get(self::URL)->assertForbidden();
        $user->givePermissionTo('inventory.view');
        $this->withoutVite()->get(self::URL)->assertOk()
            ->assertViewHas('page', fn (array $page) => $page['component'] === 'platform/hardware/scanner');
        $this->getJson(self::URL.'/resolve?code=UNKNOWN')->assertUnprocessable()->assertJsonValidationErrors('code');
    }

    public function test_lookup_and_demo_seeder_never_mutate_business_data(): void
    {
        $this->actingAs(User::factory()->create()->givePermissionTo('inventory.view'));
        $row = DB::table('items')->where('sku', 'DEMO-CCTV-IP-2MP')->first();
        $unit = DB::table('units')->where('code', 'BOX')->first();
        DB::table('item_units')->where('item_id', $row->id)->where('unit_id', $unit->id)->update(['barcode' => '0012345']);
        $before = $this->fingerprint();

        for ($i = 0; $i < 2; $i++) {
            $response = $this->getJson(self::URL.'/resolve?code=0012345')->assertOk()
                ->assertJsonPath('item_id', $row->id)->assertJsonPath('unit_id', $unit->id)->assertJsonPath('barcode', '0012345');
            $keys = array_keys($response->json());
            sort($keys);
            $this->assertSame(['barcode', 'item_id', 'name', 'sku', 'unit_id', 'unit_name'], $keys);
            $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        }
        $this->getJson(self::URL.'/resolve?code=DEMO-CCTV-IP-2MP')->assertOk()->assertJsonPath('item_id', $row->id);
        $this->seed(HardwareDemoSeeder::class);
        $this->assertSame($before, $this->fingerprint());
    }

    public function test_invalid_input_and_foreign_fields_are_rejected(): void
    {
        $this->actingAs(User::factory()->create()->givePermissionTo('inventory.view'));
        foreach ([[], ['code' => ''], ['code' => ['bad']], ['code' => str_repeat('x', 65)],
            ['code' => "AA\nBB"], ['code' => 'DEMO-CCTV-IP-2MP', 'tenant_id' => 'x'], ['code' => 'DEMO', 'raw' => 'PRINT']] as $query) {
            $this->getJson(self::URL.'/resolve?'.http_build_query($query))->assertUnprocessable()->assertJsonValidationErrors('code');
        }
        $this->postJson(self::URL.'/resolve', ['code' => 'DEMO'])->assertStatus(405);
    }

    public function test_reserved_barcode_inactive_deleted_and_purchase_only_never_fall_back_to_other_sku(): void
    {
        $this->actingAs(User::factory()->create()->givePermissionTo('inventory.view'));
        $item = DB::table('items')->where('sku', 'DEMO-CCTV-IP-2MP')->first();
        $barcode = 'DEMO-CCTV-NVR-8CH';
        $relation = DB::table('item_units')->where('item_id', $item->id)->where('unit_id', $item->base_unit_id);
        $relation->update(['barcode' => $barcode]);
        $url = self::URL.'/resolve?code='.$barcode;
        $this->getJson($url)->assertOk()->assertJsonPath('item_id', $item->id);
        foreach ([['is_sale_unit' => false], ['is_sale_unit' => true, 'deleted_at' => now()]] as $update) {
            $relation->update($update);
            $this->getJson($url)->assertUnprocessable();
        }
        $relation->update(['deleted_at' => null, 'is_sale_unit' => true]);
        DB::table('items')->where('id', $item->id)->update(['is_active' => false]);
        $this->getJson($url)->assertUnprocessable();
        DB::table('items')->where('id', $item->id)->update(['is_active' => true, 'deleted_at' => now()]);
        $this->getJson($url)->assertUnprocessable();
    }

    public function test_resolver_is_rate_limited(): void
    {
        $this->actingAs(User::factory()->create()->givePermissionTo('inventory.view'));
        for ($i = 0; $i < 120; $i++) {
            $this->getJson(self::URL.'/resolve?code=UNKNOWN')->assertUnprocessable();
        }
        $this->getJson(self::URL.'/resolve?code=UNKNOWN')->assertStatus(429);
    }

    private function fingerprint(): string
    {
        $snapshot = [];
        foreach (Schema::getTableListing(schemaQualified: false) as $table) {
            if (in_array($table, ['cache', 'cache_locks', 'sessions'], true)) {
                continue;
            }
            $rows = DB::table($table)->get()->map(fn ($row) => json_encode($row, JSON_THROW_ON_ERROR))->all();
            sort($rows);
            $snapshot[$table] = $rows;
        }
        ksort($snapshot);

        return hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR));
    }
}
