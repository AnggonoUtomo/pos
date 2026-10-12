<?php

namespace Tests\Feature\Modules\Inventory;

use App\Models\User;
use App\Modules\Inventory\Items\Database\Seeders\ItemsDemoSeeder;
use App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder;
use App\Modules\Inventory\Units\Infrastructure\Persistence\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InventoryPagesTest extends TestCase
{
    use RefreshDatabase;

    public static function masters(): array
    {
        return [['warehouses', []], ['units', ['symbol' => 'pcs']]];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        foreach (['inventory.view', 'inventory.manage'] as $name) {
            Permission::findOrCreate($name, 'web');
        }
        $user = User::factory()->create();
        $user->givePermissionTo(['inventory.view', 'inventory.manage']);
        $this->actingAs($user);
    }

    #[DataProvider('masters')]
    public function test_inertia_page_has_server_filters_and_deleted_tab(string $module, array $extra): void
    {
        $id = $this->postJson("/inventory/{$module}", ['code' => 'FIND-ME', 'name' => 'Master', 'is_active' => false] + $extra)->json('id');
        $this->postJson("/inventory/{$module}", ['code' => 'OTHER', 'name' => 'Lain'] + $extra);
        $this->get("/inventory/{$module}?search=FIND&status=inactive")
            ->assertOk()->assertInertia(fn (Assert $page) => $page->component("inventory/{$module}/index")
            ->where('records.meta.total', 1)->where('records.data.0.id', $id)
            ->where('filters.search', 'FIND')->where('filters.status', 'inactive'));
        $this->deleteJson("/inventory/{$module}/{$id}")->assertOk();
        $this->get("/inventory/{$module}?trashed=1")
            ->assertOk()->assertInertia(fn (Assert $page) => $page->where('records.meta.total', 1)
            ->where('records.data.0.id', $id)->has('records.data.0.deleted_at'));
        $this->getJson("/inventory/{$module}/data")->assertOk()->assertJsonPath('meta.total', 1);
    }

    #[DataProvider('masters')]
    public function test_page_requires_view_and_filters_are_validated(string $module, array $extra): void
    {
        $this->getJson("/inventory/{$module}?status=unknown")->assertUnprocessable();
        $this->getJson("/inventory/{$module}?page=0")->assertUnprocessable();
        $this->getJson("/inventory/{$module}?trashed=invalid")->assertUnprocessable();
        $user = User::factory()->create();
        $user->givePermissionTo('inventory.manage');
        $this->actingAs($user)->get("/inventory/{$module}")->assertForbidden();
        auth()->logout();
        $this->get("/inventory/{$module}")->assertRedirect('/login');
    }

    public function test_item_page_searches_barcode_and_publishes_only_active_unit_choices(): void
    {
        $this->seed([UnitsDemoSeeder::class, ItemsDemoSeeder::class]);
        Unit::create(['code' => 'INACTIVE', 'name' => 'Tidak Aktif', 'symbol' => 'x', 'is_active' => false]);
        Unit::create(['code' => 'DELETED', 'name' => 'Terhapus', 'symbol' => 'x'])->delete();
        $this->get('/inventory/items?search=DEMO-CCTV-RJ45-BOX')
            ->assertOk()->assertInertia(fn (Assert $page) => $page->component('inventory/items/index')
            ->where('records.meta.total', 1)->where('records.data.0.sku', 'DEMO-CCTV-RJ45')
            ->has('records.data.0.units', 3)->has('unitOptions', 8)
            ->where('unitOptions.0.code', 'BOX')->where('unitOptions.0.symbol', 'dus'));
        $this->getJson('/inventory/items/data')->assertOk()->assertJsonPath('meta.total', 9);
        $this->getJson('/inventory/items?status=unknown')->assertUnprocessable();
        $this->getJson('/inventory/items?search='.str_repeat('x', 151))->assertUnprocessable();
    }

    public function test_item_deleted_tab_and_view_only_authorization(): void
    {
        $this->seed([UnitsDemoSeeder::class, ItemsDemoSeeder::class]);
        $id = DB::table('items')->where('sku', 'DEMO-ELEC-NYM-3X15')->value('id');
        $this->deleteJson("/inventory/items/{$id}")->assertOk();
        $this->get('/inventory/items?trashed=1')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('records.meta.total', 1)->where('records.data.0.id', $id)->has('records.data.0.units', 2));
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('inventory.view');
        $this->actingAs($viewer)->get('/inventory/items')->assertOk();
        $this->actingAs(User::factory()->create())->get('/inventory/items')->assertForbidden();
    }
}
