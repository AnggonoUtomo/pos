<?php

use App\Modules\Inventory\Items\Database\Seeders\ItemsDemoSeeder;
use App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder;
use App\Modules\Inventory\Warehouses\Database\Seeders\WarehousesDemoSeeder;
use App\Modules\Purchasing\PurchaseOrders\Application\Contracts\ReturnPurchaseLookup;
use App\Modules\Purchasing\PurchaseOrders\Database\Seeders\PurchaseOrdersAccessSeeder;
use App\Modules\Purchasing\PurchaseOrders\Database\Seeders\PurchaseOrdersDemoSeeder;
use App\Modules\Purchasing\PurchaseOrders\Database\Seeders\PurchaseOrdersReceiptDemoSeeder;
use App\Modules\Purchasing\Returns\Application\Actions\ManagePurchaseReturnDraft;
use App\Modules\Purchasing\Returns\Application\Actions\PostPurchaseReturnDraft;
use App\Modules\Purchasing\Returns\Database\Seeders\ReturnsAccessSeeder;
use App\Modules\Purchasing\Suppliers\Database\Seeders\SuppliersDemoSeeder;
use Brick\Math\BigDecimal;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (! app()->environment('local') || config('database.default') !== 'mysql') {
    throw new LogicException('QA retur pembelian hanya local/MySQL disposable.');
}

$original = config('database.connections.mysql');
if (! empty($original['url']) || ! is_string($original['database'] ?? null)) {
    throw new LogicException('QA memerlukan koneksi MySQL bernama tanpa URL override.');
}

$server = DB::connection('mysql')->getPdo();
$baseDatabase = DB::selectOne('SELECT DATABASE() AS name')->name ?? null;
if ($baseDatabase !== $original['database']) {
    throw new LogicException('Database koneksi dasar tidak sesuai konfigurasi aktif.');
}

$worker = ($argv[1] ?? '') === '--worker';
$input = $worker ? json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR) : [];
$name = $worker ? ($input['database'] ?? '') : 'pos_purchase_returns_qa_'.bin2hex(random_bytes(8));
if (! preg_match('/^pos_purchase_returns_qa_[a-f0-9]{16}$/D', $name) || $name === $baseDatabase) {
    throw new LogicException('Nama database QA tidak aman.');
}

$created = false;
$workers = [];
if ($worker) {
    config(['database.connections.purchase_returns_qa' => array_replace($original, ['database' => $name, 'url' => null]),
        'database.default' => 'purchase_returns_qa', 'cache.default' => 'array']);
    DB::purge('purchase_returns_qa');
    DB::setDefaultConnection('purchase_returns_qa');
    $app->detectEnvironment(fn () => 'testing');
    echo "READY\n";
    flush();
    try {
        app(PostPurchaseReturnDraft::class)->handle($input['id'], 1);
        echo json_encode(['status' => 'posted'], JSON_THROW_ON_ERROR);
        exit(0);
    } catch (ValidationException) {
        echo json_encode(['status' => 'rejected'], JSON_THROW_ON_ERROR);
        exit(2);
    } catch (Throwable) {
        echo json_encode(['status' => 'error'], JSON_THROW_ON_ERROR);
        exit(3);
    }
}
$check = static function (bool $ok, string $label): void {
    if (! $ok) {
        throw new RuntimeException('FAIL: '.$label);
    }

    echo 'PASS: '.$label.PHP_EOL;
};
$jsonHash = static fn ($value): string => hash('sha256', json_encode($value, JSON_THROW_ON_ERROR));
$ledgerTables = ['purchase_orders', 'purchase_order_lines', 'stock_receipts', 'stock_receipt_lines',
    'stock_balances', 'stock_movements', 'fifo_layers', 'fifo_consumptions', 'payment_disbursements',
    'payment_disbursement_lines', 'payment_receipts', 'payment_receipt_lines'];
$ledger = static function () use ($ledgerTables, $jsonHash): string {
    $rows = [];
    foreach ($ledgerTables as $table) {
        if (Schema::hasTable($table)) {
            $rows[$table] = DB::table($table)->orderBy('id')->get()->all();
        }
    }

    return $jsonHash($rows);
};
$sourceAudit = static fn (): string => $jsonHash(Activity::query()->where('log_name', '!=', 'purchasing.returns')->orderBy('id')->get()->all());

