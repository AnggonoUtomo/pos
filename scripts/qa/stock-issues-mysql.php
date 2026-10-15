<?php

use App\Modules\Inventory\Items\Application\Contracts\ItemStockLookup;
use App\Modules\Inventory\Items\Database\Seeders\ItemsDemoSeeder;
use App\Modules\Inventory\Stock\Application\Actions\IssueStock;
use App\Modules\Inventory\Stock\Application\Actions\ManageOpening;
use App\Modules\Inventory\Stock\Application\Actions\PostOpening;
use App\Modules\Inventory\Stock\Application\Contracts\StockRepository;
use App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder;
use App\Modules\Inventory\Warehouses\Application\Contracts\WarehouseLookup;
use App\Modules\Inventory\Warehouses\Database\Seeders\WarehousesDemoSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('local') || config('database.default') !== 'mysql') {
    throw new LogicException('QA hanya MySQL local dengan database disposable baru.');
}
$original = config('database.connections.mysql');
if (($argv[1] ?? null) === '--worker') {
    $input = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
    if (! preg_match('/^pos_stock_issues_qa_[a-f0-9]{16}$/D', $input['database'] ?? '') || $input['database'] === $original['database']) {
        throw new LogicException('Database worker tidak aman.');
    }
    config(['database.connections.issue_worker' => array_replace($original, ['database' => $input['database']])]);
    DB::setDefaultConnection('issue_worker');
    $app->detectEnvironment(fn () => 'testing');
    echo json_encode(['thread' => DB::selectOne('SELECT CONNECTION_ID() AS id')->id])."\n";
    flush();
    try {
        app(IssueStock::class)->handle($input['issue']);
        echo "ISSUED\n";
    } catch (InvalidArgumentException $exception) {
        echo str_contains($exception->getMessage(), 'Stok gudang tidak mencukupi') ? "REJECTED\n" : "UNEXPECTED\n";
    }
    exit;
}
$name = 'pos_stock_issues_qa_'.bin2hex(random_bytes(8));
if (! preg_match('/^pos_stock_issues_qa_[a-f0-9]{16}$/D', $name) || $name === $original['database']) {
    throw new LogicException('Nama database tidak aman.');
}
$server = DB::connection('mysql')->getPdo();
$created = false;
$worker = null;
$pipes = [];
$failure = null;
$check = static function (bool $ok, string $label): void {
    if (! $ok) {
        throw new RuntimeException($label);
    }
    echo 'PASS: '.$label."\n";
};
try {
    $server->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $created = true;
    $connection = array_replace($original, ['database' => $name]);
    config(['database.connections.issue_qa' => $connection, 'database.connections.issue_peer' => $connection]);
    DB::setDefaultConnection('issue_qa');
    $app->detectEnvironment(fn () => 'testing');
    if (Artisan::call('migrate', ['--force' => true]) !== 0) {
        throw new RuntimeException('Migration QA gagal.');
    }
    foreach ([WarehousesDemoSeeder::class, UnitsDemoSeeder::class, ItemsDemoSeeder::class] as $seeder) {
        app($seeder)->run();
    }
    $warehouse = app(WarehouseLookup::class)->choices()[0];
    $item = app(ItemStockLookup::class)->choices()[0];
    $opening = app(ManageOpening::class)->create(['warehouse_id' => $warehouse->id, 'lines' => [
        ['item_id' => $item->id, 'unit_id' => $item->baseUnitId, 'quantity' => '24', 'unit_cost' => '1'],
    ]]);
    app(PostOpening::class)->handle($opening);
    $migration = require app_path('Modules/Inventory/Stock/Database/Migrations/2026_10_16_000001_add_stock_issues.php');
    $migration->down();
    $columns = array_keys((array) DB::table('stock_movements')->first());
    $before = DB::table('stock_movements')->select($columns)->get()->toJson();
    $migration->up();
    $check($before === DB::table('stock_movements')->select($columns)->get()->toJson(), 'MySQL native alter up/down mempertahankan opening existing');
    $issue = static function (string $quantity) use ($warehouse, $item): array {
        $id = strtolower((string) Str::ulid());

        return ['warehouse_id' => $warehouse->id, 'source_id' => $id, 'source_number' => 'POS-'.$id, 'lines' => [
            ['source_line_id' => strtolower((string) Str::ulid()), 'item_id' => $item->id, 'base_quantity' => $quantity],
        ]];
    };
    $peerIssue = static function (array $input): array {
        DB::setDefaultConnection('issue_peer');
        try {
            return app(IssueStock::class)->handle($input);
        } finally {
            DB::setDefaultConnection('issue_qa');
        }
    };
    DB::statement('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    DB::connection('issue_peer')->statement('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    DB::beginTransaction();
    try {
        DB::table('stock_balances')->first();
        $peerIssue($issue('23'));
        $rejected = false;
        try {
            app(IssueStock::class)->handle($issue('2'));
        } catch (InvalidArgumentException) {
            $rejected = true;
        }
        $check($rejected, 'Snapshot lama 24: current read melihat sisa 1 dan menolak issue 2');
    } finally {
        DB::rollBack();
    }
    $replay = $issue('0.25');
    DB::beginTransaction();
    try {
        DB::table('stock_issues')->where('source_id', $replay['source_id'])->first();
        $peer = $peerIssue($replay);
        $main = app(IssueStock::class)->handle($replay);
        $check($main['id'] === $peer['id'] && count($main['lines']) === 1, 'Replay header/lines current read sesudah snapshot kosong; tidak menggandakan stok');
    } finally {
        DB::rollBack();
    }
    app(IssueStock::class)->handle($issue('0.5'));
    $check(DB::table('stock_balances')->value('quantity') === '0.250000', 'Saldo dan nilai MySQL exact sesudah issue pecahan');
    DB::beginTransaction();
    try {
        app(WarehouseLookup::class)->findActive($warehouse->id, lock: true);
        $worker = proc_open([PHP_BINARY, __FILE__, '--worker'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path(), null, ['bypass_shell' => true]);
        if (! is_resource($worker)) {
            throw new RuntimeException('Worker QA gagal dibuat.');
        }
        fwrite($pipes[0], json_encode(['database' => $name, 'issue' => $issue('0.25')], JSON_THROW_ON_ERROR));
        fclose($pipes[0]);
        stream_set_timeout($pipes[1], 15);
        $ready = json_decode(trim((string) fgets($pipes[1])), true, flags: JSON_THROW_ON_ERROR);
        $waiting = false;
        for ($i = 0; $i < 60; $i++) {
            $state = DB::connection('mysql')->selectOne('SELECT trx_state FROM information_schema.innodb_trx WHERE trx_mysql_thread_id = ?', [$ready['thread']]);
            if (($state->trx_state ?? null) === 'LOCK WAIT') {
                $waiting = true;
                break;
            }
            usleep(100000);
        }
        $check($waiting, 'Worker simultan terbukti LOCK WAIT pada gudang yang dipegang transaksi utama');
        app(IssueStock::class)->handle($issue('0.25'));
        DB::commit();
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
    }
    $outcome = trim((string) stream_get_contents($pipes[1]));
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exit = proc_close($worker);
    $worker = null;
    $check($exit === 0 && $outcome === 'REJECTED' && DB::table('stock_balances')->value('quantity') === '0.000000', 'Stok terakhir hanya dikonsumsi sekali; worker ditolak sesudah lock dilepas');
    $check(DB::table('stock_issues')->count() === 4 && DB::table('fifo_consumptions')->count() === 4
        && DB::table('activity_log')->where('event', 'issued')->count() === 4, 'Tidak ada header/movement/alokasi/audit dari checkout kalah atau retry');
    $restricted = false;
    try {
        DB::table('fifo_layers')->delete();
    } catch (QueryException $exception) {
        if (($exception->errorInfo[1] ?? null) !== 1451) {
            throw $exception;
        } $restricted = true;
    }
    $check($restricted, 'FK RESTRICT melindungi layer yang sudah dikonsumsi');
    $outside = false;
    try {
        app(StockRepository::class)->lockIssueInventory($warehouse->id, $item->id);
    } catch (LogicException) {
        $outside = true;
    }
    $check($outside, 'Port locking menolak pemanggilan tanpa transaction default');
} catch (Throwable $exception) {
    $failure = $exception;
} finally {
    if (is_resource($worker)) {
        proc_terminate($worker);
        foreach ($pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        } proc_close($worker);
    }
    DB::purge('issue_peer');
    DB::purge('issue_qa');
    if ($created) {
        $server->exec('DROP DATABASE `'.$name.'`');
        echo "PASS: database disposable dibersihkan; database pengguna tidak dimutasi.\n";
    }
}
if ($failure !== null) {
    fwrite(STDERR, 'FAIL: '.get_class($failure).' pada '.basename($failure->getFile()).':'.$failure->getLine()."\n");
    exit(1);
}
