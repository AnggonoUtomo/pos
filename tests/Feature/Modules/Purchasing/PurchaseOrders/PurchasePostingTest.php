<?php

namespace Tests\Feature\Modules\Purchasing\PurchaseOrders;

use App\Models\User;
use App\Modules\Purchasing\PurchaseOrders\Application\Actions\ManagePurchaseOrder;
use App\Modules\Purchasing\PurchaseOrders\Application\Actions\PostPurchaseOrder;
use App\Modules\Purchasing\Suppliers\Infrastructure\Persistence\Models\Supplier;
use Illuminate\Support\Facades\DB;
use LogicException;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Feature\Modules\Inventory\Stock\StockTestCase;

class PurchasePostingTest extends StockTestCase
{
    private string $supplier;

    private const URL = '/purchasing/purchase-orders/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->supplier = Supplier::create(['code' => 'POST-SUP', 'name' => 'Supplier CCTV', 'is_active' => true])->id;
        foreach (['view', 'manage', 'post'] as $key) {
            $this->manager->givePermissionTo(Permission::findOrCreate('purchasing.purchase-orders.'.$key, 'web'));
        }
    }

    private function order(): string
    {
        return app(ManagePurchaseOrder::class)->save($this->payload() + ['supplier_id' => $this->supplier]);
    }

    public function test_full_receipt_is_atomic_idempotent_readonly_and_finance_unchanged(): void
    {
        $id = $this->order();
        $data = $this->postJson(self::URL.$id.'/post', ['version' => 1])->assertOk()->assertJsonPath('status', 'posted')->assertJsonPath('version', 2)->json();
        $this->assertDatabaseHas('purchase_orders', ['id' => $id, 'posted_from_version' => 1, 'posted_by' => $this->manager->id, 'stock_receipt_id' => $data['stock_receipt_id']]);
        $this->assertDatabaseHas('stock_balances', ['quantity' => '24.000000', 'total_value' => '240000.000000']);
        $this->assertDatabaseCount('stock_receipt_lines', 1);
        $this->assertDatabaseCount('fifo_layers', 1);
        $logs = Activity::count();
        DB::table('suppliers')->where('id', $this->supplier)->update(['is_active' => false]);
        $this->postJson(self::URL.$id.'/post', ['version' => 1])->assertOk()->assertExactJson($data);
        $this->assertSame($logs, Activity::count());
        $this->assertDatabaseCount('stock_movements', 1);
        $this->postJson(self::URL.$id.'/post', ['version' => 2])->assertUnprocessable();
        $input = $this->payload() + ['supplier_id' => $this->supplier, 'version' => 2];
        $this->putJson(self::URL.$id, $input)->assertUnprocessable();
        $this->deleteJson(self::URL.$id, ['version' => 2])->assertUnprocessable();
        $this->postJson(self::URL.$id.'/restore', ['version' => 2])->assertNotFound();
        $this->getJson(self::URL.'data?status=posted')->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.lines.0.base_quantity', '24.000000');
        $this->getJson(self::URL.'data?status=draft')->assertJsonPath('meta.total', 0);
        foreach (['payment_receipts', 'sales_invoices'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
    }

    public function test_stale_ratio_deleted_and_inactive_master_cannot_post(): void
    {
        $id = $this->order();
        $this->postJson(self::URL.$id.'/post', ['version' => 0])->assertUnprocessable();
        DB::table('item_units')->where('item_id', $this->item)->where('unit_id', $this->box)->update(['conversion_ratio' => '10']);
        $this->postJson(self::URL.$id.'/post', ['version' => 1])->assertUnprocessable()->assertJsonValidationErrors('purchase_order');
        $input = $this->payload() + ['supplier_id' => $this->supplier, 'version' => 1];
        app(ManagePurchaseOrder::class)->save($input, $id);
        $this->postJson(self::URL.$id.'/post', ['version' => 1])->assertUnprocessable();
        DB::table('suppliers')->where('id', $this->supplier)->update(['is_active' => false]);
        $this->postJson(self::URL.$id.'/post', ['version' => 2])->assertUnprocessable()->assertJsonValidationErrors('supplier_id');
        DB::table('suppliers')->where('id', $this->supplier)->update(['is_active' => true]);
        app(ManagePurchaseOrder::class)->delete($id, 2);
        $this->postJson(self::URL.$id.'/post', ['version' => 3])->assertNotFound();
        $this->assertDatabaseCount('stock_receipts', 0);
        $this->assertDatabaseCount('stock_balances', 0);
    }

    public function test_either_audit_failure_rolls_back_both_modules_and_retry_is_safe(): void
    {
        $id = $this->order();
        foreach (['inventory.stock', 'purchasing.purchase-orders'] as $name) {
            Activity::creating(fn ($log) => $log->log_name === $name && in_array($log->event, ['received', 'posted'], true) ? false : null);
            try {
                app(PostPurchaseOrder::class)->handle($id, 1, $this->manager->id);
                $this->fail('Audit veto harus rollback.');
            } catch (RuntimeException) {
                $this->assertDatabaseHas('purchase_orders', ['id' => $id, 'status' => 'draft', 'version' => 1, 'stock_receipt_id' => null]);
                foreach (['stock_receipts', 'stock_receipt_lines', 'stock_balances', 'stock_movements', 'fifo_layers'] as $table) {
                    $this->assertDatabaseCount($table, 0);
                }
            } finally {
                Activity::flushEventListeners();
            }
        }
        $this->postJson(self::URL.$id.'/post', ['version' => 1])->assertOk();
        $this->assertSame(1, Activity::where('event', 'received')->count());
        $this->assertSame(1, Activity::where('event', 'posted')->where('log_name', 'purchasing.purchase-orders')->count());
    }

    public function test_post_permission_payload_whitelist_csrf_and_super_invariants(): void
    {
        $id = $this->order();
        foreach ([['view'], ['manage'], ['view', 'manage'], []] as $permissions) {
            $user = User::factory()->create();
            foreach ($permissions as $key) {
                $user->givePermissionTo('purchasing.purchase-orders.'.$key);
            }
            $this->actingAs($user);
            $this->postJson(self::URL.$id.'/post', ['version' => 1])->assertForbidden();
        }
        $user = User::factory()->create();
        $user->givePermissionTo('purchasing.purchase-orders.post');
        $this->actingAs($user);
        $this->getJson(self::URL.'data')->assertForbidden();
        $this->postJson(self::URL.$id.'/post', ['version' => 1, 'lines' => [], 'quantity' => '999'])->assertUnprocessable();
        $this->app->detectEnvironment(fn () => 'local');
        $this->postJson(self::URL.$id.'/post', ['version' => 1])->assertStatus(419);
        $this->app->detectEnvironment(fn () => 'testing');
        $this->postJson(self::URL.$id.'/post', ['version' => 1])->assertOk();
        $user->assignRole(Role::findOrCreate('super-system', 'web'));
        $this->deleteJson(self::URL.$id, ['version' => 2])->assertUnprocessable();
        auth()->logout();
        $this->postJson(self::URL.$id.'/post', ['version' => 1])->assertUnauthorized();
    }

    public function test_posted_snapshot_survives_rename_and_migration_down_is_blocked(): void
    {
        $id = $this->order();
        DB::table('items')->where('id', $this->item)->update(['name' => 'Nama sebelum posting']);
        $this->postJson(self::URL.$id.'/post', ['version' => 1])->assertOk();
        DB::table('items')->where('id', $this->item)->update(['name' => 'Nama sesudah posting']);
        $this->getJson(self::URL.'data')->assertJsonPath('data.0.lines.0.item_name', 'Nama sebelum posting');
        $migration = require app_path('Modules/Purchasing/PurchaseOrders/Database/Migrations/2026_10_17_000004_add_purchase_posting.php');
        $this->expectException(LogicException::class);
        $migration->down();
    }
}
