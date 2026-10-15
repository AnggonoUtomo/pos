<?php

namespace Tests\Feature\Modules\Inventory\Stock;

use App\Modules\Inventory\Stock\Application\Actions\IssueStock;
use App\Modules\Inventory\Stock\Application\Contracts\StockRepository;
use App\Modules\Inventory\Warehouses\Infrastructure\Persistence\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Activitylog\Models\Activity;

class StockIssueTest extends StockTestCase
{
    private function opening(): void
    {
        $id = $this->draft();
        $this->postJson("/inventory/stock/openings/{$id}/post")->assertOk();
    }

    private function issue(string $quantity = '5'): array
    {
        $id = strtolower((string) Str::ulid());

        return ['warehouse_id' => $this->warehouse, 'source_id' => $id, 'source_number' => 'POS-'.$id, 'lines' => [
            ['source_line_id' => strtolower((string) Str::ulid()), 'item_id' => $this->item, 'base_quantity' => $quantity],
        ]];
    }

    public function test_issue_persists_signed_movement_allocation_snapshot_and_actor_atomically(): void
    {
        $this->opening();
        $input = $this->issue();
        $result = app(IssueStock::class)->handle($input, $this->manager->id);
        $this->assertDatabaseHas('stock_balances', ['quantity' => '19.000000', 'total_value' => '190000.000000']);
        $this->assertDatabaseHas('fifo_layers', ['remaining_quantity' => '19.000000', 'remaining_value' => '190000.000000', 'original_quantity' => '24.000000']);
        $this->assertDatabaseHas('stock_movements', ['type' => 'sale', 'opening_line_id' => null, 'quantity' => '-5.000000', 'total_value' => '-50000.000000', 'source_number' => $input['source_number']]);
        $this->assertDatabaseHas('fifo_consumptions', ['quantity' => '5.000000', 'total_value' => '50000.000000']);
        $this->assertSame('50000.000000', $result['lines'][0]['total_value']);
        $this->assertSame($input['lines'][0]['source_line_id'], $result['lines'][0]['source_line_id']);
        $this->assertSame(1, Activity::where('event', 'issued')->where('subject_id', $result['id'])->where('causer_id', $this->manager->id)->count());
    }

    public function test_same_normalized_source_replays_but_changed_payload_cannot_double_issue(): void
    {
        $this->opening();
        $input = $this->issue();
        $first = app(IssueStock::class)->handle($input, $this->manager->id);
        $input['lines'][0]['base_quantity'] = '5.0';
        $this->assertSame($first, app(IssueStock::class)->handle($input, $this->manager->id));
        $this->assertDatabaseCount('stock_movements', 2);
        $this->assertDatabaseCount('fifo_consumptions', 1);
        $this->assertSame(1, Activity::where('event', 'issued')->count());
        $input['lines'][0]['base_quantity'] = '6';
        $this->expectException(InvalidArgumentException::class);
        app(IssueStock::class)->handle($input, $this->manager->id);
    }

    public function test_shortage_on_second_line_rolls_back_first_line_and_all_audit(): void
    {
        $this->opening();
        $input = $this->issue('20');
        $input['lines'][] = [...$input['lines'][0], 'source_line_id' => strtolower((string) Str::ulid()), 'base_quantity' => '5'];
        $before = Activity::count();
        try {
            app(IssueStock::class)->handle($input, $this->manager->id);
            $this->fail('Aggregate shortage must fail.');
        } catch (InvalidArgumentException) {
            $this->assertDatabaseHas('stock_balances', ['quantity' => '24.000000', 'total_value' => '240000.000000']);
            $this->assertDatabaseHas('fifo_layers', ['remaining_quantity' => '24.000000']);
            $this->assertDatabaseCount('stock_issues', 0);
            $this->assertDatabaseCount('stock_issue_lines', 0);
            $this->assertDatabaseCount('fifo_consumptions', 0);
            $this->assertDatabaseCount('stock_movements', 1);
            $this->assertSame($before, Activity::count());
        }
    }

    public function test_failed_audit_rolls_back_all_data_and_retry_is_safe(): void
    {
        $this->opening();
        $input = $this->issue();
        Activity::creating(static function (Activity $activity): void {
            if ($activity->event === 'issued') {
                throw new \RuntimeException('Simulated issue audit failure.');
            }
        });
        try {
            app(IssueStock::class)->handle($input, $this->manager->id);
            $this->fail('Audit must fail.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated issue audit failure.', $exception->getMessage());
            $this->assertDatabaseCount('stock_issues', 0);
            $this->assertDatabaseCount('fifo_consumptions', 0);
            $this->assertDatabaseHas('stock_balances', ['quantity' => '24.000000']);
        } finally {
            Activity::flushEventListeners();
        }
        $this->assertNotEmpty(app(IssueStock::class)->handle($input, $this->manager->id)['id']);
    }

