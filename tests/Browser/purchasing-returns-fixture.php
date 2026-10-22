<?php

use App\Models\User;
use App\Modules\Finance\Payments\Database\Seeders\PaymentsDemoSeeder;
use App\Modules\Inventory\Items\Database\Seeders\ItemsDemoSeeder;
use App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder;
use App\Modules\Inventory\Warehouses\Database\Seeders\WarehousesDemoSeeder;
use App\Modules\Purchasing\PurchaseOrders\Database\Seeders\PurchaseOrdersAccessSeeder;
use App\Modules\Purchasing\PurchaseOrders\Database\Seeders\PurchaseOrdersDemoSeeder;
use App\Modules\Purchasing\PurchaseOrders\Database\Seeders\PurchaseOrdersReceiptDemoSeeder;
use App\Modules\Purchasing\Returns\Application\Actions\ManagePurchaseReturnDraft;
use App\Modules\Purchasing\Returns\Database\Seeders\ReturnsAccessSeeder;
use App\Modules\Purchasing\Suppliers\Database\Seeders\SuppliersDemoSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$database = config('database.connections.sqlite.database');
if (! app()->environment('testing') || config('database.default') !== 'sqlite'
    || realpath(dirname($database)) !== realpath(__DIR__.'/../../storage/framework/testing')
    || ! preg_match('/^purchasing-returns-browser-[a-f0-9]{16}\.sqlite$/D', basename($database))) {
    throw new LogicException('QA retur pembelian hanya SQLite disposable.');
}

$input = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
$sourceFingerprint = static function (): string {
    $rows = [];
    foreach (['purchase_orders', 'purchase_order_lines', 'stock_receipts', 'stock_receipt_lines',
        'payment_disbursements', 'payment_disbursement_lines', 'payment_receipts', 'payment_records',
        'payment_refunds', 'payment_refund_lines'] as $table) {
        if (Schema::hasTable($table)) {
            $rows[$table] = DB::table($table)->orderBy('id')->get()->all();
        }
    }

    return hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR));
};
$ledgerFingerprint = static function (): string {
    $tables = ['stock_receipts', 'stock_receipt_lines', 'stock_balances', 'stock_movements', 'fifo_layers', 'fifo_consumptions', 'payment_disbursements', 'payment_disbursement_lines', 'payment_receipts', 'payment_receipt_lines'];
    $rows = [];
    foreach ($tables as $table) {
        if (Schema::hasTable($table)) {
            $rows[$table] = DB::table($table)->orderBy('id')->get()->all();
        }
    }

    return hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR));
};

if (($input['snapshot'] ?? false) === true) {
    echo json_encode(['ledger_fingerprint' => $ledgerFingerprint()], JSON_THROW_ON_ERROR);
    exit;
}

if (($input['verify_posting'] ?? false) === true) {
    $posted = DB::table('purchase_returns')->where('id', $input['posted_id'])->sole();
    $blocked = DB::table('purchase_returns')->where('id', $input['blocked_id'])->sole();
    if ($posted->status !== 'posted' || $posted->version !== 2 || $posted->posted_from_version !== 1
        || $posted->stock_issue_id === null || $blocked->status !== 'draft' || $blocked->version !== 1
        || DB::table('stock_issues')->where('source_type', 'purchase_return')->count() !== 1
        || DB::table('activity_log')->where('log_name', 'purchasing.returns')->where('event', 'posted')->count() !== 1
        || ! hash_equals($input['source_fingerprint'], $sourceFingerprint())) {
        throw new RuntimeException('Posting browser QA, ledger atau source fingerprint tidak sesuai.');
    }
    echo json_encode(['status' => 'PASS', 'posting' => 'single', 'blocked' => 'draft', 'source' => 'unchanged'], JSON_THROW_ON_ERROR);
    exit;
}

if (($input['verify'] ?? false) === true) {
    $draft = DB::table('purchase_returns')->sole();
    $events = DB::table('activity_log')->where('log_name', 'purchasing.returns')->orderBy('id')->pluck('event')->all();
    if ($draft->status !== 'draft' || $draft->version !== 4 || $draft->deleted_at !== null
        || ! hash_equals($input['ledger_fingerprint'], $ledgerFingerprint())
        || $events !== ['created', 'updated', 'deleted', 'restored']) {
        throw new RuntimeException('Draft/lifecycle QA atau fingerprint ledger tidak sesuai.');
    }
    echo json_encode(['status' => 'PASS', 'events' => $events, 'ledger' => 'unchanged', 'line_count' => DB::table('purchase_return_lines')->where('purchase_return_id', $draft->id)->count()], JSON_THROW_ON_ERROR);
    exit;
}

if (! is_string($input['password'] ?? null) || strlen($input['password']) < 24) {
    throw new LogicException('Credential random QA wajib melalui stdin.');
}

Artisan::call('migrate', ['--force' => true]);
foreach ([UnitsDemoSeeder::class, WarehousesDemoSeeder::class, ItemsDemoSeeder::class, SuppliersDemoSeeder::class, PaymentsDemoSeeder::class,
    PurchaseOrdersAccessSeeder::class, PurchaseOrdersDemoSeeder::class, PurchaseOrdersReceiptDemoSeeder::class, ReturnsAccessSeeder::class] as $seeder) {
    app($seeder)->run();
}

foreach (['manager', 'viewer', 'outsider'] as $role) {
    $user = User::create(['name' => 'QA '.$role, 'email' => "qa-{$role}@example.test", 'password' => $input['password'], 'email_verified_at' => now()]);
    if (in_array($role, ['manager', 'viewer'], true)) {
        $user->givePermissionTo('purchasing.returns.view');
    }
    if ($role === 'manager') {
        $user->givePermissionTo('purchasing.returns.manage');
        if (($input['posting'] ?? false) === true) {
            $user->givePermissionTo('purchasing.returns.post');
        }
    }
}

$purchase = DB::table('purchase_orders')->where('demo_key', 'cctv-electrical-purchase-v1')->where('status', 'posted')->sole();
$line = DB::table('purchase_order_lines')->where('purchase_order_id', $purchase->id)->orderBy('id')->first();
$posting = [];
if (($input['posting'] ?? false) === true) {
    foreach (['posted' => '1', 'blocked' => $line->quantity] as $key => $quantity) {
        $id = app(ManagePurchaseReturnDraft::class)->save(['purchase_order_id' => $purchase->id,
            'lines' => [['purchase_order_line_id' => $line->id, 'quantity' => $quantity]]]);
        $posting[$key.'_id'] = $id;
        $posting[$key.'_number'] = DB::table('purchase_returns')->where('id', $id)->value('number');
    }
    $posting['source_fingerprint'] = $sourceFingerprint();
}
echo json_encode([
    'status' => 'READY',
    'number' => $purchase->number,
    'supplier' => $purchase->supplier_name,
    'sku' => $line->sku,
    'quantity' => $line->quantity,
    'line_id' => $line->id,
    'ledger_fingerprint' => $ledgerFingerprint(),
    ...$posting,
], JSON_THROW_ON_ERROR);
