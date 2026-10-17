<?php

use App\Models\User;
use App\Modules\Finance\Payments\Database\Seeders\PaymentsDemoSeeder;
use App\Modules\Inventory\Items\Database\Seeders\ItemsDemoSeeder;
use App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder;
use App\Modules\Inventory\Warehouses\Database\Seeders\WarehousesDemoSeeder;
use App\Modules\Purchasing\PurchaseOrders\Database\Seeders\PurchaseOrdersAccessSeeder;
use App\Modules\Purchasing\PurchaseOrders\Database\Seeders\PurchaseOrdersDemoSeeder;
use App\Modules\Purchasing\PurchaseOrders\Database\Seeders\PurchaseOrdersReceiptDemoSeeder;
use App\Modules\Purchasing\Suppliers\Database\Seeders\SuppliersDemoSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$database = config('database.connections.sqlite.database');
if (! app()->environment('testing') || config('database.default') !== 'sqlite'
    || realpath(dirname($database)) !== realpath(__DIR__.'/../../storage/framework/testing')
    || ! preg_match('/^purchase-(?:drafts|receipt|payment)-browser-[a-f0-9]{16}\.sqlite$/D', basename($database))) {
    throw new LogicException('QA hanya SQLite disposable.');
}
$input = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
$stockHash = static fn () => hash('sha256', json_encode(array_map(fn ($t) => DB::table($t)->orderBy('id')->get()->all(), ['stock_receipts', 'stock_receipt_lines', 'stock_balances', 'stock_movements', 'fifo_layers']), JSON_THROW_ON_ERROR));
if (($input['verify_payment'] ?? false) === true) {
    $order = DB::table('purchase_orders')->where('demo_key', 'cctv-electrical-purchase-v1')->sole();
    if ($order->status !== 'posted' || $order->version !== 3 || $order->paid_from_version !== 2 || $order->payment_disbursement_id === null
        || DB::table('payment_disbursements')->count() !== 1 || DB::table('payment_disbursement_lines')->count() !== 2 || ! hash_equals($input['stock_hash'], $stockHash())
        || DB::table('payment_receipts')->exists() || DB::table('sales_invoices')->exists()) {
        throw new RuntimeException('Payment/stock QA tidak sesuai.');
    }
    if (DB::table('activity_log')->where('event', 'paid')->count() !== 1 || DB::table('activity_log')->where('event', 'disbursed')->count() !== 1) {
        throw new RuntimeException('Audit pembayaran ganda.');
    }
    echo 'PASS: pembayaran lunas sekali, stock identik, tanpa receipt sale.';
    exit;
}
if (($input['verify_receipt'] ?? false) === true) {
    $order = DB::table('purchase_orders')->where('demo_key', 'cctv-electrical-purchase-v1')->sole();
    if ($order->status !== 'posted' || $order->version !== 3 || $order->posted_from_version !== 2 || $order->stock_receipt_id === null || DB::table('stock_receipts')->count() !== 1
        || DB::table('stock_movements')->count() !== 2 || DB::table('fifo_layers')->count() !== 2 || DB::table('stock_balances')->count() !== 2) {
        throw new RuntimeException('Receipt QA tidak sesuai.');
    }
    foreach (['payment_receipts', 'sales_invoices'] as $table) {
        if (DB::table($table)->exists()) {
            throw new RuntimeException('Receipt tidak boleh membuat Finance/Sales.');
        }
    }
    echo 'PASS: posted/receipt/layers/balances tanpa Finance/Sales.';
    exit;
}
if (($input['verify'] ?? false) === true) {
    $row = DB::table('purchase_orders')->whereNull('demo_key')->sole();
    $line = DB::table('purchase_order_lines')->where('purchase_order_id', $row->id)->sole();
    if ($row->notes !== 'QA purchase updated' || $row->status !== 'draft' || $row->version !== 4 || $row->deleted_at !== null
        || $line->quantity !== '0.250000' || $line->base_quantity !== '25.000000' || $row->total_value !== '225000.000000') {
        throw new RuntimeException('Persistence draft tidak sesuai.');
    }
    $logs = DB::table('activity_log')->where('log_name', 'purchasing.purchase-orders')->get();
    if ($logs->count() !== 5 || str_contains($logs->toJson(), 'QA purchase')) {
        throw new RuntimeException('Audit tidak sesuai.');
    }
    foreach (['stock_balances', 'stock_movements', 'fifo_layers', 'stock_issues', 'payment_receipts', 'sales_invoices'] as $table) {
        if (DB::table($table)->exists()) {
            throw new RuntimeException('Draft mengubah ledger.');
        }
    }
    echo 'PASS: persistence/exact value/audit privacy/no ledger.';
    exit;
}
if (! is_string($input['password'] ?? null) || strlen($input['password']) < 24) {
    throw new LogicException('Credential random wajib via stdin.');
}
Artisan::call('migrate', ['--force' => true]);
foreach ([UnitsDemoSeeder::class, WarehousesDemoSeeder::class, ItemsDemoSeeder::class, SuppliersDemoSeeder::class, PurchaseOrdersAccessSeeder::class, PurchaseOrdersDemoSeeder::class] as $seeder) {
    app($seeder)->run();
}
if ($input['payment'] ?? false) {
    app(PaymentsDemoSeeder::class)->run();
    app(PurchaseOrdersReceiptDemoSeeder::class)->run();
}
foreach (['manager', 'receiver', 'payer', 'viewer', 'outsider'] as $role) {
    $user = User::create(['name' => 'QA '.$role, 'email' => "qa-{$role}@example.test", 'password' => $input['password']]);
    if ($role === 'manager') {
        $user->givePermissionTo(['purchasing.purchase-orders.view', 'purchasing.purchase-orders.manage']);
        if ($input['receipt'] ?? false) {
            $user->givePermissionTo(['purchasing.purchase-orders.post', Permission::findOrCreate('inventory.stock.view', 'web')]);
        }
        if ($input['payment'] ?? false) {
            $user->givePermissionTo(['purchasing.purchase-orders.post', 'purchasing.purchase-orders.pay']);
        }
    } elseif ($role === 'receiver') {
        $user->givePermissionTo(['purchasing.purchase-orders.view', 'purchasing.purchase-orders.post']);
    } elseif ($role === 'viewer') {
        $user->givePermissionTo('purchasing.purchase-orders.view');
    } elseif ($role === 'payer') {
        $user->givePermissionTo(['purchasing.purchase-orders.view', 'purchasing.purchase-orders.pay']);
    }
}
echo ($input['payment'] ?? false) ? json_encode(['stock_hash' => $stockHash(), 'number' => DB::table('purchase_orders')->sole()->number, 'total' => DB::table('purchase_orders')->sole()->total_value]) : 'Fixture draft siap.';
