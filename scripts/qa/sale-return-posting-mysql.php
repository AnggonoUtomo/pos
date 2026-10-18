<?php

use App\Modules\Inventory\Stock\Application\Actions\IssueStock;
use App\Modules\Inventory\Stock\Application\Actions\ManageOpening;
use App\Modules\Inventory\Stock\Application\Actions\PostOpening;
use App\Modules\Inventory\Stock\Application\Contracts\SaleReturnStockWriter;
use App\Modules\Inventory\Stock\Application\DTOs\SaleReturnStockRequest;
use App\Modules\Sales\Returns\Application\Actions\ManageReturnDraft;
use App\Modules\Sales\Returns\Application\Actions\PostReturnDraft;
use Brick\Math\BigDecimal;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;
use Tests\Fixtures\SalesReturnFixture;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('local') || config('database.default') !== 'mysql') {
    throw new LogicException('QA hanya local/MySQL disposable.');
}
$original = config('database.connections.mysql');
$worker = ($argv[1] ?? '') === '--worker';
$input = $worker ? json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR) : [];
$name = $worker ? ($input['database'] ?? '') : 'pos_return_post_qa_'.bin2hex(random_bytes(8));
if (! is_string($name) || ! preg_match('/^pos_return_post_qa_[a-f0-9]{16}$/D', $name) || $name === $original['database']) {
    throw new LogicException('Target DB tidak aman.');
}
config(['database.connections.return_post_qa' => array_replace($original, ['database' => $name]), 'cache.default' => 'array']);
if ($worker) {
    DB::setDefaultConnection('return_post_qa');
    $app->detectEnvironment(fn () => 'testing');
    echo "READY\n";
    flush();
    try {
        app(PostReturnDraft::class)->handle($input['id'], 1);
        echo json_encode(['status' => 'posted']);
        exit(0);
    } catch (ValidationException) {
        echo json_encode(['status' => 'rejected']);
        exit(2);
    } catch (Throwable) {
        echo json_encode(['status' => 'error']);
        exit(3);
    }
}
$server = DB::connection('mysql')->getPdo();
$created = false;
$workers = [];
$check = static function (bool $ok, string $label): void {
    if (! $ok) {
        throw new RuntimeException($label);
    } echo "PASS: {$label}\n";
};
try {
    $server->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $created = true;
    DB::setDefaultConnection('return_post_qa');
    $app->detectEnvironment(fn () => 'testing');
    $check(Artisan::call('migrate', ['--force' => true]) === 0, 'Native schema migration');
    $stockMigration = require __DIR__.'/../../app/Modules/Inventory/Stock/Database/Migrations/2026_10_18_000002_create_stock_return_allocations.php';
    $returnMigration = require __DIR__.'/../../app/Modules/Sales/Returns/Database/Migrations/2026_10_18_000003_add_sales_return_posting.php';
    $returnMigration->down();
    $stockMigration->down();
    $stockMigration->up();
    $returnMigration->up();
    $check(true, 'Native down/up kosong reversible');
    $invoice = SalesReturnFixture::seed('BOX', '1');
    $source = SalesReturnFixture::sourceFingerprint();
    $draft = static fn (string $q) => app(ManageReturnDraft::class)->save(['invoice_id' => $invoice['id'],
        'lines' => [['invoice_line_id' => $invoice['lines'][0]['id'], 'quantity' => $q]]]);
    $first = $draft('0.5');
    $result = app(PostReturnDraft::class)->handle($first, 1);
    $line = DB::table('stock_receipt_lines')->where('receipt_id', $result['stock_receipt_id'])->sole();
    $check($line->quantity === '2.000000' && $line->total_value === '1000000.000000', 'BOX ke base dan cost FIFO asal exact');
    $before = SalesReturnFixture::fingerprint();
    $audit = Activity::count();
    $check($result === app(PostReturnDraft::class)->handle($first, 1) && $before === SalesReturnFixture::fingerprint() && Activity::count() === $audit, 'Replay native tanpa stock/audit tambahan');
    $ids = [$draft('0.375'), $draft('0.375')];
    DB::beginTransaction();
    DB::table('warehouses')->where('id', $invoice['warehouse_id'])->lockForUpdate()->first();
    foreach ($ids as $id) {
        $process = proc_open([PHP_BINARY, __FILE__, '--worker'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__, 2), options: ['bypass_shell' => true]);
        if (! is_resource($process)) {
            throw new RuntimeException('Worker native tidak dapat dimulai.');
        }
        fwrite($pipes[0], json_encode(['database' => $name, 'id' => $id], JSON_THROW_ON_ERROR));
        fclose($pipes[0]);
        stream_set_timeout($pipes[1], 45);
        stream_set_timeout($pipes[2], 45);
        $workers[] = ['process' => $process, 'pipes' => $pipes, 'closed' => false];
    }
    foreach ($workers as $entry) {
        $check(trim((string) fgets($entry['pipes'][1])) === 'READY', 'Worker posting independen siap pada gudang terkunci');
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
        $check(in_array($code, [0, 2], true) && $err === '', 'Worker native selesai tanpa unexpected error');
        $statuses[] = json_decode($out, true, flags: JSON_THROW_ON_ERROR)['status'];
    }
    sort($statuses);
    $check($statuses === ['posted', 'rejected'], 'Dua proses bersaing: tepat satu menang, kumulatif tidak melebihi terjual');
    $last = $draft('0.125');
    $before = SalesReturnFixture::fingerprint();
    Activity::saving(fn ($log) => $log->log_name === 'sales.returns' && $log->event === 'posted' ? false : null);
    try {
        app(PostReturnDraft::class)->handle($last, 1);
        throw new LogicException('Audit veto lolos.');
    } catch (RuntimeException) {
        $check($before === SalesReturnFixture::fingerprint() && DB::table('sales_returns')->where('id', $last)->value('status') === 'draft', 'Native Returns audit veto rollback receipt/trace/balance/layer');
    } finally {
        Activity::flushEventListeners();
    }
    app(PostReturnDraft::class)->handle($last, 1);
    $q = $v = BigDecimal::zero()->toScale(6);
    foreach (DB::table('stock_return_allocations')->get() as $row) {
        $q = $q->plus($row->quantity);
        $v = $v->plus($row->total_value);
    }
    $check((string) $q === '4.000000' && (string) $v === '2000000.000000', 'Seluruh source quantity dan HPP kembali exact termasuk residual');
    $check(SalesReturnFixture::sourceFingerprint() === $source, 'Invoice/sale consumption/payment asal immutable');
    foreach ([$stockMigration, $returnMigration] as $migration) {
        try {
            $migration->down();
            throw new RuntimeException('Rollback posted lolos.');
        } catch (LogicException) {
            $check(true, 'Rollback migration menolak histori posted');
        }
    }
    $item = DB::table('items')->where('sku', 'DEMO-CCTV-NVR-8CH')->sole();
    $warehouse = $invoice['warehouse_id'];
    $opening = app(ManageOpening::class)->create(['warehouse_id' => $warehouse,
        'lines' => [['item_id' => $item->id, 'unit_id' => $item->base_unit_id, 'quantity' => '3', 'unit_cost' => '0.000001']]]);
    app(PostOpening::class)->handle($opening);
    $microInvoice = strtolower((string) Str::ulid());
    $microLine = strtolower((string) Str::ulid());
    app(IssueStock::class)->handle(['source_id' => $microInvoice, 'source_number' => 'POS-MICRO', 'warehouse_id' => $warehouse,
        'lines' => [['source_line_id' => $microLine, 'item_id' => $item->id, 'base_quantity' => '0.000001']]]);
    $micro = DB::transaction(fn () => app(SaleReturnStockWriter::class)->receive(new SaleReturnStockRequest($warehouse, strtolower((string) Str::ulid()), 'SR-MICRO', $microInvoice,
        [['source_line_id' => strtolower((string) Str::ulid()), 'sale_line_id' => $microLine, 'base_quantity' => '0.000001']])));
    $zero = DB::table('stock_receipt_lines')->where('receipt_id', $micro->id)->sole();
    $resale = app(IssueStock::class)->handle(['source_id' => strtolower((string) Str::ulid()), 'source_number' => 'POS-RESALE', 'warehouse_id' => $warehouse,
        'lines' => [['source_line_id' => strtolower((string) Str::ulid()), 'item_id' => $item->id, 'base_quantity' => '3']]]);
    $check($zero->total_value === '0.000000' && $resale['lines'][0]['total_value'] === '0.000003', 'Native biaya nol dapat diretur/dijual ulang dan residual terjaga');
} finally {
    foreach ($workers as $entry) {
        if (! $entry['closed']) {
            proc_terminate($entry['process']);
            foreach ([1, 2] as $pipe) {
                if (is_resource($entry['pipes'][$pipe])) {
                    fclose($entry['pipes'][$pipe]);
                }
            } proc_close($entry['process']);
        }
    }
    while (DB::connection('return_post_qa')->transactionLevel() > 0) {
        DB::connection('return_post_qa')->rollBack();
    }
    DB::purge('return_post_qa');
    if ($created) {
        $server->exec('DROP DATABASE `'.$name.'`');
        echo "PASS: DB disposable dibersihkan, DB user tidak dimutasi.\n";
    }
}
