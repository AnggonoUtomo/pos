<?php

namespace Tests\Feature\Modules\Purchasing\PurchaseOrders;

use App\Models\User;
use App\Modules\Inventory\Items\Database\Seeders\ItemsDemoSeeder;
use App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder;
use App\Modules\Inventory\Warehouses\Database\Seeders\WarehousesDemoSeeder;
use App\Modules\Purchasing\PurchaseOrders\Application\Actions\ManagePurchaseOrder;
use App\Modules\Purchasing\PurchaseOrders\Database\Seeders\PurchaseOrdersAccessSeeder;
use App\Modules\Purchasing\PurchaseOrders\Database\Seeders\PurchaseOrdersDemoSeeder;
use App\Modules\Purchasing\Suppliers\Database\Seeders\SuppliersDemoSeeder;
use App\Modules\Purchasing\Suppliers\Infrastructure\Persistence\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use LogicException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Feature\Modules\Inventory\Stock\StockTestCase;

class PurchaseOrdersHttpTest extends StockTestCase
{
    private const URL = '/purchasing/purchase-orders';

    private string $supplier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->supplier = Supplier::create(['code' => 'HTTP-SUP', 'name' => 'Supplier 100%_!', 'phone' => 'private-contact', 'is_active' => true])->id;
        foreach (['view', 'manage'] as $key) {
            $this->manager->givePermissionTo(Permission::findOrCreate('purchasing.purchase-orders.'.$key, 'web'));
        }
    }

    protected function payload(): array
    {
        return parent::payload() + ['supplier_id' => $this->supplier];
    }

    public function test_crud_stale_whitelist_restore_and_readonly_snapshot(): void
    {
        $input = $this->payload();
        $input['id'] = (string) Str::ulid();
        $input['number'] = 'UNTRUSTED';
        $input['status'] = 'posted';
        $input['total_value'] = '1';
        $input['demo_key'] = 'UNTRUSTED';
        $input['lines'][0]['conversion_ratio'] = '100';
        $input['lines'][0]['total_value'] = '1';
        $id = $this->postJson(self::URL, $input)->assertCreated()->json('id');
        $this->assertNotSame($input['id'], $id);
        $this->assertDatabaseHas('purchase_orders', ['id' => $id, 'status' => 'draft', 'demo_key' => null, 'version' => 1]);
        $data = $this->getJson(self::URL.'/data')->assertOk()->json('data.0');
        $this->assertSame('240000.000000', $data['total_value']);
        $this->assertSame('12.000000', $data['lines'][0]['conversion_ratio']);
        $this->putJson(self::URL.'/'.strtoupper($id), $input)->assertUnprocessable()->assertJsonValidationErrors('version');
        $input['version'] = 1;
        $input['lines'][0]['quantity'] = '3';
        $this->putJson(self::URL.'/'.$id, $input)->assertOk();
        $this->putJson(self::URL.'/'.$id, $input)->assertUnprocessable()->assertJsonValidationErrors('purchase_order');
        $this->deleteJson(self::URL.'/'.$id, ['version' => 1])->assertUnprocessable();
        $this->deleteJson(self::URL.'/'.$id, ['version' => 2])->assertOk();
        $this->getJson(self::URL.'/data')->assertJsonPath('meta.total', 0);
        $this->getJson(self::URL.'/data?trashed=1')->assertJsonPath('data.0.version', 3);
        DB::table('suppliers')->where('id', $this->supplier)->update(['is_active' => false, 'name' => 'Renamed']);
        $this->postJson(self::URL.'/'.$id.'/restore', ['version' => 3])->assertOk();
        $logs = DB::table('activity_log')->count();
        $this->getJson(self::URL.'/data')->assertJsonPath('data.0.supplier_name', 'Supplier 100%_!')->assertJsonPath('data.0.version', 4);
        $this->get(self::URL)->assertInertia(fn (Assert $page) => $page->component('purchasing/purchase-orders/index')->has('records.data', 1));
        $this->assertSame($logs, DB::table('activity_log')->count());
        $input['version'] = 4;
        $this->putJson(self::URL.'/'.$id, $input)->assertUnprocessable()->assertJsonValidationErrors('supplier_id');
        $this->deleteJson(self::URL.'/invalid', ['version' => 4])->assertNotFound();
        $this->deleteJson(self::URL.'/'.strtolower((string) Str::ulid()), ['version' => 1])->assertNotFound();
        $this->postJson(self::URL.'/'.$id.'/post')->assertNotFound();
    }

    public function test_permission_matrix_csrf_and_super_system_invariant(): void
    {
        $id = $this->postJson(self::URL, $this->payload())->assertCreated()->json('id');
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('purchasing.purchase-orders.view');
        $this->actingAs($viewer);
        $this->getJson(self::URL.'/data')->assertOk();
        $this->postJson(self::URL, $this->payload())->assertForbidden();
        $this->putJson(self::URL.'/'.$id, $this->payload())->assertForbidden();
        $this->deleteJson(self::URL.'/'.$id, ['version' => 1])->assertForbidden();
        $this->postJson(self::URL.'/'.$id.'/restore', ['version' => 1])->assertForbidden();
        $outsider = User::factory()->create();
        $this->actingAs($outsider);
        $this->getJson(self::URL.'/data')->assertForbidden();
        $outsider->givePermissionTo('purchasing.purchase-orders.manage');
        $this->postJson(self::URL, $this->payload())->assertCreated();
        $this->getJson(self::URL.'/data')->assertForbidden();
        $outsider->assignRole(Role::findOrCreate('super-system', 'web'));
        $this->getJson(self::URL.'/data')->assertOk();
        $input = $this->payload();
        $input['lines'][0]['unit_cost'] = '0';
        $this->postJson(self::URL, $input)->assertUnprocessable();
        DB::table('purchase_orders')->where('id', $id)->update(['status' => 'posted']);
        $this->deleteJson(self::URL.'/'.$id, ['version' => 1])->assertUnprocessable()->assertJsonValidationErrors('purchase_order');
        $this->app->detectEnvironment(fn () => 'local');
        $this->postJson(self::URL, [])->assertStatus(419);
        $this->putJson(self::URL.'/'.$id, [])->assertStatus(419);
        $this->deleteJson(self::URL.'/'.$id, [])->assertStatus(419);
        $this->postJson(self::URL.'/'.$id.'/restore', [])->assertStatus(419);
        $this->app->detectEnvironment(fn () => 'testing');
        auth()->logout();
        $this->get(self::URL)->assertRedirect('/login');
        $this->postJson(self::URL, [])->assertUnauthorized();
    }

    public function test_filter_pagination_literal_search_and_validation(): void
    {
        for ($i = 0; $i < 26; $i++) {
            app(ManagePurchaseOrder::class)->save($this->payload());
        }
        $this->getJson(self::URL.'/data')->assertJsonCount(25, 'data')->assertJsonPath('meta.total', 26);
        $this->getJson(self::URL.'/data?page=2')->assertJsonCount(1, 'data');
        $this->getJson(self::URL.'/data?search='.urlencode('%_!'))->assertJsonPath('meta.total', 26);
        $this->getJson(self::URL.'/data?supplier_id='.$this->supplier.'&warehouse_id='.$this->warehouse)->assertJsonPath('meta.total', 26);
        $this->getJson(self::URL.'/data?supplier_id='.(string) Str::ulid())->assertJsonPath('meta.total', 0);
        $this->getJson(self::URL.'/data?page=0&trashed=invalid&warehouse_id=invalid&supplier_id=invalid')->assertUnprocessable();
        $this->getJson(self::URL.'/data?search='.str_repeat('x', 151))->assertUnprocessable();
        $input = $this->payload();
        $input['lines'][0]['item_id'] = strtoupper($this->item);
        $input['supplier_id'] = strtoupper($this->supplier);
        $this->postJson(self::URL, $input)->assertCreated();
        $input['lines'][] = $this->payload()['lines'][0];
        $this->postJson(self::URL, $input)->assertUnprocessable();
    }

    public function test_demo_and_access_seeders_preserve_user_edits_trashed_and_ledgers(): void
    {
        $grants = DB::table('model_has_permissions')->get()->toJson();
        $this->seed([PurchaseOrdersAccessSeeder::class, PurchaseOrdersAccessSeeder::class, UnitsDemoSeeder::class, WarehousesDemoSeeder::class, ItemsDemoSeeder::class, SuppliersDemoSeeder::class]);
        $this->assertSame($grants, DB::table('model_has_permissions')->get()->toJson());
        $this->seed(PurchaseOrdersDemoSeeder::class);
        $this->assertDatabaseCount('purchase_orders', 1);
        $row = DB::table('purchase_orders')->first();
        DB::table('purchase_orders')->where('id', $row->id)->update(['notes' => 'User edited notes', 'deleted_at' => now()]);
        $before = [DB::table('purchase_orders')->get()->toJson(), DB::table('purchase_order_lines')->get()->toJson(), DB::table('activity_log')->count()];
        $this->seed(PurchaseOrdersDemoSeeder::class);
        $this->assertSame($before, [DB::table('purchase_orders')->get()->toJson(), DB::table('purchase_order_lines')->get()->toJson(), DB::table('activity_log')->count()]);
        foreach (['stock_balances', 'stock_movements', 'fifo_layers', 'payment_receipts', 'sales_invoices'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
    }

    public function test_demo_rejects_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        $this->expectException(LogicException::class);
        app(PurchaseOrdersDemoSeeder::class)->run();
    }
}
