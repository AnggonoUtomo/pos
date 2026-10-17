<?php

namespace Tests\Feature\Modules\Purchasing\PurchaseOrders;

use App\Models\User;
use App\Modules\Finance\Payments\Database\Seeders\PaymentsDemoSeeder;
use App\Modules\Purchasing\PurchaseOrders\Application\Actions\ManagePurchaseOrder;
use App\Modules\Purchasing\PurchaseOrders\Application\Actions\PayPurchaseOrder;
use App\Modules\Purchasing\PurchaseOrders\Application\Actions\PostPurchaseOrder;
use App\Modules\Purchasing\Suppliers\Infrastructure\Persistence\Models\Supplier;
use Illuminate\Support\Facades\DB;
use LogicException;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Feature\Modules\Inventory\Stock\StockTestCase;

class PurchasePaymentTest extends StockTestCase
{
    private string $supplier;

    private array $payments;

    private const URL = '/purchasing/purchase-orders/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PaymentsDemoSeeder::class);
        $this->supplier = Supplier::create(['code' => 'PAY-SUP', 'name' => 'Supplier CCTV', 'is_active' => true])->id;
        foreach (['view', 'manage', 'post', 'pay'] as $key) {
            $this->manager->givePermissionTo(Permission::findOrCreate('purchasing.purchase-orders.'.$key, 'web'));
        }
        $methods = DB::table('payment_methods')->pluck('id', 'code');
        $this->payments = [['method_id' => $methods['TUNAI'], 'amount' => '40000'], ['method_id' => $methods['TRANSFER'], 'amount' => '200000']];
    }

    private function order(bool $post = true): string
    {
        $id = app(ManagePurchaseOrder::class)->save($this->payload() + ['supplier_id' => $this->supplier]);
        if ($post) {
            app(PostPurchaseOrder::class)->handle($id, 1);
        }

        return $id;
    }

    private function stock(): array
    {
        return array_map(fn ($table) => DB::table($table)->orderBy('id')->get()->toJson(), ['stock_receipts', 'stock_receipt_lines', 'stock_movements', 'stock_balances', 'fifo_layers']);
    }

    public function test_payment_atomic_replay_snapshot_filters_and_stock_unchanged(): void
    {
        $id = $this->order();
        $before = $this->stock();
        $this->getJson(self::URL.'data?payment_status=unrecorded')->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.payment_disbursement_id', null);
        DB::table('suppliers')->update(['is_active' => false]);
        $payload = ['version' => 2, 'payments' => $this->payments];
        $result = $this->postJson(self::URL.$id.'/pay', $payload)->assertOk()->assertJsonPath('version', 3)->assertJsonPath('payment_snapshot.total', '240000.000000')->json();
        $this->assertDatabaseHas('purchase_orders', ['id' => $id, 'status' => 'posted', 'paid_from_version' => 2, 'paid_by' => $this->manager->id]);
        $this->assertSame($before, $this->stock());
        $this->assertDatabaseCount('payment_receipts', 0);
        $this->assertDatabaseCount('payment_disbursement_lines', 2);
        $logs = Activity::count();
        // MySQL may reorder object keys in JSON; semantic snapshot equality must survive it.
        $snapshot = $result['payment_snapshot'];
        $snapshot['payments'] = array_map(fn ($row) => array_reverse($row, true), $snapshot['payments']);
        DB::table('purchase_orders')->where('id', $id)->update(['payment_snapshot' => json_encode(array_reverse($snapshot, true), JSON_THROW_ON_ERROR)]);
        DB::table('payment_methods')->update(['name' => 'Berubah', 'is_active' => false]);
        $payload['payments'] = array_reverse($this->payments);
        $this->postJson(self::URL.$id.'/pay', $payload)->assertOk()->assertExactJson($result);
        $this->assertSame($logs, Activity::count());
        $this->getJson(self::URL.'data?payment_status=paid')->assertJsonPath('meta.total', 1);
        $this->getJson(self::URL.'data?payment_status=unrecorded')->assertJsonPath('meta.total', 0);
        $this->postJson(self::URL.$id.'/pay', ['version' => 3, 'payments' => $this->payments])->assertUnprocessable();
        $changed = $this->payments;
        $changed[0]['amount'] = '50000';
        $changed[1]['amount'] = '190000';
        $this->postJson(self::URL.$id.'/pay', ['version' => 2, 'payments' => $changed])->assertUnprocessable();
        $this->assertSame($before, $this->stock());
    }

    public function test_draft_stale_payload_and_super_invariants(): void
    {
        $this->manager->assignRole(Role::findOrCreate('super-system', 'web'));
        $id = $this->order(false);
        $this->postJson(self::URL.$id.'/pay', ['version' => 1, 'payments' => $this->payments])->assertUnprocessable();
        app(PostPurchaseOrder::class)->handle($id, 1);
        foreach ([['version' => 1, 'payments' => $this->payments], ['version' => 2, 'payments' => $this->payments, 'total' => '1'], ['version' => 2, 'payments' => [['method_id' => $this->payments[0]['method_id'], 'amount' => '1']]]] as $payload) {
            $this->postJson(self::URL.$id.'/pay', $payload)->assertUnprocessable();
            $this->assertDatabaseCount('payment_disbursements', 0);
        }
        $deleted = $this->order(false);
        app(ManagePurchaseOrder::class)->delete($deleted, 1);
        $this->postJson(self::URL.$deleted.'/pay', ['version' => 2, 'payments' => $this->payments])->assertNotFound();
    }

    public function test_either_audit_veto_rolls_back_finance_and_purchase_without_touching_stock(): void
    {
        $id = $this->order();
        $before = $this->stock();
        foreach (['finance.payments', 'purchasing.purchase-orders'] as $name) {
            Activity::creating(fn ($log) => $log->log_name === $name && in_array($log->event, ['disbursed', 'paid'], true) ? false : null);
            try {
                app(PayPurchaseOrder::class)->handle($id, 2, $this->payments, $this->manager->id);
                $this->fail('Audit harus atomic.');
            } catch (RuntimeException) {
                $this->assertDatabaseHas('purchase_orders', ['id' => $id, 'version' => 2, 'payment_disbursement_id' => null]);
                $this->assertDatabaseCount('payment_disbursements', 0);
                $this->assertDatabaseCount('payment_disbursement_lines', 0);
                $this->assertSame($before, $this->stock());
            } finally {
                Activity::flushEventListeners();
            }
        }
        $this->postJson(self::URL.$id.'/pay', ['version' => 2, 'payments' => $this->payments])->assertOk();
    }

    public function test_permission_is_separate_and_csrf_is_enforced(): void
    {
        $id = $this->order();
        foreach (['view', 'manage', 'post'] as $key) {
            $user = User::factory()->create();
            $user->givePermissionTo('purchasing.purchase-orders.'.$key);
            $this->actingAs($user)->postJson(self::URL.$id.'/pay', ['version' => 2, 'payments' => $this->payments])->assertForbidden();
        }
        $payer = User::factory()->create();
        $payer->givePermissionTo('purchasing.purchase-orders.pay');
        $this->actingAs($payer)->getJson(self::URL.'data')->assertForbidden();
        $this->app->detectEnvironment(fn () => 'local');
        $this->postJson(self::URL.$id.'/pay', ['version' => 2, 'payments' => $this->payments])->assertStatus(419);
        $this->app->detectEnvironment(fn () => 'testing');
        $this->postJson(self::URL.$id.'/pay', ['version' => 2, 'payments' => $this->payments])->assertOk();
    }

    public function test_posted_payment_migration_down_is_guarded(): void
    {
        $id = $this->order();
        app(PayPurchaseOrder::class)->handle($id, 2, $this->payments);
        $migration = require app_path('Modules/Purchasing/PurchaseOrders/Database/Migrations/2026_10_17_000006_add_purchase_payment.php');
        $this->expectException(LogicException::class);
        $migration->down();
    }
}
