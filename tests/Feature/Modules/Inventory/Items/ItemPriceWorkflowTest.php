<?php

namespace Tests\Feature\Modules\Inventory\Items;

use App\Models\User;
use App\Modules\Inventory\Items\Infrastructure\Persistence\Models\Item;
use App\Modules\Inventory\Units\Infrastructure\Persistence\Models\Unit;
use App\Modules\Sales\Parties\Application\Contracts\ItemPriceRepository;
use App\Modules\Sales\Parties\Infrastructure\Persistence\Models\CustomerLevel;
use App\Modules\Sales\Parties\Infrastructure\Persistence\Models\ItemPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ItemPriceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function payload(): array
    {
        $this->actingAs(User::factory()->create()->assignRole(Role::findOrCreate('super-system', 'web')));
        $unit = Unit::create(['code' => 'PCS', 'name' => 'Buah', 'symbol' => 'pcs'])->id;
        $levels = collect(['ECERAN', 'GROSIR'])->map(fn ($code) => CustomerLevel::create(['code' => $code, 'name' => $code])->id);

        return ['sku' => 'PRICE-QA', 'name' => 'Barang harga', 'base_unit_id' => $unit, 'is_active' => true,
            'prices' => $levels->map(fn ($level) => ['unit_id' => $unit, 'customer_level_id' => $level, 'amount' => '999999999999999999.99'])->all()];
    }

    public function test_complete_matrix_persists_reloads_and_noop_does_not_audit(): void
    {
        $payload = $this->payload();
        $id = $this->postJson('/inventory/items', $payload)->assertCreated()->json('id');
        $this->assertSame($payload['prices'], $this->getJson('/inventory/items/data')->assertOk()->json('data.0.prices'));
        $before = DB::table('activity_log')->count();
        $this->putJson("/inventory/items/{$id}", $payload)->assertOk();
        $this->assertSame($before, DB::table('activity_log')->count());
        $payload['prices'][0]['amount'] = '1250.25';
        $this->putJson("/inventory/items/{$id}", $payload)->assertOk();
        $this->assertDatabaseHas('item_prices', ['item_id' => $id, 'amount' => '1250.25']);
    }

    public function test_missing_duplicate_zero_and_stale_matrix_roll_back_catalog_and_audit(): void
    {
        $valid = $this->payload();
        foreach (['missing', 'duplicate', 'zero', 'precision', 'unknown'] as $case) {
            $payload = $valid;
            if ($case === 'missing') {
                unset($payload['prices']);
            } elseif ($case === 'duplicate') {
                $payload['prices'][] = $payload['prices'][0];
            } elseif ($case === 'zero' || $case === 'precision') {
                $payload['prices'][0]['amount'] = $case === 'zero' ? '0' : '1.001';
            } else {
                $payload['prices'][0]['unit_id'] = (string) Str::ulid();
            }
            $before = DB::table('activity_log')->count();
            $this->postJson('/inventory/items', $payload)->assertUnprocessable();
            $this->assertDatabaseCount('items', 0);
            $this->assertDatabaseCount('item_units', 0);
            $this->assertDatabaseCount('item_prices', 0);
            $this->assertSame($before, DB::table('activity_log')->count());
        }
        $id = $this->postJson('/inventory/items', $valid)->assertCreated()->json('id');
        CustomerLevel::create(['code' => 'INSTALLER', 'name' => 'Installer']);
        $before = DB::table('activity_log')->count();
        $valid['name'] = 'Must rollback';
        $this->putJson("/inventory/items/{$id}", $valid)->assertUnprocessable()->assertJsonValidationErrors('prices');
        $this->assertSame('Barang harga', Item::findOrFail($id)->name);
        $this->assertSame($before, DB::table('activity_log')->count());
    }

    public function test_price_persistence_failure_rolls_back_catalog_units_and_audit(): void
    {
        $payload = $this->payload();
        $repository = $this->app->make(ItemPriceRepository::class);
        $mock = \Mockery::mock($repository)->makePartial();
        $mock->shouldReceive('replace')->once()->andReturnUsing(function ($id, $prices): void {
            ItemPrice::create(['item_id' => $id, 'unit_id' => $prices[0]->unitId,
                'customer_level_id' => $prices[0]->customerLevelId, 'amount' => $prices[0]->amount]);
            throw new \RuntimeException('Simulated pricing persistence failure');
        });
        $this->app->instance(ItemPriceRepository::class, $mock);
        $before = DB::table('activity_log')->count();
        $this->withoutExceptionHandling();
        try {
            $this->postJson('/inventory/items', $payload);
            $this->fail('Must fail atomically.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated pricing persistence failure', $exception->getMessage());
        }
        $this->assertDatabaseCount('items', 0);
        $this->assertDatabaseCount('item_units', 0);
        $this->assertDatabaseCount('item_prices', 0);
        $this->assertSame($before, DB::table('activity_log')->count());
    }

    public function test_purchase_only_item_has_no_price_requirement(): void
    {
        $payload = $this->payload();
        $payload['units'] = [['unit_id' => $payload['base_unit_id'], 'conversion_ratio' => '1', 'is_sale_unit' => false]];
        $payload['prices'] = [];
        $this->postJson('/inventory/items', $payload)->assertCreated();
        $this->assertDatabaseCount('item_prices', 0);
    }
}
