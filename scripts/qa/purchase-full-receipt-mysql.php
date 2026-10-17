<?php

use App\Modules\Inventory\Items\Database\Seeders\ItemsDemoSeeder;
use App\Modules\Inventory\Stock\Application\Actions\IssueStock;
use App\Modules\Inventory\Stock\Application\Actions\ManageOpening;
use App\Modules\Inventory\Stock\Application\Actions\PostOpening;
use App\Modules\Inventory\Stock\Application\Contracts\StockRepository;
use App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder;
use App\Modules\Inventory\Warehouses\Application\Contracts\WarehouseLookup;
use App\Modules\Inventory\Warehouses\Database\Seeders\WarehousesDemoSeeder;
use App\Modules\Purchasing\PurchaseOrders\Application\Actions\ManagePurchaseOrder;
use App\Modules\Purchasing\PurchaseOrders\Application\Actions\PostPurchaseOrder;
use App\Modules\Purchasing\Suppliers\Database\Seeders\SuppliersDemoSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('local') || config('database.default') !== 'mysql') {
    throw new LogicException('QA hanya MySQL local/generated.');
}
$original = config('database.connections.mysql');
$valid = static fn ($name) => is_string($name) && preg_match('/^pos_full_receipt_qa_[a-f0-9]{16}$/D', $name) && $name !== $original['database'];
if (($argv[1] ?? '') === '--worker') {
    try {
        $input = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
        if (! $valid($input['database'] ?? null)) {
            throw new LogicException('Database worker tidak aman.');
        }
        config(['database.connections.receipt_worker' => array_replace($original, ['database' => $input['database']])]);
        DB::setDefaultConnection('receipt_worker');
        $app->detectEnvironment(fn () => 'testing');
        echo json_encode(['thread' => DB::selectOne('SELECT CONNECTION_ID() AS id')->id])."\n";
        flush();
        $result = $input['mode'] === 'post' ? app(PostPurchaseOrder::class)->handle($input['id'], 1) : app(IssueStock::class)->handle($input['issue']);
        echo json_encode(['status' => 'ok', 'id' => $result['id'], 'receipt' => $result['stock_receipt_id'] ?? null])."\n";
        exit(0);
    } catch (Throwable) {
        fwrite(STDERR, "FAIL: receipt worker gagal.\n");
        exit(1);
    }
}
$name = 'pos_full_receipt_qa_'.bin2hex(random_bytes(8));
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
    config(['database.connections.receipt_qa' => array_replace($original, ['database' => $name])]);
    DB::setDefaultConnection('receipt_qa');
    $app->detectEnvironment(fn () => 'testing');
    $check(Artisan::call('migrate', ['--force' => true]) === 0, 'Native migrate lengkap');
    foreach ([UnitsDemoSeeder::class, WarehousesDemoSeeder::class, ItemsDemoSeeder::class, SuppliersDemoSeeder::class] as $seeder) {
        app($seeder)->run();
    }
    $warehouse = DB::table('warehouses')->where('code', 'WH-MAIN')->value('id');
    $legacyWarehouse = DB::table('warehouses')->where('code', 'WH-SECONDARY')->value('id');
    $supplier = DB::table('suppliers')->where('code', 'DEMO-SUP-CCTV')->value('id');
    $item = DB::table('items')->where('sku', 'DEMO-CCTV-IP-2MP')->first();
    $make = static fn ($quantity, $cost) => ['supplier_id' => $supplier, 'warehouse_id' => $warehouse, 'lines' => [['item_id' => $item->id, 'unit_id' => $item->base_unit_id, 'quantity' => $quantity, 'unit_cost' => $cost]]];
    $opening = app(ManageOpening::class)->create(['warehouse_id' => $legacyWarehouse, 'lines' => [['item_id' => $item->id, 'unit_id' => $item->base_unit_id, 'quantity' => '24', 'unit_cost' => '1000']]]);
    app(PostOpening::class)->handle($opening);
    $legacy = static fn () => DB::table('stock_balances')->where('warehouse_id', $legacyWarehouse)->select('id', 'quantity', 'total_value', 'opening_line_id')->get()->toJson();
    $beforeLegacy = $legacy();
    $stockMigration = require app_path('Modules/Inventory/Stock/Database/Migrations/2026_10_17_000003_create_stock_receipts.php');
    $purchaseMigration = require app_path('Modules/Purchasing/PurchaseOrders/Database/Migrations/2026_10_17_000004_add_purchase_posting.php');
    $purchaseMigration->down();
    $stockMigration->down();
    $stockMigration->up();
    $purchaseMigration->up();
    $check($beforeLegacy === $legacy(), 'Empty native up/down mempertahankan saldo opening legacy');
    $race = function (array $input, callable $lock, callable $parent, string $label) use ($name, $check, &$worker, &$pipes): array {
        DB::beginTransaction();
        try {
            $lock();
            $worker = proc_open([PHP_BINARY, __FILE__, '--worker'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path(), null, ['bypass_shell' => true]);
            if (! is_resource($worker)) {
                throw new RuntimeException('Worker tidak dapat dimulai.');
            }
            fwrite($pipes[0], json_encode($input + ['database' => $name], JSON_THROW_ON_ERROR));
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
            $check($waiting, $label.': worker terbukti LOCK WAIT');
            $main = $parent();
            DB::commit();
        } finally {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        }
        $outcome = json_decode(trim((string) stream_get_contents($pipes[1])), true, flags: JSON_THROW_ON_ERROR);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($worker);
        $worker = null;
        $check($exit === 0 && ($outcome['status'] ?? '') === 'ok', $label.': worker selesai sesudah commit');

        return [$main, $outcome];
    };
    $orders = app(ManagePurchaseOrder::class);
    $post = app(PostPurchaseOrder::class);
    $first = $orders->save($make('2', '50000'));
    [$main, $workerResult] = $race(['mode' => 'post', 'id' => $first], fn () => DB::table('purchase_orders')->where('id', $first)->lockForUpdate()->first(), fn () => $post->handle($first, 1), 'Double posting');
    $check($main['stock_receipt_id'] === $workerResult['receipt'] && DB::table('stock_receipts')->count() === 1, 'Double posting satu receipt, bukan stok ganda');
    $issue = static function () use ($warehouse, $item): array {
        $source = strtolower((string) Str::ulid());

        return ['warehouse_id' => $warehouse, 'source_id' => $source, 'source_number' => 'POS-'.$source, 'lines' => [['source_line_id' => strtolower((string) Str::ulid()), 'item_id' => $item->id, 'base_quantity' => '1']]];
    };
    $second = $orders->save($make('3', '60000'));
    $race(['mode' => 'issue', 'issue' => $issue()], fn () => app(WarehouseLookup::class)->findActive($warehouse, lock: true), fn () => $post->handle($second, 1), 'Receipt dahulu vs sale');
    $third = $orders->save($make('2', '70000'));
    $race(['mode' => 'post', 'id' => $third], fn () => app(WarehouseLookup::class)->findActive($warehouse, lock: true), fn () => app(IssueStock::class)->handle($issue()), 'Sale dahulu vs receipt');
    $balance = DB::table('stock_balances')->where('warehouse_id', $warehouse)->where('item_id', $item->id)->first();
    $check($balance->quantity === '5.000000' && $balance->total_value === '320000.000000' && DB::table('stock_receipts')->count() === 3, 'Tidak lost update; FIFO lama lebih dulu, saldo5 nilai320000');
    $check(DB::table('activity_log')->where('log_name', 'purchasing.purchase-orders')->where('event', 'posted')->count() === 3, 'Tidak audit posted ganda');
    $fourth = $orders->save($make('1', '80000'));
    $before = DB::table('stock_balances')->orderBy('id')->get()->toJson();
    Activity::creating(fn ($log) => $log->log_name === 'purchasing.purchase-orders' && $log->event === 'posted' ? false : null);
    $rejected = false;
    try {
        $post->handle($fourth, 1);
    } catch (RuntimeException) {
        $rejected = true;
    } finally {
        Activity::flushEventListeners();
    }
    $check($rejected && $before === DB::table('stock_balances')->orderBy('id')->get()->toJson() && DB::table('stock_receipts')->count() === 3 && DB::table('purchase_orders')->where('id', $fourth)->value('status') === 'draft', 'Audit Purchasing gagal rollback seluruh Stock native');
    $copy = (array) DB::table('stock_receipt_lines')->first();
    $copy['id'] = strtolower((string) Str::ulid());
    $rejected = false;
    try {
        DB::transaction(fn () => DB::table('stock_receipt_lines')->insert($copy));
    } catch (UniqueConstraintViolationException) {
        $rejected = true;
    }
    $check($rejected, 'Source line receipt native unique');
    $rejected = false;
    try {
        DB::transaction(fn () => DB::table('stock_receipts')->where('id', $main['stock_receipt_id'])->delete());
    } catch (QueryException $error) {
        if (($error->errorInfo[1] ?? null) !== 1451) {
            throw $error;
        } $rejected = true;
    }
    $check($rejected, 'FK RESTRICT receipt posted');
    foreach ([$purchaseMigration, $stockMigration] as $migration) {
        $rejected = false;
        try {
            $migration->down();
        } catch (LogicException) {
            $rejected = true;
        } $check($rejected, 'Down migration posted ditolak');
    }
    $stock = app(StockRepository::class);
    $balances = $stock->paginate('balances', 1, []);
    $movements = $stock->paginate('movements', 1, ['warehouse_id' => $warehouse]);
    $check($balances['meta']['total'] === 2 && $movements['meta']['total'] === 5 && $beforeLegacy === $legacy(), 'Query native saldo purchase-only dan legacy serta kartu stok tetap terlihat');
    $check(DB::table('payment_receipts')->count() === 0 && DB::table('sales_invoices')->count() === 0, 'Inventory sale writer tidak membuat Finance atau invoice palsu');
} finally {
    if (is_resource($worker)) {
        proc_terminate($worker);
        foreach ($pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        } proc_close($worker);
    }
    if (config('database.connections.receipt_qa') !== null) {
        while (DB::connection('receipt_qa')->transactionLevel() > 0) {
            DB::connection('receipt_qa')->rollBack();
        } DB::purge('receipt_qa');
    }
    if ($created) {
        $server->exec('DROP DATABASE `'.$name.'`');
        echo "PASS: database QA dibersihkan, database user tidak dimutasi.\n";
    }
}
