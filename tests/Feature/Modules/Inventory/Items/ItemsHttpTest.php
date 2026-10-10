<?php

namespace Tests\Feature\Modules\Inventory\Items;

use App\Models\User;
use App\Modules\Inventory\Items\Application\Contracts\ItemRepository;
use App\Modules\Inventory\Items\Database\Seeders\ItemsDemoSeeder;
use App\Modules\Inventory\Items\Infrastructure\Persistence\Models\Item;
use App\Modules\Inventory\Items\Infrastructure\Persistence\Models\ItemUnit;
use App\Modules\Inventory\Units\Application\Contracts\UnitLookup;
use App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ItemsHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['inventory.view', 'inventory.manage'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $this->seed(UnitsDemoSeeder::class);
    }

    private function payload(): array
    {
        return ['sku' => ' ITEM ', 'name' => ' Barang ', 'base_unit_id' => app(UnitLookup::class)->findActiveByCode('PCS')->id];
    }

    public function test_guest_and_permission_matrix_are_enforced(): void
    {
        $this->getJson('/inventory/items/data')->assertUnauthorized();
        $this->postJson('/inventory/items', [])->assertUnauthorized();
        $user = User::factory()->create();
        $this->actingAs($user)->getJson('/inventory/items/data')->assertForbidden();
        $this->postJson('/inventory/items', [])->assertForbidden();
        $user->givePermissionTo('inventory.view');
        $this->getJson('/inventory/items/data')->assertOk();
        $this->postJson('/inventory/items', $this->payload())->assertForbidden();
        $user->revokePermissionTo('inventory.view');
        $user->givePermissionTo('inventory.manage');
        $this->getJson('/inventory/items/data')->assertForbidden();
        $this->postJson('/inventory/items', $this->payload())->assertCreated();
    }

    public function test_create_preserves_flags_normalizes_and_records_actor_on_every_subject(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('inventory.manage');
        $box = app(UnitLookup::class)->findActiveByCode('BOX')->id;
        $payload = array_merge($this->payload(), ['id' => (string) Str::ulid(), 'is_active' => false, 'units' => [
            ['unit_id' => $box, 'conversion_ratio' => '12', 'barcode' => ' BOX-ITEM ', 'is_sale_unit' => false, 'is_purchase_unit' => true],
        ]]);
        $id = $this->actingAs($user)->postJson('/inventory/items', $payload)->assertCreated()->json('id');
        $this->assertNotSame($payload['id'], $id);
        $this->assertDatabaseHas('items', ['id' => $id, 'sku' => 'ITEM', 'name' => 'Barang', 'is_active' => false]);
        $this->assertDatabaseHas('item_units', ['item_id' => $id, 'unit_id' => $box, 'barcode' => 'BOX-ITEM', 'is_sale_unit' => false]);
        $this->assertDatabaseCount('item_units', 2);
        $logs = Activity::query()->whereIn('log_name', ['inventory.items', 'inventory.item_units'])->get();
        $this->assertCount(3, $logs);
        $this->assertTrue($logs->every(fn ($log) => $log->causer->is($user) && $log->subject !== null && Str::isUlid($log->subject_id)));
    }

    #[DataProvider('invalidUnitInputs')]
    public function test_invalid_relations_do_not_leave_partial_item_or_audit(string $case): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('inventory.manage');
        $payload = $this->payload();
        $pcs = $payload['base_unit_id'];
        $row = ['unit_id' => $pcs, 'conversion_ratio' => '1'];
        $payload['units'] = [$row];
        if ($case === 'base_ratio') {
            $payload['units'][0]['conversion_ratio'] = '2';
        } elseif ($case === 'float') {
            $payload['units'][0]['conversion_ratio'] = 1.5;
        } elseif ($case === 'precision') {
            $payload['units'][0]['conversion_ratio'] = '1.0000001';
        } elseif ($case === 'duplicate') {
            $payload['units'][] = $row;
        } elseif ($case === 'too_many') {
            $payload['units'] = array_fill(0, 101, $row);
        } elseif ($case === 'unknown') {
            $payload['base_unit_id'] = (string) Str::ulid();
        } else {
            DB::table('units')->where('id', $pcs)->update($case === 'inactive' ? ['is_active' => false] : ['deleted_at' => now()]);
        }
        $field = match ($case) {
            'float', 'precision' => 'units.0.conversion_ratio',
            'duplicate' => 'units.0.unit_id',
            default => 'units',
        };
        $this->actingAs($user)->postJson('/inventory/items', $payload)->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->assertDatabaseCount('items', 0);
        $this->assertDatabaseCount('item_units', 0);
        $this->assertSame(0, Activity::query()->whereIn('log_name', ['inventory.items', 'inventory.item_units'])->count());
    }

    public static function invalidUnitInputs(): array
    {
        return array_map(fn ($case) => [$case], ['base_ratio', 'float', 'precision', 'duplicate', 'too_many', 'unknown', 'inactive', 'deleted']);
    }

    public function test_duplicate_sku_or_barcode_including_deleted_is_a_validation_error(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('inventory.manage');
        $payload = $this->payload();
        $payload['units'] = [['unit_id' => $payload['base_unit_id'], 'conversion_ratio' => '1', 'barcode' => 'RESERVED']];
        $id = $this->actingAs($user)->postJson('/inventory/items', $payload)->assertCreated()->json('id');
        Item::query()->findOrFail($id)->delete();
        ItemUnit::query()->where('item_id', $id)->sole()->delete();
        $this->postJson('/inventory/items', $payload)->assertUnprocessable()->assertJsonValidationErrors(['sku', 'units.0.barcode']);
        $payload['sku'] = 'NEW';
        $this->postJson('/inventory/items', $payload)->assertUnprocessable()->assertJsonValidationErrors('units.0.barcode');
        $this->assertDatabaseCount('items', 1);
    }

    public function test_list_is_paginated_with_only_own_live_unit_relations(): void
    {
        $this->seed(ItemsDemoSeeder::class);
        $item = Item::query()->where('sku', 'DEMO-WATER-600')->sole();
        $item->delete();
        $tea = Item::query()->where('sku', 'DEMO-TEA-250')->sole();
        $tea->units()->firstOrFail()->delete();
        $user = User::factory()->create();
        $user->givePermissionTo('inventory.view');
        $this->actingAs($user)->getJson('/inventory/items/data')->assertOk()->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2)->assertJsonPath('meta.per_page', 25)
            ->assertJsonPath('data.0.sku', 'DEMO-RICE')->assertJsonCount(2, 'data.0.units')
            ->assertJsonCount(2, 'data.1.units')->assertJsonFragment(['conversion_ratio' => '1.000000']);
        $this->getJson('/inventory/items/data?page=0')->assertUnprocessable()->assertJsonValidationErrors('page');
    }

    public function test_super_system_bypass_does_not_bypass_validation_and_web_redirect_works(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('super-system', 'web'));
        $this->actingAs($user)->getJson('/inventory/items/data')->assertOk();
        $this->postJson('/inventory/items', [])->assertUnprocessable()->assertJsonValidationErrors(['sku', 'base_unit_id']);
        $this->assertDatabaseCount('items', 0);
        $this->from('/dashboard')->post('/inventory/items', $this->payload())->assertRedirect('/dashboard')->assertSessionHas('success');
    }

    public function test_persistence_conflict_is_redacted_and_data_audit_roll_back(): void
    {
        $this->mock(ItemRepository::class, function ($mock): void {
            $mock->shouldReceive('create')->once()->andReturnUsing(function ($data): string {
                Item::query()->create(['sku' => $data->sku, 'name' => $data->name, 'base_unit_id' => $data->baseUnitId]);
                throw new UniqueConstraintViolationException('testing', 'SQL-private-detail', [], new \RuntimeException('Conflict'));
            });
        });
        $user = User::factory()->create();
        $user->givePermissionTo('inventory.manage');
        $this->actingAs($user)->postJson('/inventory/items', $this->payload())->assertUnprocessable()
            ->assertJsonValidationErrors('inventory')->assertDontSee('SQL-private-detail');
        $this->assertDatabaseCount('items', 0);
        $this->assertSame(0, Activity::query()->where('log_name', 'inventory.items')->count());
    }

    public function test_zero_barcode_is_not_treated_as_empty_and_ulids_are_normalized(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('inventory.manage');
        $payload = $this->payload();
        $pcs = $payload['base_unit_id'];
        $payload['base_unit_id'] = strtoupper($pcs);
        $payload['units'] = [['unit_id' => strtoupper($pcs), 'conversion_ratio' => '1', 'barcode' => '0']];
        $this->actingAs($user)->postJson('/inventory/items', $payload)->assertCreated();
        $this->assertDatabaseHas('item_units', ['unit_id' => $pcs, 'barcode' => '0']);
    }

    public function test_case_variant_duplicate_unit_and_duplicate_payload_barcode_are_rejected(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('inventory.manage');
        $payload = $this->payload();
        $pcs = $payload['base_unit_id'];
        $box = app(UnitLookup::class)->findActiveByCode('BOX')->id;
        $payload['units'] = [
            ['unit_id' => $pcs, 'conversion_ratio' => '1'],
            ['unit_id' => strtoupper($pcs), 'conversion_ratio' => '1'],
        ];
        $this->actingAs($user)->postJson('/inventory/items', $payload)->assertUnprocessable()->assertJsonValidationErrors('units.0.unit_id');
        $payload['units'] = [
            ['unit_id' => $pcs, 'conversion_ratio' => '1', 'barcode' => 'SAME'],
            ['unit_id' => $box, 'conversion_ratio' => '12', 'barcode' => 'same'],
        ];
        $this->postJson('/inventory/items', $payload)->assertUnprocessable()->assertJsonValidationErrors('units.0.barcode');
        $this->assertDatabaseCount('items', 0);
    }
}
