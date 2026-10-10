<?php

namespace Tests\Feature\Modules\Inventory;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
