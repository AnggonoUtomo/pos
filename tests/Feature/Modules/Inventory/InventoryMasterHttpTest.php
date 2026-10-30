<?php

namespace Tests\Feature\Modules\Inventory;

use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InventoryMasterHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['inventory.view', 'inventory.manage'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }

    public static function masters(): array
    {
        return [['warehouses', []], ['units', ['symbol' => ' pcs ']]];
    }

    #[DataProvider('masters')]
    public function test_guest_and_user_without_permissions_cannot_read_or_write(string $module, array $extra): void
    {
        $this->getJson("/inventory/{$module}/data")->assertUnauthorized();
        $this->postJson("/inventory/{$module}", [])->assertUnauthorized();
        $this->actingAs(User::factory()->create());
        $this->getJson("/inventory/{$module}/data")->assertForbidden();
        $this->postJson("/inventory/{$module}", [])->assertForbidden();
        $this->assertDatabaseCount($module, 0);
        $this->assertDatabaseCount('activity_log', 0);
    }

    #[DataProvider('masters')]
    public function test_view_and_manage_are_independent_and_create_is_audited(string $module, array $extra): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('inventory.view');
        $this->actingAs($viewer)->getJson("/inventory/{$module}/data")
            ->assertOk()->assertJsonPath('meta.total', 0);
        $this->postJson("/inventory/{$module}", [])->assertForbidden();

        $manager = User::factory()->create();
        $manager->givePermissionTo('inventory.manage');
        $this->actingAs($manager)->getJson("/inventory/{$module}/data")->assertForbidden();
        $clientId = (string) Str::ulid();
        $response = $this->postJson("/inventory/{$module}", array_merge($extra, [
            'code' => ' QA ', 'name' => ' Nama Master ', 'id' => $clientId,
            'deleted_at' => now()->toISOString(), 'is_active' => false,
        ]))->assertCreated()->assertJsonStructure(['id']);
        $id = $response->json('id');
        $this->assertTrue(Str::isUlid($id));
        $this->assertNotSame($clientId, $id);
        $this->assertDatabaseHas($module, ['id' => $id, 'code' => 'QA', 'name' => 'Nama Master', 'is_active' => false, 'deleted_at' => null]);
        if ($module === 'units') {
            $this->assertDatabaseHas('units', ['id' => $id, 'symbol' => 'pcs']);
        }
        $log = Activity::query()->where('log_name', "inventory.{$module}")->sole();
        $this->assertSame($id, $log->subject_id);
        $this->assertTrue($log->causer->is($manager));
        $this->assertNotNull($log->subject);
    }

    #[DataProvider('masters')]
    public function test_list_is_paginated_and_excludes_soft_deleted_rows(string $module, array $extra): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['inventory.view', 'inventory.manage']);
        $this->actingAs($user);
        for ($i = 0; $i < 27; $i++) {
            $this->postJson("/inventory/{$module}", array_merge($extra, ['code' => sprintf('QA-%02d', $i), 'name' => 'Master', 'is_active' => $i !== 0]))->assertCreated();
        }
        DB::table($module)->where('code', 'QA-26')->update(['deleted_at' => now()]);
        $this->getJson("/inventory/{$module}/data?per_page=25")->assertOk()
            ->assertJsonCount(25, 'data')->assertJsonPath('meta.total', 26)
            ->assertJsonPath('meta.per_page', 25)->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('data.0.code', 'QA-00')->assertJsonPath('data.0.is_active', false);
        $this->getJson("/inventory/{$module}/data?page=2&per_page=25")->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.code', 'QA-25');
        foreach (['0', '-1', 'abc', '1.5'] as $page) {
            $this->getJson("/inventory/{$module}/data?page={$page}")->assertUnprocessable()->assertJsonValidationErrors('page');
        }
    }

    #[DataProvider('masters')]
    public function test_invalid_and_reserved_code_fail_without_new_audit(string $module, array $extra): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('inventory.manage');
        $this->actingAs($user);
        $this->postJson("/inventory/{$module}", ['code' => ' ', 'name' => ' '])->assertUnprocessable()->assertJsonValidationErrors(['code', 'name']);
        $this->assertDatabaseCount($module, 0);
        $this->assertDatabaseCount('activity_log', 0);
        $payload = array_merge($extra, ['code' => 'QA', 'name' => 'Master']);
        $id = $this->postJson("/inventory/{$module}", $payload)->assertCreated()->json('id');
        DB::table($module)->where('id', $id)->update(['deleted_at' => now()]);
        $this->postJson("/inventory/{$module}", $payload)->assertUnprocessable()->assertJsonValidationErrors('code');
        $this->assertDatabaseCount($module, 1);
        $this->assertDatabaseCount('activity_log', 1);
    }

    #[DataProvider('masters')]
    public function test_super_system_and_web_redirect_use_existing_auth_contract(string $module, array $extra): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('super-system', 'web'));
        $this->actingAs($user)->getJson("/inventory/{$module}/data")->assertOk();
        $this->from('/dashboard')->post("/inventory/{$module}", array_merge($extra, ['code' => 'QA', 'name' => 'Master']))
            ->assertRedirect('/dashboard')->assertSessionHas('success');
        $this->assertDatabaseCount($module, 1);
    }

    public function test_guest_web_request_redirects_to_login(): void
    {
        $this->get('/inventory/warehouses/data')->assertRedirect(route('login'));
        $this->post('/inventory/units', [])->assertRedirect(route('login'));
        $this->assertDatabaseCount('units', 0);
    }

    #[DataProvider('masters')]
    public function test_persistence_unique_conflict_returns_validation_error_and_rolls_back_audit(string $module, array $extra): void
    {
        $singular = $module === 'units' ? 'Unit' : 'Warehouse';
        $namespace = 'App\\Modules\\Inventory\\'.ucfirst($module);
        $model = $namespace.'\\Infrastructure\\Persistence\\Models\\'.$singular;
        $this->mock($namespace.'\\Application\\Contracts\\'.$singular.'Repository', function ($mock) use ($model, $extra): void {
            $mock->shouldReceive('create')->once()->andReturnUsing(function ($data) use ($model, $extra): string {
                $model::query()->create(array_merge($extra, ['code' => $data->code, 'name' => $data->name]));
                throw new UniqueConstraintViolationException('testing', 'SQL-private-detail', [], new \RuntimeException('Conflict'));
            });
        });
        $user = User::factory()->create();
        $user->givePermissionTo('inventory.manage');
        $this->actingAs($user)->postJson("/inventory/{$module}", array_merge($extra, ['code' => 'QA', 'name' => 'Master']))
            ->assertUnprocessable()->assertJsonValidationErrors('code')->assertDontSee('SQL-private-detail');
        $this->assertDatabaseCount($module, 0);
        $this->assertDatabaseCount('activity_log', 0);
    }
}