try {
    $server->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $created = true;
    $connection = array_replace($original, ['database' => $name, 'url' => null]);
    config(['database.connections.purchase_returns_qa' => $connection,
        'database.connections.purchase_returns_peer' => $connection, 'database.default' => 'purchase_returns_qa',
        'cache.default' => 'array']);
    DB::purge('purchase_returns_qa');
    DB::purge('purchase_returns_peer');
    DB::setDefaultConnection('purchase_returns_qa');
    $check(DB::selectOne('SELECT DATABASE() AS name')->name === $name, 'Koneksi aktif diverifikasi sebagai database disposable');
    $app->detectEnvironment(fn () => 'testing');
    $check(Artisan::call('migrate', ['--force' => true]) === 0, 'Seluruh migration native MySQL lulus');

    foreach ([UnitsDemoSeeder::class, WarehousesDemoSeeder::class, ItemsDemoSeeder::class,
        SuppliersDemoSeeder::class, PurchaseOrdersAccessSeeder::class, PurchaseOrdersDemoSeeder::class,
        PurchaseOrdersReceiptDemoSeeder::class, ReturnsAccessSeeder::class] as $seeder) {
        app($seeder)->run();
    }

    $columns = DB::select("SELECT COLUMN_NAME AS column_name, DATA_TYPE AS data_type, NUMERIC_PRECISION AS numeric_precision, NUMERIC_SCALE AS numeric_scale FROM information_schema.columns WHERE table_schema = ? AND table_name = 'purchase_return_lines' AND column_name IN ('quantity', 'base_quantity') ORDER BY column_name", [$name]);
    $check(count($columns) === 2 && collect($columns)->every(fn ($column) => $column->data_type === 'decimal'
        && (int) $column->numeric_precision === 20 && (int) $column->numeric_scale === 6), 'Kolom kuantitas native DECIMAL(20,6)');

    $migration = require app_path('Modules/Purchasing/Returns/Database/Migrations/2026_10_19_000003_create_purchase_return_drafts.php');
    $postingMigration = require app_path('Modules/Purchasing/Returns/Database/Migrations/2026_10_21_000001_add_purchase_return_posting.php');
    $postingMigration->down();
    $migration->down();
    $check(! Schema::hasTable('purchase_returns') && Schema::hasTable('purchase_orders'), 'Down kosong hanya menghapus tabel Returns');
    $migration->up();
    $postingMigration->up();
    $check(Schema::hasTable('purchase_return_lines') && Schema::hasColumn('purchase_returns', 'posted_from_version'),
        'Up ulang migration kosong beserta kolom posting berhasil');

    $purchaseId = DB::table('purchase_orders')->where('demo_key', 'cctv-electrical-purchase-v1')->where('status', 'posted')->value('id');
    $source = app(ReturnPurchaseLookup::class)->findEligible((string) $purchaseId);
    if ($source === null || $source->lines === []) {
        throw new RuntimeException('Sumber pembelian demo tidak eligible untuk QA.');
    }
    $sourceLine = $source->lines[0];
    $payload = ['purchase_order_id' => $purchaseId, 'notes' => 'Native QA draft',
        'lines' => [['purchase_order_line_id' => $sourceLine['id'], 'quantity' => '1']]];
    $beforeLedger = $ledger();
    $beforeSourceAudit = $sourceAudit();
    $action = app(ManagePurchaseReturnDraft::class);
    $id = $action->save($payload);
    $row = DB::table('purchase_return_lines')->where('purchase_return_id', $id)->first();
    $snapshot = json_decode($row->snapshot, true, flags: JSON_THROW_ON_ERROR);
    $check($row->quantity === '1.000000' && $row->base_quantity === $sourceLine['conversion_ratio']
        && $snapshot['unit_cost'] === $sourceLine['unit_cost'], 'Simpan native mempertahankan kuantitas, base dan harga snapshot exact');

    $action->save(array_replace($payload, ['version' => 1, 'notes' => 'Native QA updated']), $id);
    $auditEvents = static fn (string $draftId): array => Activity::query()->where('log_name', 'purchasing.returns')
        ->where('subject_id', $draftId)->orderBy('id')->pluck('event')->all();
    $check((int) DB::table('purchase_returns')->where('id', $id)->value('version') === 2
        && $auditEvents($id) === ['created', 'updated'], 'Update menaikkan revision dan audit native atomik');
    $noOp = ['purchase_order_id' => $purchaseId, 'notes' => 'Native QA updated', 'version' => 2,
        'lines' => [['purchase_order_line_id' => $sourceLine['id'], 'quantity' => '1.000000']]];
    $action->save($noOp, $id);
    $check((int) DB::table('purchase_returns')->where('id', $id)->value('version') === 2
        && $auditEvents($id) === ['created', 'updated'], 'No-op tidak menambah revision/audit native');
    $action->delete($id, 2);
    $action->restore($id, 3);
    $check((int) DB::table('purchase_returns')->where('id', $id)->value('version') === 4
        && $auditEvents($id) === ['created', 'updated', 'deleted', 'restored'], 'Delete/restore native mempertahankan audit dan revision');

    $staleId = $action->save($payload + ['notes' => 'Native QA stale']);
    $peer = DB::connection('purchase_returns_peer');
    DB::statement('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    $staleRejected = false;
    DB::beginTransaction();
    try {
        DB::table('purchase_returns')->where('id', $staleId)->first();
        $peer->table('purchase_returns')->where('id', $staleId)->update(['version' => 2, 'notes' => 'Peer update']);
        try {
            $action->save($payload + ['version' => 1, 'notes' => 'Stale write'], $staleId);
        } catch (ValidationException) {
            $staleRejected = true;
        }
    } finally {
        DB::rollBack();
    }
    $check($staleRejected && (int) DB::table('purchase_returns')->where('id', $staleId)->value('version') === 2,
        'Current read FOR UPDATE menolak revision stale sesudah snapshot REPEATABLE READ');

    $countBeforeVeto = DB::table('purchase_returns')->count();
    Activity::creating(fn ($activity) => $activity->log_name === 'purchasing.returns' ? false : null);
    $auditRejected = false;
    try {
        $action->save($payload + ['notes' => 'Audit veto QA']);
    } catch (RuntimeException) {
        $auditRejected = true;
    } finally {
        Activity::flushEventListeners();
    }
    $check($auditRejected && DB::table('purchase_returns')->count() === $countBeforeVeto,
        'Audit veto me-rollback header/baris create native');

    $copy = (array) $row;
    $copy['id'] = strtolower((string) Str::ulid());
    $duplicateRejected = false;
    try {
        DB::transaction(fn () => DB::table('purchase_return_lines')->insert($copy));
    } catch (UniqueConstraintViolationException) {
        $duplicateRejected = true;
    }
    $check($duplicateRejected, 'Unique source line per draft ditegakkan native');

    $foreignKeyRejected = false;
    try {
        DB::transaction(fn () => DB::table('purchase_order_lines')->where('id', $sourceLine['id'])->delete());
    } catch (QueryException $error) {
        if (($error->errorInfo[1] ?? null) !== 1451) {
            throw $error;
        }
        $foreignKeyRejected = true;
    }
    $check($foreignKeyRejected, 'Foreign key RESTRICT menjaga source pembelian native');

    $downRejected = false;
    try {
        $migration->down();
    } catch (LogicException) {
        $downRejected = true;
    }
    $check($downRejected && Schema::hasTable('purchase_return_lines'), 'Down migration menolak draft populated');
    $check($beforeLedger === $ledger() && $beforeSourceAudit === $sourceAudit(),
        'Draft dan lifecycle tidak mengubah PO/receipt/Stock/FIFO/Finance atau audit asal');

    $balanceBefore = DB::table('stock_balances')->where('warehouse_id', $source->header['warehouse_id'])
        ->where('item_id', $sourceLine['item_id'])->sole();
    $receiptLayer = DB::table('fifo_layers as layer')
        ->join('stock_movements as movement', 'movement.id', '=', 'layer.movement_id')
        ->join('stock_receipt_lines as receipt_line', 'receipt_line.id', '=', 'movement.receipt_line_id')
        ->where('receipt_line.source_line_id', $sourceLine['id'])->sole(['layer.id', 'layer.remaining_quantity']);
    $result = app(PostPurchaseReturnDraft::class)->handle($id, 4);
    $issueLine = DB::table('stock_issue_lines')->where('issue_id', $result['stock_issue_id'])->sole();
    $consumption = DB::table('fifo_consumptions as consumption')
        ->join('stock_movements as movement', 'movement.id', '=', 'consumption.movement_id')
        ->where('movement.issue_line_id', $issueLine->id)->sole(['consumption.fifo_layer_id']);
    $balanceAfter = DB::table('stock_balances')->where('warehouse_id', $source->header['warehouse_id'])
        ->where('item_id', $sourceLine['item_id'])->sole();
    $check($result['status'] === 'posted' && $consumption->fifo_layer_id === $receiptLayer->id
        && DB::table('stock_issues')->where('id', $result['stock_issue_id'])->value('source_type') === 'purchase_return'
        && BigDecimal::of($balanceBefore->quantity)->minus($balanceAfter->quantity)->isEqualTo($row->base_quantity),
        'Posting native mengurangi saldo dan hanya mengonsumsi layer penerimaan asal');
    $issueCount = DB::table('stock_issues')->count();
    $movementCount = DB::table('stock_movements')->count();
    $check(app(PostPurchaseReturnDraft::class)->handle($id, 4) === $result
        && DB::table('stock_issues')->count() === $issueCount && DB::table('stock_movements')->count() === $movementCount,
        'Replay posting native tidak menggandakan ledger');
    $overId = $action->save(['purchase_order_id' => $purchaseId,
        'lines' => [['purchase_order_line_id' => $sourceLine['id'], 'quantity' => $sourceLine['quantity']]]]);
    $overRejected = false;
    try {
        app(PostPurchaseReturnDraft::class)->handle($overId, 1);
    } catch (ValidationException) {
        $overRejected = true;
    }
    $check($overRejected && DB::table('purchase_returns')->where('id', $overId)->value('status') === 'draft'
        && DB::table('stock_issues')->count() === $issueCount,
        'Posting native menolak penggantian dengan layer lain saat sumber tidak cukup');
    $draft = static fn (): string => $action->save(['purchase_order_id' => $purchaseId,
        'lines' => [['purchase_order_line_id' => $sourceLine['id'], 'quantity' => '1']]]);
    $contenders = [$draft(), $draft()];
    DB::beginTransaction();
    DB::table('warehouses')->where('id', $source->header['warehouse_id'])->lockForUpdate()->first();
    foreach ($contenders as $contender) {
        $pipes = [];
        $process = proc_open([PHP_BINARY, __FILE__, '--worker'],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes,
            dirname(__DIR__, 2), options: ['bypass_shell' => true]);
        if (! is_resource($process)) {
            throw new RuntimeException('Worker native tidak dapat dimulai.');
        }
        fwrite($pipes[0], json_encode(['database' => $name, 'id' => $contender], JSON_THROW_ON_ERROR));
        fclose($pipes[0]);
        stream_set_timeout($pipes[1], 45);
        stream_set_timeout($pipes[2], 45);
        $workers[] = ['process' => $process, 'pipes' => $pipes, 'closed' => false];
    }
    foreach ($workers as $entry) {
        $check(trim((string) fgets($entry['pipes'][1])) === 'READY', 'Worker posting independen siap');
    }
    DB::commit();
    $statuses = [];
    foreach ($workers as $index => $entry) {
        $out = stream_get_contents($entry['pipes'][1]);
        $err = stream_get_contents($entry['pipes'][2]);
        fclose($entry['pipes'][1]);
        fclose($entry['pipes'][2]);
        $code = proc_close($entry['process']);
        $workers[$index]['closed'] = true;
        $check(in_array($code, [0, 2], true) && $err === '', 'Worker native selesai tanpa error lain');
        $statuses[] = json_decode($out, true, flags: JSON_THROW_ON_ERROR)['status'];
    }
    sort($statuses);
    $check($statuses === ['posted', 'rejected']
        && DB::table('purchase_returns')->whereIn('id', $contenders)->where('status', 'posted')->count() === 1,
        'Dua proses bersaing: satu menang dan satu ditolak dari layer asal');
} finally {
    if (DB::transactionLevel() > 0) {
        DB::rollBack();
    }
    foreach ($workers as $entry) {
        if (! $entry['closed']) {
            proc_terminate($entry['process']);
            foreach ([1, 2] as $pipe) {
                if (is_resource($entry['pipes'][$pipe])) {
                    fclose($entry['pipes'][$pipe]);
                }
            }
            proc_close($entry['process']);
        }
    }
    DB::purge('purchase_returns_peer');
    DB::purge('purchase_returns_qa');
    if ($created) {
        $server->exec('DROP DATABASE `'.$name.'`');
        echo 'PASS: database native QA dihapus; database bisnis tidak dimutasi.'.PHP_EOL;
    }
}
