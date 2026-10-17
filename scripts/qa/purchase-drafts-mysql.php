<?php

use App\Modules\Inventory\Items\Application\Contracts\ItemStockLookup;
use App\Modules\Inventory\Items\Database\Seeders\ItemsDemoSeeder;
use App\Modules\Inventory\Stock\Application\Actions\ManageOpening;
use App\Modules\Inventory\Stock\Application\Actions\PostOpening;
use App\Modules\Inventory\Units\Application\Contracts\UnitLookup;
use App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder;
use App\Modules\Inventory\Warehouses\Application\Contracts\WarehouseLookup;
use App\Modules\Inventory\Warehouses\Database\Seeders\WarehousesDemoSeeder;
use App\Modules\Purchasing\PurchaseOrders\Application\Actions\ManagePurchaseOrder;
use App\Modules\Purchasing\PurchaseOrders\Database\Seeders\PurchaseOrdersDemoSeeder;
use App\Modules\Purchasing\Suppliers\Application\Contracts\SupplierLookup;
use App\Modules\Purchasing\Suppliers\Database\Seeders\SuppliersDemoSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('local') || config('database.default') !== 'mysql') {
    throw new LogicException('QA hanya MySQL local/disposable.');
}
$original = config('database.connections.mysql');
$name = 'pos_purchase_qa_'.bin2hex(random_bytes(8));
if (! preg_match('/^pos_purchase_qa_[a-f0-9]{16}$/D', $name) || $name === $original['database']) {
    throw new LogicException('Nama database QA tidak aman.');
}
$server = DB::connection('mysql')->getPdo();
$created = false;
$check = static function (bool $ok, string $label): void {
    if (! $ok) {
        throw new RuntimeException($label);
    } echo "PASS: {$label}\n";
};
try {
    $server->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $created = true;
    $connection = array_replace($original, ['database' => $name]);
    config(['database.connections.purchase_qa' => $connection, 'database.connections.purchase_peer' => $connection, 'database.default' => 'purchase_qa']);
    $app->detectEnvironment(fn () => 'testing');
    $check(Artisan::call('migrate', ['--force' => true]) === 0, 'Migration lengkap native MySQL');
    foreach ([UnitsDemoSeeder::class, WarehousesDemoSeeder::class, ItemsDemoSeeder::class, SuppliersDemoSeeder::class] as $seeder) {
        app($seeder)->run();
    }
    $warehouse = collect(app(WarehouseLookup::class)->choices())->firstWhere('code', 'WH-MAIN');
    $supplier = collect(app(SupplierLookup::class)->choices())->firstWhere('code', 'DEMO-SUP-CCTV');
    $item = collect(app(ItemStockLookup::class)->choices())->firstWhere('sku', 'DEMO-CCTV-IP-2MP');
    $unit = app(UnitLookup::class)->findActiveByCode('UNIT');
    $payload = ['warehouse_id' => $warehouse->id, 'supplier_id' => $supplier->id, 'lines' => [['item_id' => $item->id, 'unit_id' => $unit->id, 'quantity' => '2', 'unit_cost' => '500000']]];
    $opening = app(ManageOpening::class)->create($payload);
    app(PostOpening::class)->handle($opening);
    $ledger = static fn () => array_map(fn ($table) => DB::table($table)->orderBy('id')->get()->toJson(), ['stock_balances', 'stock_movements', 'fifo_layers', 'stock_issues', 'payment_receipts', 'sales_invoices']);
    $beforeLedger = $ledger();
    app(PurchaseOrdersDemoSeeder::class)->run();
    $snapshot = static fn () => [DB::table('purchase_orders')->orderBy('id')->get()->toJson(), DB::table('purchase_order_lines')->orderBy('id')->get()->toJson(), Activity::count()];
    $before = $snapshot();
    app(PurchaseOrdersDemoSeeder::class)->run();
    $check($before === $snapshot(), 'Demo native idempotent');
    $action = app(ManagePurchaseOrder::class);
    $id = $action->save($payload);
    $peer = DB::connection('purchase_peer');
    DB::statement('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    DB::beginTransaction();
    try {
        DB::table('purchase_orders')->where('id', $id)->first();
        $peer->table('purchase_orders')->where('id', $id)->update(['version' => 2, 'notes' => 'Peer revision']);
        $rejected = false;
        try {
            $action->save($payload + ['version' => 1], $id);
        } catch (ValidationException) {
            $rejected = true;
        }
        $check($rejected, 'Locked current read menolak revision stale setelah peer commit');
    } finally {
        DB::rollBack();
    }
    DB::beginTransaction();
    try {
        DB::table('suppliers')->where('id', $supplier->id)->first();
        $peer->table('suppliers')->where('id', $supplier->id)->update(['is_active' => false]);
        $rejected = false;
        try {
            $action->save($payload + ['version' => 2], $id);
        } catch (ValidationException) {
            $rejected = true;
        }
        $check($rejected, 'Supplier lookup lock menolak master nonaktif terbaru');
    } finally {
        DB::rollBack();
    }
    $peer->table('suppliers')->where('id', $supplier->id)->update(['is_active' => true]);
    $action->delete($id, 2);
    $action->restore($id, 3);
    $before = $snapshot();
    Activity::creating(fn ($log) => $log->log_name === 'purchasing.purchase-orders' ? false : null);
    $rejected = false;
    try {
        $input = $payload + ['version' => 4, 'notes' => 'Must rollback'];
        $action->save($input, $id);
    } catch (RuntimeException) {
        $rejected = true;
    } finally {
        Activity::flushEventListeners();
    }
    $check($rejected && $before === $snapshot(), 'Audit veto rollback header/baris/revision native');
    $copy = (array) DB::table('purchase_order_lines')->where('purchase_order_id', $id)->first();
    $copy['id'] = strtolower((string) Str::ulid());
    $rejected = false;
    try {
        DB::transaction(fn () => DB::table('purchase_order_lines')->insert($copy));
    } catch (UniqueConstraintViolationException) {
        $rejected = true;
    }
    $check($rejected, 'Unique order/item native');
    $rejected = false;
    try {
        DB::transaction(fn () => DB::table('suppliers')->where('id', $supplier->id)->delete());
    } catch (QueryException $error) {
        if (($error->errorInfo[1] ?? null) !== 1451) {
            throw $error;
        } $rejected = true;
    }
    $check($rejected, 'FK RESTRICT supplier menjaga referensi draft');
    $check($beforeLedger === $ledger(), 'Lifecycle draft tidak mengubah saldo/movement/FIFO existing atau finance/sales');
    $migration = require app_path('Modules/Purchasing/PurchaseOrders/Database/Migrations/2026_10_17_000002_create_purchase_drafts.php');
    $migration->down();
    $migration->up();
    $check(DB::table('purchase_orders')->count() === 0, 'Migration module down/up disposable');
} finally {
    DB::purge('purchase_peer');
    DB::purge('purchase_qa');
    if ($created) {
        $server->exec('DROP DATABASE `'.$name.'`');
        echo "PASS: QA database dibersihkan, database pengguna tidak dimutasi.\n";
    }
}