    public function test_ledger_query_keeps_sale_and_legacy_opening_after_master_rename(): void
    {
        $this->opening();
        $input = $this->issue();
        app(IssueStock::class)->handle($input, $this->manager->id);
        DB::table('items')->where('id', $this->item)->update(['name' => 'Nama baru']);
        DB::table('warehouses')->where('id', $this->warehouse)->update(['name' => 'Gudang baru']);
        $this->getJson('/inventory/stock/data?tab=movements')->assertOk()->assertJsonPath('meta.total', 2);
        $this->getJson('/inventory/stock/data?tab=movements&search='.$input['source_number'])->assertOk()
            ->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.type', 'sale')
            ->assertJsonPath('data.0.quantity', '-5.000000')->assertJsonPath('data.0.number', $input['source_number'])
            ->assertJsonPath('data.0.item_name', 'Barang QA')->assertJsonPath('data.0.warehouse_name', 'Gudang QA');
        $this->postJson('/inventory/stock/issues', $input)->assertNotFound();
    }

    public function test_full_drain_keeps_zero_balance_and_refuses_migration_rollback(): void
    {
        $this->opening();
        app(IssueStock::class)->handle($this->issue('24'), $this->manager->id);
        $this->assertDatabaseHas('stock_balances', ['quantity' => '0.000000', 'total_value' => '0.000000']);
        $this->assertDatabaseHas('fifo_layers', ['remaining_quantity' => '0.000000', 'remaining_value' => '0.000000']);
        $migration = require app_path('Modules/Inventory/Stock/Database/Migrations/2026_10_16_000001_add_stock_issues.php');
        $this->expectException(\LogicException::class);
        $migration->down();
    }

    public function test_inconsistent_balance_and_layers_cannot_be_issued(): void
    {
        $this->opening();
        DB::table('stock_balances')->update(['total_value' => '1.000000']);
        try {
            app(IssueStock::class)->handle($this->issue(), $this->manager->id);
            $this->fail('Inconsistent inventory must fail.');
        } catch (InvalidArgumentException) {
            $this->assertDatabaseCount('stock_issues', 0);
            $this->assertDatabaseCount('stock_movements', 1);
        }
    }

    public function test_persistence_failure_after_writes_rolls_back_and_retry_is_safe(): void
    {
        $this->opening();
        $real = app(StockRepository::class);
        $repository = \Mockery::mock($real)->makePartial();
        $repository->shouldReceive('postIssueLine')->once()->andReturnUsing(function (...$args) use ($real): void {
            $real->postIssueLine(...$args);
            throw new \RuntimeException('Simulated issue persistence failure.');
        });
        $this->app->instance(StockRepository::class, $repository);
        try {
            app(IssueStock::class)->handle($this->issue(), $this->manager->id);
            $this->fail('Persistence must fail.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated issue persistence failure.', $exception->getMessage());
            $this->assertDatabaseCount('fifo_consumptions', 0);
            $this->assertDatabaseCount('stock_issues', 0);
            $this->assertDatabaseHas('stock_balances', ['quantity' => '24.000000']);
        } finally {
            $this->app->instance(StockRepository::class, $real);
        }
    }

    public function test_audit_veto_is_not_success_without_audit(): void
    {
        $this->opening();
        Activity::creating(static fn (Activity $activity) => $activity->event === 'issued' ? false : null);
        $rejected = false;
        try {
            app(IssueStock::class)->handle($this->issue(), $this->manager->id);
        } catch (\RuntimeException) {
            $rejected = true;
            $this->assertDatabaseCount('stock_issues', 0);
            $this->assertDatabaseHas('stock_balances', ['quantity' => '24.000000']);
        } finally {
            Activity::flushEventListeners();
        }
        $this->assertTrue($rejected);
    }

    public function test_other_warehouse_stock_is_untouched_and_line_sources_cannot_be_reused(): void
    {
        $this->opening();
        $other = Warehouse::create(['code' => 'OTHER', 'name' => 'Gudang lain', 'is_active' => true])->id;
        $payload = $this->payload();
        $payload['warehouse_id'] = $other;
        $opening = $this->postJson('/inventory/stock/openings', $payload)->assertCreated()->json('id');
        $this->postJson("/inventory/stock/openings/{$opening}/post")->assertOk();
        $input = $this->issue();
        app(IssueStock::class)->handle($input, $this->manager->id);
        $this->assertDatabaseHas('stock_balances', ['warehouse_id' => $other, 'quantity' => '24.000000']);
        $input['source_id'] = strtolower((string) Str::ulid());
        $input['warehouse_id'] = $other;
        try {
            app(IssueStock::class)->handle($input, $this->manager->id);
            $this->fail('Reusing source line must fail.');
        } catch (InvalidArgumentException) {
            $this->assertDatabaseCount('stock_issues', 1);
            $this->assertDatabaseHas('stock_balances', ['warehouse_id' => $other, 'quantity' => '24.000000']);
        }
    }

