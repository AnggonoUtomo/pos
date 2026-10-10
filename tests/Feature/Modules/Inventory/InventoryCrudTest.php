<?php

namespace Tests\Feature\Modules\Inventory;

use App\Models\User;
use App\Modules\Inventory\Items\Application\Contracts\ItemRepository;
use App\Modules\Inventory\Items\Application\DTOs\CreateItemData;
use App\Modules\Inventory\Items\Infrastructure\Persistence\Models\Item;
use App\Modules\Inventory\Items\Infrastructure\Persistence\Repositories\EloquentItemRepository;
use App\Modules\Inventory\Units\Infrastructure\Persistence\Models\Unit;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InventoryCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['inventory.view', 'inventory.manage'] as $name) {
            Permission::findOrCreate($name, 'web');
        }
        $user = User::factory()->create();
        $user->givePermissionTo(['inventory.view', 'inventory.manage']);
        $this->actingAs($user);
    }

    public static function masters(): array
    {
        return [['warehouses', []], ['units', ['symbol' => 'pcs']]];
    }

    #[DataProvider('masters')]
    public function test_edit_delete_restore_are_audited_and_uniqueness_is_retained(string $module, array $extra): void
    {
        $id = $this->postJson("/inventory/{$module}", ['code' => 'QA', 'name' => 'Awal'] + $extra)->assertCreated()->json('id');
        $this->putJson("/inventory/{$module}/{$id}", ['code' => ' QA ', 'name' => ' Diubah ', 'is_active' => false] + $extra)
            ->assertOk()->assertJsonPath('id', $id);
        $this->assertDatabaseHas($module, ['id' => $id, 'name' => 'Diubah', 'is_active' => false]);
        $this->deleteJson("/inventory/{$module}/{$id}")->assertOk();
        $this->assertSoftDeleted($module, ['id' => $id]);
        $this->putJson("/inventory/{$module}/{$id}", ['code' => 'QA', 'name' => 'Deleted', 'is_active' => true] + $extra)->assertNotFound();
        $this->postJson("/inventory/{$module}", ['code' => 'QA', 'name' => 'Duplicate'] + $extra)->assertUnprocessable();
        $this->postJson("/inventory/{$module}/{$id}/restore")->assertOk();
        $this->assertDatabaseHas($module, ['id' => $id, 'deleted_at' => null]);
        $this->postJson("/inventory/{$module}/{$id}/restore")->assertNotFound();
        $this->assertSame(['created', 'updated', 'deleted', 'restored'], Activity::where('subject_id', $id)->orderBy('id')->pluck('event')->all());
        $this->assertSame(1, Activity::where('subject_id', $id)->distinct()->count('causer_id'));
    }

    #[DataProvider('masters')]
    public function test_view_permission_cannot_mutate(string $module, array $extra): void
    {
        $id = $this->postJson("/inventory/{$module}", ['code' => 'QA', 'name' => 'Master'] + $extra)->json('id');
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('inventory.view');
        $this->actingAs($viewer);
        $this->putJson("/inventory/{$module}/{$id}", [])->assertForbidden();
        $this->deleteJson("/inventory/{$module}/{$id}")->assertForbidden();
        $this->postJson("/inventory/{$module}/{$id}/restore")->assertForbidden();
        $this->assertDatabaseCount('activity_log', 1);
    }

    public function test_referenced_unit_cannot_be_deleted_or_deactivated_even_for_deleted_item(): void
    {
        [$item, $base] = $this->makeItem();
        $this->deleteJson("/inventory/units/{$base}")->assertUnprocessable()->assertJsonValidationErrors('inventory');
        $this->from('/inventory/units')->delete("/inventory/units/{$base}")->assertRedirect('/inventory/units')->assertSessionHasErrors('inventory');
        $this->putJson("/inventory/units/{$base}", ['code' => 'PCS', 'name' => 'Buah', 'symbol' => 'pcs', 'is_active' => false])->assertUnprocessable();
        $this->deleteJson("/inventory/items/{$item}")->assertOk();
        $this->deleteJson("/inventory/units/{$base}")->assertUnprocessable();
        $this->assertDatabaseHas('units', ['id' => $base, 'is_active' => true, 'deleted_at' => null]);
    }

    public function test_item_base_unit_is_immutable_and_invalid_ratio_rolls_back(): void
    {
        [$id, $base, $box] = $this->makeItem();
        $before = Activity::count();
        $this->putJson("/inventory/items/{$id}", ['sku' => 'QA', 'name' => 'Changed', 'base_unit_id' => $box, 'is_active' => true])->assertUnprocessable()->assertJsonValidationErrors('units');
        $this->putJson("/inventory/items/{$id}", ['sku' => 'QA', 'name' => 'Changed', 'base_unit_id' => $base, 'is_active' => true,
            'units' => [['unit_id' => $base, 'conversion_ratio' => '2']]])->assertUnprocessable()->assertJsonValidationErrors('units');
        $this->assertDatabaseHas('items', ['id' => $id, 'name' => 'Barang', 'base_unit_id' => $base]);
        $this->assertSame($before, Activity::count());
    }

    public function test_item_edit_syncs_relations_with_soft_delete_and_restore(): void
    {
        [$id, $base, $box] = $this->makeItem();
        $relation = DB::table('item_units')->where('item_id', $id)->where('unit_id', $box)->value('id');
        $payload = ['sku' => 'QA', 'name' => 'Baru', 'base_unit_id' => $base, 'is_active' => true, 'units' => [
            ['unit_id' => $base, 'conversion_ratio' => '1', 'barcode' => 'QA-PCS'],
            ['unit_id' => $box, 'conversion_ratio' => '24', 'barcode' => 'QA-BOX', 'is_sale_unit' => false],
        ]];
        $this->putJson("/inventory/items/{$id}", $payload)->assertOk();
        $this->assertDatabaseHas('item_units', ['id' => $relation, 'conversion_ratio' => 24, 'is_sale_unit' => false]);
        $payload['units'] = [$payload['units'][0]];
        $this->putJson("/inventory/items/{$id}", $payload)->assertOk();
        $this->assertSoftDeleted('item_units', ['id' => $relation]);
        $payload['units'][] = ['unit_id' => $box, 'conversion_ratio' => '12', 'barcode' => 'QA-BOX'];
        $this->putJson("/inventory/items/{$id}", $payload)->assertOk();
        $this->assertDatabaseHas('item_units', ['id' => $relation, 'deleted_at' => null]);
        $this->deleteJson("/inventory/items/{$id}")->assertOk();
        $this->assertSoftDeleted('items', ['id' => $id]);
        $this->postJson("/inventory/items/{$id}/restore")->assertOk();
        $this->assertSame(2, Item::findOrFail($id)->units()->count());
    }

    public function test_conflicting_barcode_update_is_rejected_without_partial_audit(): void
    {
        [$id, $base, $box] = $this->makeItem();
        $other = Item::create(['sku' => 'OTHER', 'name' => 'Other', 'base_unit_id' => $base]);
        $other->units()->create(['unit_id' => $base, 'conversion_ratio' => '1', 'barcode' => 'RESERVED']);
        $before = Activity::count();
        $this->putJson("/inventory/items/{$id}", ['sku' => 'QA', 'name' => 'Changed', 'base_unit_id' => $base, 'is_active' => true,
            'units' => [['unit_id' => $box, 'conversion_ratio' => '12', 'barcode' => 'RESERVED']]])->assertUnprocessable()->assertJsonValidationErrors('units.0.barcode');
        $this->assertDatabaseHas('items', ['id' => $id, 'name' => 'Barang']);
        $this->assertSame($before, Activity::count());
    }

    public function test_item_mutations_require_manage_and_guest_authentication(): void
    {
        [$id] = $this->makeItem();
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('inventory.view');
        $this->actingAs($viewer);
        $this->putJson("/inventory/items/{$id}", [])->assertForbidden();
        $this->deleteJson("/inventory/items/{$id}")->assertForbidden();
        $this->postJson("/inventory/items/{$id}/restore")->assertForbidden();
        auth()->logout();
        $this->deleteJson("/inventory/items/{$id}")->assertUnauthorized();
    }

    public function test_invalid_or_missing_identifiers_are_not_found(): void
    {
        foreach (['warehouses', 'units', 'items'] as $module) {
            $this->deleteJson("/inventory/{$module}/not-a-ulid")->assertNotFound();
            $this->deleteJson("/inventory/{$module}/01arz3ndektsv4rrffq69g5fav")->assertNotFound();
            $this->postJson("/inventory/{$module}/01arz3ndektsv4rrffq69g5fav/restore")->assertNotFound();
        }
    }

    public function test_persistence_failure_rolls_back_item_edit_and_all_audit(): void
    {
        [$id, $base] = $this->makeItem();
        $before = Activity::count();
        $repository = \Mockery::mock(EloquentItemRepository::class)->makePartial();
        $repository->shouldReceive('update')->once()->andReturnUsing(function (string $itemId, CreateItemData $data): void {
            Item::findOrFail($itemId)->update(['name' => $data->name]);
            throw new UniqueConstraintViolationException('testing', 'PRIVATE SQL', [], new \Exception('PRIVATE DETAIL'));
        });
        $this->app->instance(ItemRepository::class, $repository);
        $this->putJson("/inventory/items/{$id}", ['sku' => 'QA', 'name' => 'Changed', 'base_unit_id' => $base, 'is_active' => true])
            ->assertUnprocessable()->assertJsonValidationErrors('inventory')->assertDontSee('PRIVATE');
        $this->assertDatabaseHas('items', ['id' => $id, 'name' => 'Barang']);
        $this->assertSame($before, Activity::count());
    }

    private function makeItem(): array
    {
        $base = Unit::create(['code' => 'PCS', 'name' => 'Buah', 'symbol' => 'pcs'])->id;
        $box = Unit::create(['code' => 'BOX', 'name' => 'Dus', 'symbol' => 'dus'])->id;
        $id = $this->postJson('/inventory/items', ['sku' => 'QA', 'name' => 'Barang', 'base_unit_id' => $base,
            'units' => [['unit_id' => $box, 'conversion_ratio' => '12', 'barcode' => 'QA-BOX']]])->assertCreated()->json('id');

        return [$id, $base, $box];
    }
}
