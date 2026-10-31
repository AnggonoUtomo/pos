<?php

namespace Tests\Feature\Modules\Inventory\Stock;

use App\Models\User;
use App\Modules\Inventory\Stock\Application\Actions\ManageStockCount;
use App\Modules\Inventory\Stock\Application\Actions\PostAdjustment;
use App\Modules\Inventory\Stock\Application\Actions\PostStockCount;
use App\Modules\Inventory\Stock\Application\Contracts\StockCountRepository;
use App\Modules\Inventory\Stock\Application\Contracts\StockReceiptRepository;
use App\Modules\Inventory\Stock\Application\Contracts\StockRepository;
use App\Modules\Inventory\Stock\Application\Contracts\WarehouseStockAccess;
use App\Modules\Inventory\Stock\Database\Seeders\StockAccessSeeder;
use App\Modules\Inventory\Warehouses\Infrastructure\Persistence\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;

class StockCountTest extends StockTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(StockAccessSeeder::class);
        $this->manager->givePermissionTo(Permission::all());
    }

    private function startWithStock(): array
    {
        $this->postJson('/inventory/stock/openings/'.$this->draft().'/post')->assertOk();
        $id = app(ManageStockCount::class)->start($this->warehouse, 'Opname CCTV', $this->manager->id);

        return app(StockCountRepository::class)->find($id);
    }

    public function test_active_count_freezes_adjustments_but_cancellation_releases_warehouse(): void
    {
        $warehouse = $this->warehouse;
        $item = $this->item;
        $count = app(ManageStockCount::class)->start($warehouse, 'Hitung fisik toko', 1);
        $input = ['idempotency_key' => strtolower((string) Str::ulid()), 'warehouse_id' => $warehouse,
            'item_id' => $item, 'direction' => 'in', 'quantity' => '1', 'unit_cost' => '100', 'reason' => 'QA'];
        try {
            app(PostAdjustment::class)->handle($input, 1);
            $this->fail('Gudang opname harus tertahan.');
        } catch (ValidationException $error) {
            $this->assertStringContainsString('opname', $error->getMessage());
        }
        $this->assertSame(0, DB::table('stock_adjustments')->count());
        app(ManageStockCount::class)->cancel($count, 0, 'Dibatalkan untuk hitung ulang', 1);
        app(PostAdjustment::class)->handle($input, 1);
        $this->assertSame(1, DB::table('stock_adjustments')->count());
    }

    #[DataProvider('physicalCounts')]
    public function test_posting_difference_is_atomic_exact_and_replay_safe(string $quantity, int $adjustments): void
    {
        $record = $this->startWithStock();
        $action = app(ManageStockCount::class);
        $line = $record['lines'][0];
        $action->save($record['id'], 0, [['id' => $line['id'], 'quantity' => $quantity]], $this->manager->id);
        $action->submit($record['id'], 1, $this->manager->id);
        $costs = $quantity === '25' ? [['id' => $line['id'], 'unit_cost' => '10000']] : [];
        $post = app(PostStockCount::class);
        $post->handle($record['id'], 2, 'Disetujui hitung fisik', $costs, $this->manager->id);
        $post->handle($record['id'], 2, 'Disetujui hitung fisik', $costs, $this->manager->id);
        $this->assertDatabaseCount('stock_adjustments', $adjustments);
        $this->assertDatabaseCount('stock_movements', 1 + $adjustments);
        $this->assertSame($quantity.'.000000', DB::table('stock_balances')->value('quantity'));
        $this->assertEquals((int) $quantity * 10000, DB::table('stock_balances')->value('total_value'));
        $this->assertDatabaseHas('stock_counts', ['id' => $record['id'], 'status' => 'posted', 'active_warehouse_id' => null]);
        $this->assertDatabaseHas('activity_log', ['event' => 'count.posted']);
        DB::transaction(fn () => app(WarehouseStockAccess::class)->assertWritable($this->warehouse));
    }

    public static function physicalCounts(): array
    {
        return [['23', 1], ['25', 1], ['24', 0], ['0', 1]];
    }

    public function test_counting_empty_stale_review_and_duplicate_session_are_rejected(): void
    {
        $record = $this->startWithStock();
        $id = $record['id'];
        $line = $record['lines'][0]['id'];
        $this->postJson('/inventory/stock/counts/'.$id.'/submit', ['version' => 0])->assertUnprocessable();
        $this->postJson('/inventory/stock/counts', ['warehouse_id' => $this->warehouse, 'notes' => 'Lagi', 'operation_key' => (string) Str::ulid()])->assertUnprocessable();
        $this->postJson('/inventory/stock/counts/'.$id.'/save', ['version' => 0, 'lines' => [['id' => $line, 'quantity' => '1,5']]])->assertUnprocessable();
        $this->postJson('/inventory/stock/counts/'.$id.'/save', ['version' => 0, 'lines' => [['id' => $line, 'quantity' => '25']]])->assertOk();
        $this->postJson('/inventory/stock/counts/'.$id.'/submit', ['version' => 0])->assertUnprocessable();
        $this->postJson('/inventory/stock/counts/'.$id.'/submit', ['version' => 1])->assertOk();
        $this->postJson('/inventory/stock/counts/'.$id.'/save', ['version' => 2, 'lines' => [['id' => $line, 'quantity' => '24']]])->assertUnprocessable();
        $this->postJson('/inventory/stock/counts/'.$id.'/post', ['version' => 2, 'reason' => 'QA', 'costs' => []])->assertUnprocessable();
        $this->assertDatabaseCount('stock_adjustments', 0);
        $this->postJson('/inventory/stock/counts/'.$id.'/reopen', ['version' => 2, 'reason' => 'Hitung ulang'])->assertOk();
        $this->assertDatabaseHas('stock_counts', ['status' => 'counting', 'version' => 3]);
    }

    public function test_audit_failure_rolls_back_posting_and_keeps_freeze(): void
    {
        $record = $this->startWithStock();
        $action = app(ManageStockCount::class);
        $action->save($record['id'], 0, [['id' => $record['lines'][0]['id'], 'quantity' => '23']], $this->manager->id);
        $action->submit($record['id'], 1, $this->manager->id);
        Activity::creating(fn ($log) => $log->event === 'count.posted' ? false : null);
        try {
            app(PostStockCount::class)->handle($record['id'], 2, 'QA', [], $this->manager->id);
            $this->fail('Audit gagal wajib rollback.');
        } catch (\RuntimeException $error) {
            $this->assertStringContainsString('Audit', $error->getMessage());
        }
        $this->assertDatabaseCount('stock_adjustments', 0);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertEquals(24, DB::table('stock_balances')->value('quantity'));
        $this->assertDatabaseHas('stock_counts', ['status' => 'review', 'active_warehouse_id' => $this->warehouse]);
    }

    public function test_all_common_ledger_gates_reject_freeze_even_with_forged_context(): void
    {
        $record = $this->startWithStock();
        foreach (['issue', 'receipt', 'opening'] as $kind) {
            try {
                DB::transaction(function () use ($kind, $record): void {
                    $header = ['warehouse_id' => $this->warehouse, 'stock_count_id' => $record['id']];
                    if ($kind === 'issue') {
                        app(StockRepository::class)->createIssue($header);
                    } elseif ($kind === 'receipt') {
                        app(StockReceiptRepository::class)->create($header);
                    } else {
                        app(StockRepository::class)->postOpening('invalid', $header, [], $this->manager->id);
                    }
                });
                $this->fail('Ledger '.$kind.' harus tertahan.');
            } catch (InvalidArgumentException $error) {
                $this->assertStringContainsString('opname', $error->getMessage());
            }
        }
    }

    public function test_view_manage_post_permissions_are_independent_and_readonly(): void
    {
        $record = $this->startWithStock();
        $user = User::factory()->create()->givePermissionTo('inventory.stock.counts.view');
        $before = DB::table('activity_log')->count();
        $this->actingAs($user)->get('/inventory/stock/counts')->assertOk();
        $this->get('/inventory/stock/counts/'.$record['id'])->assertOk();
        foreach (['save', 'add', 'submit', 'cancel', 'post', 'reopen'] as $method) {
            $this->postJson('/inventory/stock/counts/'.$record['id'].'/'.$method, [])->assertForbidden();
        }
        $this->assertSame($before, DB::table('activity_log')->count());
        $this->actingAs(User::factory()->create())->get('/inventory/stock/counts')->assertForbidden();
    }

    public function test_other_warehouse_stays_writable_and_extra_zero_balance_item_can_be_counted(): void
    {
        $count = app(ManageStockCount::class)->start($this->warehouse, 'QA kosong', $this->manager->id);
        app(ManageStockCount::class)->add($count, 0, $this->item, $this->manager->id);
        $record = app(StockCountRepository::class)->find($count);
        $this->assertSame('0.000000', $record['lines'][0]['system_quantity']);
        $this->assertNull($record['lines'][0]['counted_quantity']);
        $other = Warehouse::create([
            'code' => 'OTHER', 'name' => 'Gudang lain', 'is_active' => true,
        ]);
        app(PostAdjustment::class)->handle(['idempotency_key' => (string) Str::ulid(), 'warehouse_id' => $other->id,
            'item_id' => $this->item, 'direction' => 'in', 'quantity' => '1', 'unit_cost' => '100', 'reason' => 'Gudang lain'], $this->manager->id);
        $this->assertDatabaseCount('stock_adjustments', 1);
        $this->assertDatabaseHas('stock_counts', ['id' => $count, 'status' => 'counting']);
        $this->postJson('/inventory/stock/counts/'.$count.'/submit', ['version' => 1])->assertUnprocessable();
        $this->postJson('/inventory/stock/counts/'.$count.'/save', ['version' => 1, 'lines' => [['id' => (string) Str::ulid(), 'quantity' => '0']]])->assertUnprocessable();
    }
}
