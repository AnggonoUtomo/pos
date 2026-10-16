<?php

namespace Tests\Feature\Modules\Purchasing\PurchaseOrders;

use App\Modules\Purchasing\PurchaseOrders\Application\Actions\ManagePurchaseOrder;
use App\Modules\Purchasing\PurchaseOrders\Application\Contracts\PurchaseOrderRepository;
use App\Modules\Purchasing\Suppliers\Application\Contracts\SupplierLookup;
use App\Modules\Purchasing\Suppliers\Infrastructure\Persistence\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;
use Tests\Feature\Modules\Inventory\Stock\StockTestCase;

class PurchaseDraftTest extends StockTestCase
{
    protected string $supplier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->supplier = Supplier::create(['code' => 'SUP-QA', 'name' => 'Supplier QA', 'phone' => 'private-contact', 'is_active' => true])->id;
    }

    protected function payload(): array
    {
        return parent::payload() + ['supplier_id' => $this->supplier];
    }

    private function record(string $id): array
    {
        return app(PurchaseOrderRepository::class)->paginate(1, [])['data'][0];
    }

    public function test_draft_snapshot_lifecycle_noop_stale_and_no_ledger(): void
    {
        $action = app(ManagePurchaseOrder::class);
        $input = $this->payload() + ['status' => 'posted', 'total_value' => '1', 'number' => 'UNTRUSTED'];
        $id = $action->save($input);
        $this->assertTrue(Str::isUlid($id));
        $row = $this->record($id);
        $this->assertSame('PO-'.strtoupper($id), $row['number']);
        $this->assertSame('draft', $row['status']);
        $this->assertSame(1, $row['version']);
        $this->assertSame('240000.000000', $row['total_value']);
        $this->assertSame('24.000000', $row['lines'][0]['base_quantity']);
        $this->assertSame($this->manager->id, Activity::where('log_name', 'purchasing.purchase-orders')->sole()->causer_id);
        $action->save($input + ['version' => 1], $id);
        $this->assertSame(1, $this->record($id)['version']);
        $input['lines'][0]['quantity'] = '3';
        $action->save($input + ['version' => 1], $id);
        $this->assertSame('360000.000000', $this->record($id)['total_value']);
        try {
            $action->delete($id, 1);
            $this->fail('Revision stale harus ditolak.');
        } catch (ValidationException) {
            $this->assertSame(2, $this->record($id)['version']);
        }
        $action->delete($id, 2);
        $this->assertSoftDeleted('purchase_orders', ['id' => $id]);
        $action->restore($id, 3);
        $this->assertDatabaseHas('purchase_orders', ['id' => $id, 'version' => 4, 'deleted_at' => null, 'status' => 'draft']);
        $logs = Activity::where('log_name', 'purchasing.purchase-orders')->get();
        $this->assertSame(['created', 'updated', 'deleted', 'restored'], $logs->pluck('event')->all());
        $this->assertStringNotContainsString('Stok awal QA', $logs->toJson());
        $this->assertStringNotContainsString('private-contact', $logs->toJson());
        foreach (['stock_balances', 'stock_movements', 'fifo_layers', 'stock_issues', 'payment_receipts', 'sales_invoices'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
    }

    public function test_invalid_masters_purchase_flag_duplicate_and_zero_cost_are_rejected(): void
    {
        $action = app(ManagePurchaseOrder::class);
        $original = $this->payload();
        $inputs = [];
        $input = $original;
        $input['lines'][] = $input['lines'][0];
        $inputs[] = $input;
        $input = $original;
        $input['lines'][0]['unit_cost'] = '0';
        $inputs[] = $input;
        $input = $original;
        $input['lines'][0]['quantity'] = 2;
        $inputs[] = $input;
        foreach ($inputs as $input) {
            try {
                $action->save($input);
                $this->fail('Invalid input diterima.');
            } catch (ValidationException) {
                $this->assertDatabaseCount('purchase_orders', 0);
            }
        }
        foreach (['suppliers' => $this->supplier, 'warehouses' => $this->warehouse, 'units' => $this->box, 'items' => $this->item] as $table => $id) {
            DB::table($table)->where('id', $id)->update(['is_active' => false]);
            try {
                $action->save($original);
                $this->fail('Inactive master diterima.');
            } catch (ValidationException) {
                $this->assertDatabaseCount('purchase_orders', 0);
            }
            DB::table($table)->where('id', $id)->update(['is_active' => true]);
        }
        DB::table('item_units')->where('item_id', $this->item)->where('unit_id', $this->box)->update(['is_purchase_unit' => false]);
        $this->expectException(ValidationException::class);
        $action->save($original);
    }

    public function test_audit_veto_rolls_back_header_lines_and_lifecycle(): void
    {
        $action = app(ManagePurchaseOrder::class);
        Activity::creating(fn ($log) => $log->log_name === 'purchasing.purchase-orders' ? false : null);
        try {
            $action->save($this->payload());
            $this->fail('Audit veto harus ditolak.');
        } catch (RuntimeException) {
            $this->assertDatabaseCount('purchase_orders', 0);
            $this->assertDatabaseCount('purchase_order_lines', 0);
        } finally {
            Activity::flushEventListeners();
        }
        $id = $action->save($this->payload());
        foreach (['update', 'delete', 'restore'] as $operation) {
            if ($operation === 'restore') {
                $action->delete($id, 1);
            }
            $before = [DB::table('purchase_orders')->get()->toJson(), DB::table('purchase_order_lines')->get()->toJson(), Activity::count()];
            Activity::creating(fn ($log) => $log->log_name === 'purchasing.purchase-orders' ? false : null);
            try {
                $input = $this->payload();
                $input['notes'] = 'Changed';
                $input['version'] = 1;
                match ($operation) {
                    'update' => $action->save($input, $id), 'delete' => $action->delete($id, 1), 'restore' => $action->restore($id, 2)
                };
                $this->fail('Audit veto harus rollback.');
            } catch (RuntimeException) {
                $this->assertSame($before, [DB::table('purchase_orders')->get()->toJson(), DB::table('purchase_order_lines')->get()->toJson(), Activity::count()]);
            } finally {
                Activity::flushEventListeners();
            }
        }
    }

    public function test_supplier_lookup_is_redacted_and_filters_deleted_or_inactive(): void
    {
        $lookup = app(SupplierLookup::class);
        $row = $lookup->findActive($this->supplier);
        $this->assertSame(['id', 'code', 'name'], array_keys(get_object_vars($row)));
        $this->assertCount(1, $lookup->choices());
        DB::table('suppliers')->where('id', $this->supplier)->update(['deleted_at' => now()]);
        $this->assertNull($lookup->findActive($this->supplier));
        $this->assertSame([], $lookup->choices());
    }

    public function test_migration_up_down_on_disposable_database(): void
    {
        $migration = require app_path('Modules/Purchasing/PurchaseOrders/Database/Migrations/2026_10_17_000002_create_purchase_drafts.php');
        $migration->down();
        $this->assertFalse(Schema::hasTable('purchase_orders'));
        $migration->up();
        $this->assertTrue(Schema::hasColumns('purchase_orders', ['version', 'deleted_at', 'total_value']));
    }
}