    public function test_persisted_thirds_drain_all_residual_without_changing_original_layer(): void
    {
        $payload = $this->payload();
        $payload['lines'][0]['quantity'] = '0.25';
        $payload['lines'][0]['unit_cost'] = '4';
        $id = $this->postJson('/inventory/stock/openings', $payload)->assertCreated()->json('id');
        $this->postJson("/inventory/stock/openings/{$id}/post")->assertOk();
        $values = [];
        foreach (range(1, 3) as $step) {
            $values[] = app(IssueStock::class)->handle($this->issue('1'), $this->manager->id)['lines'][0]['total_value'];
        }
        $this->assertSame(['0.333333', '0.333333', '0.333334'], $values);
        $this->assertDatabaseHas('fifo_layers', ['original_quantity' => '3.000000', 'original_value' => '1.000000', 'remaining_value' => '0.000000']);
        $this->assertDatabaseHas('stock_balances', ['quantity' => '0.000000', 'total_value' => '0.000000']);
    }

    public static function layerOrders(): array
    {
        return [[false, '35.000000'], [true, '40.000000']];
    }

    #[DataProvider('layerOrders')]
    public function test_sql_layer_order_is_received_time_then_id(bool $tied, string $expected): void
    {
        $payload = $this->payload();
        $payload['lines'] = [['item_id' => $this->item, 'unit_id' => $this->base, 'quantity' => '2', 'unit_cost' => '10']];
        $id = $this->postJson('/inventory/stock/openings', $payload)->assertCreated()->json('id');
        $this->postJson("/inventory/stock/openings/{$id}/post")->assertOk();
        DB::table('fifo_layers')->update(['received_at' => '2026-01-01 00:00:00']);
        $movement = (string) Str::ulid();
        DB::table('stock_movements')->insert(['id' => $movement, 'warehouse_id' => $this->warehouse, 'item_id' => $this->item,
            'type' => 'opening', 'quantity' => '2.000000', 'total_value' => '30.000000', 'posted_at' => '2026-01-01 00:00:00']);
        DB::table('fifo_layers')->insert(['id' => '00000000000000000000000001', 'movement_id' => $movement,
            'warehouse_id' => $this->warehouse, 'item_id' => $this->item, 'original_quantity' => '2.000000', 'remaining_quantity' => '2.000000',
            'original_value' => '30.000000', 'remaining_value' => '30.000000', 'received_at' => $tied ? '2026-01-01 00:00:00' : '2026-01-02 00:00:00']);
        DB::table('stock_balances')->update(['quantity' => '4.000000', 'total_value' => '50.000000']);
        $this->assertSame($expected, app(IssueStock::class)->handle($this->issue('3'), $this->manager->id)['lines'][0]['total_value']);
        $this->assertDatabaseCount('fifo_consumptions', 2);
    }

    public function test_invalid_quantity_duplicate_line_and_unavailable_master_fail_without_issue(): void
    {
        $this->opening();
        foreach (['0', '-1', '0.0000001', '1,5', '1e1', 1.5] as $quantity) {
            $input = $this->issue();
            $input['lines'][0]['base_quantity'] = $quantity;
            try {
                app(IssueStock::class)->handle($input, $this->manager->id);
                $this->fail('Invalid quantity must fail.');
            } catch (InvalidArgumentException|ValidationException) {
                $this->assertDatabaseCount('stock_issues', 0);
            }
        }
        foreach (['warehouses' => $this->warehouse, 'items' => $this->item, 'units' => $this->base] as $table => $id) {
            DB::table($table)->where('id', $id)->update(['is_active' => false]);
            try {
                app(IssueStock::class)->handle($this->issue(), $this->manager->id);
                $this->fail('Inactive master must fail.');
            } catch (InvalidArgumentException) {
                $this->assertDatabaseCount('stock_issues', 0);
            } finally {
                DB::table($table)->where('id', $id)->update(['is_active' => true]);
            }
        }
        $input = $this->issue();
        $input['lines'][] = $input['lines'][0];
        $this->expectException(InvalidArgumentException::class);
        app(IssueStock::class)->handle($input, $this->manager->id);
    }
}
