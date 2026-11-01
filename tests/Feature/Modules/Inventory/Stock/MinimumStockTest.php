<?php

namespace Tests\Feature\Modules\Inventory\Stock;

use App\Models\User;
use App\Modules\Inventory\Stock\Application\Actions\ManageMinimumStock;
use App\Modules\Inventory\Stock\Application\Actions\PostAdjustment;
use App\Modules\Inventory\Stock\Application\Contracts\MinimumStockAlerts;
use App\Modules\Inventory\Stock\Application\Contracts\MinimumStockRepository;
use App\Modules\Inventory\Stock\Database\Seeders\StockAccessSeeder;
use App\Modules\Inventory\Warehouses\Infrastructure\Persistence\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;

class MinimumStockTest extends StockTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(StockAccessSeeder::class);
        $this->manager->givePermissionTo(Permission::all());
    }

    public function test_missing_balance_is_zero_and_minimum_zero_still_warns_without_writing_stock(): void
    {
        $this->assertSame(['low_count' => 0, 'empty_count' => 0], app(MinimumStockAlerts::class)->summary());
        app(ManageMinimumStock::class)->create(['warehouse_id' => $this->warehouse, 'item_id' => $this->item,
            'minimum_quantity' => '0', 'is_active' => true], $this->manager->id);
        $this->assertSame(['low_count' => 1, 'empty_count' => 1], app(MinimumStockAlerts::class)->summary());
        $this->assertDatabaseCount('stock_balances', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseCount('fifo_layers', 0);
    }

    private function input(string $minimum = '24'): array
    {
        return ['warehouse_id' => $this->warehouse, 'item_id' => $this->item,
            'minimum_quantity' => $minimum, 'is_active' => true];
    }

    private function filters(): array
    {
        return ['search' => '', 'warehouse_id' => '', 'status' => 'all', 'trashed' => false];
    }

    public function test_equal_is_low_and_real_stock_changes_refresh_warning_without_blocking_writes(): void
    {
        $this->postJson('/inventory/stock/openings/'.$this->draft().'/post')->assertOk();
        $this->postJson('/inventory/stock/minimums', $this->input())->assertCreated();
        $alerts = app(MinimumStockAlerts::class);
        $this->assertSame(1, $alerts->summary()['low_count']);
        $rows = app(MinimumStockRepository::class)->paginate($this->filters(), 1);
        $this->assertSame('0.000000', $rows['data'][0]['shortage']);
        app(PostAdjustment::class)->handle(['idempotency_key' => (string) Str::ulid(), 'warehouse_id' => $this->warehouse,
            'item_id' => $this->item, 'direction' => 'in', 'quantity' => '0.000001', 'unit_cost' => '1', 'reason' => 'QA'], $this->manager->id);
        $this->assertSame(0, $alerts->summary()['low_count']);
        $this->assertDatabaseCount('stock_movements', 2);
    }

    public function test_crud_duplicate_soft_delete_restore_noop_stale_and_audit(): void
    {
        $id = $this->postJson('/inventory/stock/minimums', $this->input())->assertCreated()->json('id');
        $audit = Activity::count();
        $this->putJson('/inventory/stock/minimums/'.$id, $this->input() + ['version' => 0])->assertOk();
        $this->assertSame($audit, Activity::count());
        $this->postJson('/inventory/stock/minimums', $this->input())->assertUnprocessable();
        $this->putJson('/inventory/stock/minimums/'.$id, ['minimum_quantity' => '0', 'is_active' => false, 'version' => 0])->assertOk();
        $this->assertSame(0, app(MinimumStockAlerts::class)->summary()['low_count']);
        $this->deleteJson('/inventory/stock/minimums/'.$id, ['version' => 0])->assertUnprocessable();
        $this->deleteJson('/inventory/stock/minimums/'.$id, ['version' => 1])->assertOk();
        $this->postJson('/inventory/stock/minimums', $this->input())->assertUnprocessable();
        $this->postJson('/inventory/stock/minimums/'.$id.'/restore', ['version' => 2])->assertOk();
        $this->assertDatabaseHas('stock_minimums', ['id' => $id, 'version' => 3, 'deleted_at' => null, 'is_active' => false]);
        foreach (['created', 'updated', 'deleted', 'restored'] as $event) {
            $this->assertDatabaseHas('activity_log', ['event' => 'minimum.'.$event, 'subject_id' => $id]);
        }
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseCount('fifo_layers', 0);
    }

    #[DataProvider('invalidMinimums')]
    public function test_invalid_minimum_is_rejected(string $minimum): void
    {
        $this->postJson('/inventory/stock/minimums', $this->input($minimum))->assertUnprocessable();
        $this->assertDatabaseCount('stock_minimums', 0);
    }

    public static function invalidMinimums(): array
    {
        return [['-1'], ['1,5'], ['1.0000001'], ['100000000000000'], ['NaN']];
    }

    public function test_precision_summaries_filters_and_pagination_are_not_limited_to_first_page(): void
    {
        foreach (range(1, 12) as $n) {
            $warehouse = Warehouse::create(['code' => 'W'.$n, 'name' => 'Gudang '.$n, 'is_active' => true]);
            app(ManageMinimumStock::class)->create(array_replace($this->input('99999999999999.999999'), ['warehouse_id' => $warehouse->id]), $this->manager->id);
        }
        $id = app(ManageMinimumStock::class)->create($this->input('99999999999999.999998'), $this->manager->id);
        DB::table('stock_balances')->insert(['id' => strtolower((string) Str::ulid()), 'item_id' => $this->item, 'warehouse_id' => $this->warehouse,
            'quantity' => '99999999999999.999999', 'total_value' => '1.000000', 'created_at' => now(), 'updated_at' => now()]);
        $this->assertSame(12, app(MinimumStockAlerts::class)->summary()['low_count']);
        $this->get('/inventory/stock/minimums')->assertOk()->assertInertia(fn (Assert $p) => $p->component('inventory/stock/minimums/index', false)
            ->has('records.data', 10)->where('records.meta.total', 13)->where('records.summary.low_count', 12));
        $this->get('/inventory/stock/minimums?per_page=25&status=low')->assertOk()->assertInertia(fn (Assert $p) => $p
            ->has('records.data', 12)->where('records.meta.per_page', 25));
        $this->get('/inventory/stock/minimums?status=healthy')->assertOk()->assertInertia(fn (Assert $p) => $p->has('records.data', 1)->where('records.data.0.id', $id));
        $this->get('/inventory/stock/minimums?search=%25')->assertOk()->assertInertia(fn (Assert $p) => $p->has('records.data', 0));
    }

    public function test_permission_boundaries_and_master_inactivation_suppress_alert(): void
    {
        $id = app(ManageMinimumStock::class)->create($this->input(), $this->manager->id);
        $viewer = User::factory()->create()->givePermissionTo('inventory.stock.minimums.view');
        $this->actingAs($viewer)->get('/inventory/stock/minimums')->assertOk();
        $this->postJson('/inventory/stock/minimums', $this->input())->assertForbidden();
        $this->putJson('/inventory/stock/minimums/'.$id, [])->assertForbidden();
        $this->deleteJson('/inventory/stock/minimums/'.$id, ['version' => 0])->assertForbidden();
        $this->postJson('/inventory/stock/minimums/'.$id.'/restore', ['version' => 0])->assertForbidden();
        $this->actingAs(User::factory()->create())->get('/inventory/stock/minimums')->assertForbidden();
        DB::table('items')->where('id', $this->item)->update(['is_active' => false]);
        $this->assertSame(0, app(MinimumStockAlerts::class)->summary()['low_count']);
        $this->assertSame('unavailable', app(MinimumStockRepository::class)->paginate($this->filters(), 1)['data'][0]['status']);
        $this->actingAs($this->manager)->putJson('/inventory/stock/minimums/'.$id, ['minimum_quantity' => '1', 'is_active' => true, 'version' => 0])->assertUnprocessable();
        $this->deleteJson('/inventory/stock/minimums/'.$id, ['version' => 0])->assertOk();
        $this->postJson('/inventory/stock/minimums/'.$id.'/restore', ['version' => 1])->assertUnprocessable();
    }

    public function test_failed_audit_rolls_back_threshold(): void
    {
        Activity::creating(fn ($log) => str_starts_with($log->event ?? '', 'minimum.') ? false : null);
        try {
            app(ManageMinimumStock::class)->create($this->input(), $this->manager->id);
            $this->fail('Audit wajib tersimpan.');
        } catch (\RuntimeException $error) {
            $this->assertStringContainsString('Audit', $error->getMessage());
        }
        $this->assertDatabaseCount('stock_minimums', 0);
    }

    public function test_dashboard_alerts_have_independent_permission_and_no_mutation(): void
    {
        app(ManageMinimumStock::class)->create($this->input(), $this->manager->id);
        $viewer = User::factory()->create()->givePermissionTo('inventory.stock.minimums.view');
        $before = Activity::count();
        $this->actingAs($viewer)->get('/dashboard')->assertOk()->assertInertia(fn (Assert $p) => $p
            ->where('summary', null)->where('low_stock.low_count', 1)->where('low_stock.empty_count', 1));
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertOk()->assertInertia(fn (Assert $p) => $p->where('low_stock', null));
        $this->assertSame($before, Activity::count());
    }
}
