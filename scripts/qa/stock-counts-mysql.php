<?php

use App\Modules\Inventory\Stock\Application\Actions\ManageStockCount;
use App\Modules\Inventory\Stock\Application\Actions\PostAdjustment;
use App\Modules\Inventory\Stock\Application\Actions\PostStockCount;
use App\Modules\Inventory\Stock\Application\Contracts\StockCountRepository;
use Brick\Math\BigDecimal;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\Fixtures\SalesReturnFixture;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
set_exception_handler(static function (Throwable $error): never {
    fwrite(STDERR, 'FAIL opname MySQL: '.class_basename($error).' pada '.basename($error->getFile()).':'.$error->getLine().PHP_EOL);
    exit(1);
});
if (DB::connection()->getDriverName() !== 'mysql' || ! in_array(app()->environment(), ['local', 'production'], true)) {
    throw new LogicException('QA memerlukan konfigurasi MySQL lokal.');
}
$original = config('database.connections.mysql');
$valid = static fn ($name) => is_string($name) && preg_match('/^pos_count_qa_[a-f0-9]{16}$/D', $name) && $name !== $original['database'];
$connect = static function (string $name) use ($original, $app): void {
    config(['database.connections.count_qa' => array_replace($original, ['database' => $name, 'url' => null]), 'cache.default' => 'array']);
    DB::setDefaultConnection('count_qa');
    if (DB::selectOne('SELECT DATABASE() AS db')->db !== $name) {
        throw new LogicException('Koneksi bukan database QA.');
    }
    $app->detectEnvironment(fn () => 'testing');
};
if (($argv[1] ?? '') === '--worker') {
    $input = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
    if (! $valid($input['database'] ?? null)) {
        throw new LogicException('Database worker tidak aman.');
    }
    $connect($input['database']);
    echo json_encode(['thread' => DB::selectOne('SELECT CONNECTION_ID() AS id')->id])."\n";
    flush();
    try {
        if ($input['action'] === 'adjust') {
            app(PostAdjustment::class)->handle($input['body'], $input['actor']);
        } elseif ($input['action'] === 'start') {
            app(ManageStockCount::class)->start($input['warehouse'], 'QA native', $input['actor']);
        } else {
            app(PostStockCount::class)->handle($input['count'], $input['version'], 'QA native', [], $input['actor']);
        }
        echo "{\"status\":\"accepted\"}\n";
    } catch (InvalidArgumentException|ValidationException) {
        echo "{\"status\":\"rejected\"}\n";
    }
    exit;
}
$name = 'pos_count_qa_'.bin2hex(random_bytes(8));
if (! $valid($name)) {
    throw new LogicException('Database QA tidak aman.');
}
$server = DB::connection('mysql')->getPdo();
$workers = [];
$check = static function (bool $ok, string $label): void {
    if (! $ok) {
        throw new RuntimeException($label);
    }
    echo 'PASS: '.$label.PHP_EOL;
};
$startWorker = static function (array $input) use (&$workers, $name): int {
    $process = proc_open([PHP_BINARY, __FILE__, '--worker'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path(), null, ['bypass_shell' => true]);
    if (! is_resource($process)) {
        throw new RuntimeException('Worker gagal.');
    }
    fwrite($pipes[0], json_encode(['database' => $name] + $input, JSON_THROW_ON_ERROR));
    fclose($pipes[0]);
    stream_set_timeout($pipes[1], 30);
    $thread = json_decode(trim((string) fgets($pipes[1])), true, flags: JSON_THROW_ON_ERROR)['thread'];
    $workers[] = ['process' => $process, 'pipes' => $pipes, 'thread' => $thread];

    return array_key_last($workers);
};
$wait = static function (int $index) use (&$workers): void {
    for ($i = 0; $i < 100; $i++) {
        $state = DB::connection('mysql')->selectOne('SELECT trx_state FROM information_schema.innodb_trx WHERE trx_mysql_thread_id = ?', [$workers[$index]['thread']]);
        if (($state->trx_state ?? null) === 'LOCK WAIT') {
            return;
        }
        usleep(100000);
    }
    throw new RuntimeException('Worker tidak terbukti LOCK WAIT.');
};
$finish = static function (int $index) use (&$workers): string {
    $worker = $workers[$index];
    $out = stream_get_contents($worker['pipes'][1]);
    $meta = stream_get_meta_data($worker['pipes'][1]);
    fclose($worker['pipes'][1]);
    fclose($worker['pipes'][2]);
    $code = proc_close($worker['process']);
    unset($workers[$index]);
    if ($code !== 0 || $meta['timed_out']) {
        throw new RuntimeException('Worker gagal/timeout.');
    }

    return json_decode(trim($out), true, flags: JSON_THROW_ON_ERROR)['status'];
};
$created = false;
try {
    $server->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $created = true;
    $connect($name);
    $check(Artisan::call('migrate', ['--force' => true]) === 0, 'Schema native lengkap');
    $source = SalesReturnFixture::seed();
    $warehouse = $source['warehouse_id'];
    $actor = (int) DB::table('users')->min('id');
    $item = $source['lines'][0]['item_id'];
    $input = ['idempotency_key' => strtolower((string) Str::ulid()), 'warehouse_id' => $warehouse,
        'item_id' => $item, 'direction' => 'in', 'quantity' => '1', 'unit_cost' => '100', 'reason' => 'QA native'];
    DB::beginTransaction();
    $id = app(ManageStockCount::class)->start($warehouse, 'QA native', $actor);
    $worker = $startWorker(['action' => 'adjust', 'body' => $input, 'actor' => $actor]);
    $wait($worker);
    DB::commit();
    $check($finish($worker) === 'rejected', 'Start opname versus writer: LOCK WAIT lalu freeze current read');
    app(ManageStockCount::class)->cancel($id, 0, 'QA batal', $actor);
    DB::beginTransaction();
    app(PostAdjustment::class)->handle($input, $actor);
    $worker = $startWorker(['action' => 'start', 'warehouse' => $warehouse, 'actor' => $actor]);
    $wait($worker);
    DB::commit();
    $check($finish($worker) === 'accepted', 'Writer versus start: LOCK WAIT lalu snapshot saldo terbaru');
    $active = app(StockCountRepository::class)->active($warehouse);
    $record = app(StockCountRepository::class)->find($active['id']);
    $balances = DB::table('stock_balances')->where('warehouse_id', $warehouse)->pluck('quantity', 'item_id');
    $lines = [];
    foreach ($record['lines'] as $line) {
        $check($line['system_quantity'] === $balances[$line['item_id']], 'Snapshot decimal native exact');
        $quantity = BigDecimal::of($line['system_quantity']);
        if ($line['item_id'] === $item) {
            $quantity = $quantity->minus('0.000001');
        }
        $lines[] = ['id' => $line['id'], 'quantity' => (string) $quantity];
    }
    app(ManageStockCount::class)->save($record['id'], 0, $lines, $actor);
    app(ManageStockCount::class)->submit($record['id'], 1, $actor);
    DB::beginTransaction();
    app(PostStockCount::class)->handle($record['id'], 2, 'QA native', [], $actor);
    $worker = $startWorker(['action' => 'post', 'count' => $record['id'], 'version' => 2, 'actor' => $actor]);
    $wait($worker);
    DB::commit();
    $check($finish($worker) === 'accepted' && DB::table('stock_adjustments')->count() === 2, 'Posting versus retry: LOCK WAIT, replay tanpa koreksi ganda');
    $check(DB::table('stock_counts')->whereNotNull('active_warehouse_id')->count() === 0, 'Gudang dilepas hanya sesudah posting commit');
    $migration = require app_path('Modules/Inventory/Stock/Database/Migrations/2026_10_31_000001_create_stock_counts.php');
    try {
        $migration->down();
        throw new RuntimeException('Rollback harus ditolak.');
    } catch (LogicException) {
        $check(true, 'Rollback histori ditolak');
    }
} finally {
    while (DB::transactionLevel() > 0) {
        DB::rollBack();
    }
    foreach ($workers as $worker) {
        proc_terminate($worker['process']);
        foreach ($worker['pipes'] as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }
        proc_close($worker['process']);
    }
    DB::disconnect('count_qa');
    if ($created) {
        $server->exec('DROP DATABASE `'.$name.'`');
    }
}
