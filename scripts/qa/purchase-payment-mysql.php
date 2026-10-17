<?php

use App\Modules\Finance\Payments\Database\Seeders\PaymentsDemoSeeder;
use App\Modules\Inventory\Items\Database\Seeders\ItemsDemoSeeder;
use App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder;
use App\Modules\Inventory\Warehouses\Database\Seeders\WarehousesDemoSeeder;
use App\Modules\Purchasing\PurchaseOrders\Application\Actions\ManagePurchaseOrder;
use App\Modules\Purchasing\PurchaseOrders\Application\Actions\PayPurchaseOrder;
use App\Modules\Purchasing\PurchaseOrders\Application\Actions\PostPurchaseOrder;
use App\Modules\Purchasing\PurchaseOrders\Database\Seeders\PurchaseOrdersReceiptDemoSeeder;
use App\Modules\Purchasing\Suppliers\Database\Seeders\SuppliersDemoSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('local') || config('database.default') !== 'mysql') {
    throw new LogicException('QA hanya local/MySQL disposable.');
}
$original = config('database.connections.mysql');
$valid = static fn ($name) => is_string($name) && preg_match('/^pos_purchase_pay_qa_[a-f0-9]{16}$/D', $name) && $name !== $original['database'];
if (($argv[1] ?? '') === '--worker') {
    try {
        $input = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
        if (! $valid($input['database'] ?? null)) {
            throw new LogicException('Database worker tidak aman.');
        }
        config(['database.connections.pay_worker' => array_replace($original, ['database' => $input['database']])]);
        DB::setDefaultConnection('pay_worker');
        $app->detectEnvironment(fn () => 'testing');
        echo json_encode(['thread' => DB::selectOne('SELECT CONNECTION_ID() AS id')->id])."\n";
        flush();
        $result = app(PayPurchaseOrder::class)->handle($input['id'], 2, $input['payments']);
        echo json_encode(['id' => $result['payment_disbursement_id']])."\n";
        exit(0);
    } catch (ValidationException) {
        echo json_encode(['rejected' => true])."\n";
        exit(0);
    } catch (Throwable) {
        fwrite(STDERR, "FAIL: worker pembayaran gagal.\n");
        exit(1);
    }
}
$name = 'pos_purchase_pay_qa_'.bin2hex(random_bytes(8));
if (! $valid($name)) {
    throw new LogicException('Nama database tidak aman.');
}
$server = DB::connection('mysql')->getPdo();
$created = false;
$worker = null;
$pipes = [];
$check = static function (bool $ok, string $label): void {
    if (! $ok) {
        throw new RuntimeException($label);
    } echo "PASS: {$label}\n";
};
try {
    $server->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $created = true;
    config(['database.connections.pay_qa' => array_replace($original, ['database' => $name])]);
    DB::setDefaultConnection('pay_qa');
    $app->detectEnvironment(fn () => 'testing');
    $check(Artisan::call('migrate', ['--force' => true]) === 0, 'Native migrate lengkap');
    foreach ([UnitsDemoSeeder::class, WarehousesDemoSeeder::class, ItemsDemoSeeder::class, SuppliersDemoSeeder::class, PaymentsDemoSeeder::class, PurchaseOrdersReceiptDemoSeeder::class] as $seeder) {
        app($seeder)->run();
    }
    $order = DB::table('purchase_orders')->sole();
    $financeMigration = require app_path('Modules/Finance/Payments/Database/Migrations/2026_10_17_000005_create_payment_disbursements.php');
    $purchaseMigration = require app_path('Modules/Purchasing/PurchaseOrders/Database/Migrations/2026_10_17_000006_add_purchase_payment.php');
    $legacy = DB::table('purchase_orders')->select('id', 'version', 'stock_receipt_id', 'total_value')->get()->toJson();
    $purchaseMigration->down();
    $financeMigration->down();
    $financeMigration->up();
    $purchaseMigration->up();
    $check($legacy === DB::table('purchase_orders')->select('id', 'version', 'stock_receipt_id', 'total_value')->get()->toJson(), 'Empty up/down mempertahankan receipt/order legacy');
    $stock = static fn () => hash('sha256', json_encode(array_map(fn ($t) => DB::table($t)->orderBy('id')->get()->all(), ['stock_receipts', 'stock_receipt_lines', 'stock_balances', 'stock_movements', 'fifo_layers']), JSON_THROW_ON_ERROR));
    $before = $stock();
    $methods = DB::table('payment_methods')->pluck('id', 'code');
    $payments = [['method_id' => $methods['TUNAI'], 'amount' => '100000'], ['method_id' => $methods['TRANSFER'], 'amount' => '3612500']];
    DB::beginTransaction();
    DB::table('purchase_orders')->where('id', $order->id)->lockForUpdate()->first();
    $worker = proc_open([PHP_BINARY, __FILE__, '--worker'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path(), null, ['bypass_shell' => true]);
    if (! is_resource($worker)) {
        throw new RuntimeException('Worker gagal dimulai.');
    }
    fwrite($pipes[0], json_encode(['database' => $name, 'id' => $order->id, 'payments' => $payments], JSON_THROW_ON_ERROR));
    fclose($pipes[0]);
    stream_set_timeout($pipes[1], 30);
    $ready = json_decode(trim((string) fgets($pipes[1])), true, flags: JSON_THROW_ON_ERROR);
    $waiting = false;
    for ($i = 0; $i < 100; $i++) {
        $state = DB::connection('mysql')->selectOne('SELECT trx_state FROM information_schema.innodb_trx WHERE trx_mysql_thread_id = ?', [$ready['thread']]);
        if (($state->trx_state ?? null) === 'LOCK WAIT') {
            $waiting = true;
            break;
        } usleep(100000);
    }
    $check($waiting, 'Double payment worker terbukti LOCK WAIT');
    $result = app(PayPurchaseOrder::class)->handle($order->id, 2, $payments);
    DB::commit();
    $peer = json_decode(trim((string) stream_get_contents($pipes[1])), true, flags: JSON_THROW_ON_ERROR);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exit = proc_close($worker);
    $worker = null;
    $check($exit === 0 && $peer['id'] === $result['payment_disbursement_id'] && DB::table('payment_disbursements')->count() === 1, 'Concurrent replay menghasilkan satu pengeluaran');
    DB::table('payment_methods')->update(['is_active' => false]);
    $replay = app(PayPurchaseOrder::class)->handle($order->id, 2, array_reverse($payments));
    $check($replay == $result && DB::table('activity_log')->where('event', 'disbursed')->count() === 1, 'Replay snapshot MySQL JSON sesudah master nonaktif tanpa audit ganda');
    DB::table('payment_methods')->update(['is_active' => true]);
    $line = DB::table('purchase_order_lines')->where('purchase_order_id', $order->id)->first();
    $second = app(ManagePurchaseOrder::class)->save(['supplier_id' => $order->supplier_id, 'warehouse_id' => $order->warehouse_id, 'lines' => [['item_id' => $line->item_id, 'unit_id' => $line->unit_id, 'quantity' => '1', 'unit_cost' => '10.123456']]]);
    app(PostPurchaseOrder::class)->handle($second, 1);
    $before = $stock();
    Activity::creating(fn ($log) => $log->log_name === 'purchasing.purchase-orders' && $log->event === 'paid' ? false : null);
    $rejected = false;
    try {
        app(PayPurchaseOrder::class)->handle($second, 2, [['method_id' => $methods['TUNAI'], 'amount' => '10.123456']]);
    } catch (RuntimeException) {
        $rejected = true;
    } finally {
        Activity::flushEventListeners();
    }
    $check($rejected && DB::table('payment_disbursements')->count() === 1 && DB::table('purchase_orders')->where('id', $second)->value('payment_disbursement_id') === null && $stock() === $before, 'Native audit veto rollback dua module, Stock identik');
    $paid = app(PayPurchaseOrder::class)->handle($second, 2, [['method_id' => $methods['TUNAI'], 'amount' => '10.123456']]);
    $check($paid['payment_snapshot']['total'] === '10.123456' && $stock() === $before, 'Presisi6 native tanpa rounding atau mutation stok');
    $third = app(ManagePurchaseOrder::class)->save(['supplier_id' => $order->supplier_id, 'warehouse_id' => $order->warehouse_id,
        'lines' => [['item_id' => $line->item_id, 'unit_id' => $line->unit_id, 'quantity' => '1', 'unit_cost' => '15']]]);
    app(PostPurchaseOrder::class)->handle($third, 1);
    $before = $stock();
    DB::beginTransaction();
    $accountId = DB::table('payment_methods')->where('id', $methods['TUNAI'])->value('payment_account_id');
    DB::table('payment_accounts')->where('id', $accountId)->lockForUpdate()->first();
    $worker = proc_open([PHP_BINARY, __FILE__, '--worker'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path(), null, ['bypass_shell' => true]);
    if (! is_resource($worker)) {
        throw new RuntimeException('Worker current read gagal dimulai.');
    }
    fwrite($pipes[0], json_encode(['database' => $name, 'id' => $third, 'payments' => [['method_id' => $methods['TUNAI'], 'amount' => '15']]], JSON_THROW_ON_ERROR));
    fclose($pipes[0]);
    stream_set_timeout($pipes[1], 30);
    $ready = json_decode(trim((string) fgets($pipes[1])), true, flags: JSON_THROW_ON_ERROR);
    $waiting = false;
    for ($i = 0; $i < 100; $i++) {
        $state = DB::connection('mysql')->selectOne('SELECT trx_state FROM information_schema.innodb_trx WHERE trx_mysql_thread_id = ?', [$ready['thread']]);
        if (($state->trx_state ?? null) === 'LOCK WAIT') {
            $waiting = true;
            break;
        } usleep(100000);
    }
    $check($waiting, 'Current read metode: worker terbukti menunggu lock akun');
    DB::table('payment_methods')->where('id', $methods['TUNAI'])->update(['is_active' => false]);
    DB::commit();
    $peer = json_decode(trim((string) stream_get_contents($pipes[1])), true, flags: JSON_THROW_ON_ERROR);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exit = proc_close($worker);
    $worker = null;
    $check($exit === 0 && ($peer['rejected'] ?? false) && DB::table('payment_disbursements')->count() === 2
        && DB::table('purchase_orders')->where('id', $third)->value('payment_disbursement_id') === null && $before === $stock(), 'Peer nonaktifkan metode: current read menolak pembayaran tanpa mutation');
    foreach ([$purchaseMigration, $financeMigration] as $migration) {
        $rejected = false;
        try {
            $migration->down();
        } catch (LogicException) {
            $rejected = true;
        } $check($rejected, 'Down posted ditolak');
    }
    $rejected = false;
    try {
        DB::table('payment_disbursements')->where('id', $result['payment_disbursement_id'])->delete();
    } catch (QueryException $e) {
        if (($e->errorInfo[1] ?? null) !== 1451) {
            throw $e;
        } $rejected = true;
    }
    $check($rejected, 'Native FK restrict mempertahankan pengeluaran');
    $check(DB::table('payment_receipts')->count() === 0 && DB::table('sales_invoices')->count() === 0, 'Receipt penjualan tidak tercampur');
} finally {
    while (DB::connection('pay_qa')->transactionLevel() > 0) {
        DB::connection('pay_qa')->rollBack();
    }
    if (is_resource($worker)) {
        proc_terminate($worker);
        foreach ($pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        } proc_close($worker);
    }
    DB::purge('pay_qa');
    if ($created) {
        $server->exec('DROP DATABASE `'.$name.'`');
        echo "PASS: DB QA dibersihkan, DB user tidak dimutasi.\n";
    }
}
