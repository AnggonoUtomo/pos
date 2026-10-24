<?php

use App\Modules\Inventory\Transfers\Application\Actions\ManageTransferDraft;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;
use Tests\Fixtures\TransferDraftFixture;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
set_exception_handler(static function (Throwable $error): never {
    fwrite(STDERR, 'FAIL: native refund pada '.basename($error->getFile()).':'.$error->getLine().PHP_EOL);
    exit(1);
});
if (! app()->environment('local') || config('database.default') !== 'mysql') {
    throw new LogicException('QA settlement hanya local/MySQL disposable.');
}
$original = config('database.connections.mysql');
$valid = static fn ($name) => is_string($name) && preg_match('/^pos_transfer_draft_qa_[a-f0-9]{16}$/D', $name) && $name !== $original['database'];
$connect = static function (string $name) use ($original, $app): void {
    config(['database.connections.return_refund_qa' => array_replace($original, ['database' => $name, 'url' => null]), 'cache.default' => 'array']);
    DB::setDefaultConnection('return_refund_qa');
    if (DB::selectOne('SELECT DATABASE() AS selected_database')->selected_database !== $name) {
        throw new LogicException('Database aktual bukan database disposable yang diminta.');
    }
    $app->detectEnvironment(fn () => 'testing');
};
if (($argv[1] ?? '') === '--worker') {
    try {
        $input = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
        if (! $valid($input['database'] ?? null)) {
            throw new LogicException('Nama database worker tidak aman.');
        }
        $connect($input['database']);
        echo json_encode(['thread' => DB::selectOne('SELECT CONNECTION_ID() AS id')->id])."\n";
        flush();
        $result = app(ManageTransferDraft::class)->save($input['body'], $input['id']);
        echo json_encode(['id' => $result])."\n";
        exit(0);
    } catch (ValidationException) {
        echo json_encode(['rejected' => true])."\n";
        exit(0);
    } catch (Throwable) {
        fwrite(STDERR, "FAIL: worker settlement gagal.\n");
        exit(1);
    }
}
$name = 'pos_transfer_draft_qa_'.bin2hex(random_bytes(8));
if (! $valid($name)) {
    throw new LogicException('Nama database tidak aman.');
}
$server = DB::connection('mysql')->getPdo();
$created = false;
$workers = [];
$check = static function (bool $ok, string $label): void {
    if (! $ok) {
        throw new RuntimeException($label);
    }
    echo "PASS: {$label}\n";
};
$start = static function (string $id, array $body) use (&$workers, $name): array {
    $process = proc_open([PHP_BINARY, __FILE__, '--worker'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path(), null, ['bypass_shell' => true]);
    if (! is_resource($process)) {
        throw new RuntimeException('Worker tidak dapat dimulai.');
    }
    $index = count($workers);
    $workers[$index] = ['process' => $process, 'pipes' => $pipes];
    fwrite($pipes[0], json_encode(['database' => $name, 'id' => $id, 'body' => $body], JSON_THROW_ON_ERROR));
    fclose($pipes[0]);
    stream_set_timeout($pipes[1], 30);
    $ready = json_decode(trim((string) fgets($pipes[1])), true, flags: JSON_THROW_ON_ERROR);

    return ['index' => $index, 'thread' => (int) $ready['thread']];
};
$wait = static function (array $worker) use ($check): void {
    for ($i = 0; $i < 100; $i++) {
        $state = DB::connection('mysql')->selectOne('SELECT trx_state FROM information_schema.innodb_trx WHERE trx_mysql_thread_id = ?', [$worker['thread']]);
        if (($state->trx_state ?? null) === 'LOCK WAIT') {
            $check(true, 'Worker settlement terbukti LOCK WAIT');

            return;
        }
        usleep(100000);
    }
    throw new RuntimeException('Worker tidak terbukti menunggu lock.');
};
$finish = static function (array $worker) use (&$workers): array {
    $entry = $workers[$worker['index']];
    $out = stream_get_contents($entry['pipes'][1]);
    $meta = stream_get_meta_data($entry['pipes'][1]);
    if ($meta['timed_out']) {
        throw new RuntimeException('Worker settlement timeout.');
    }
    fclose($entry['pipes'][1]);
    fclose($entry['pipes'][2]);
    $code = proc_close($entry['process']);
    unset($workers[$worker['index']]);
    if ($code !== 0) {
        throw new RuntimeException('Worker settlement gagal tanpa output sensitif.');
    }

    return json_decode(trim((string) $out), true, flags: JSON_THROW_ON_ERROR);
};
try {
    $server->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $created = true;
    $connect($name);
    $check(Artisan::call('migrate', ['--force' => true]) === 0, 'Migration native lengkap');
    $migration = require app_path('Modules/Inventory/Transfers/Database/Migrations/2026_10_23_000003_create_stock_transfer_drafts.php');
    $migration->down();
    $migration->up();
    $payload = TransferDraftFixture::seed();
    $action = app(ManageTransferDraft::class);
    $id = $action->save($payload);
    $before = TransferDraftFixture::fingerprint();
    $lines = DB::table('stock_transfer_lines')->where('transfer_id', $id)->orderBy('item_id')->get()->toJson();
    $action->save($payload + ['version' => 1], $id);
    $check($lines === DB::table('stock_transfer_lines')->where('transfer_id', $id)->orderBy('item_id')->get()->toJson()
        && DB::table('stock_transfers')->where('id', $id)->value('version') === 1, 'Native snapshot/no-op exact tanpa version/audit tambahan');
    $cable = DB::table('stock_transfer_lines')->where('sku', 'DEMO-ELEC-NYM-3X15')->sole();
    $check($cable->quantity === '0.125000' && $cable->base_quantity === '12.500000', 'Decimal20,6 native exact');
    DB::beginTransaction();
    DB::table('stock_transfers')->where('id', $id)->lockForUpdate()->first();
    $one = $start($id, array_replace($payload, ['version' => 1, 'notes' => 'Child']));
    $wait($one);
    $action->save(array_replace($payload, ['version' => 1, 'notes' => 'Parent']), $id);
    DB::commit();
    $result = $finish($one);
    $check(($result['rejected'] ?? false) && DB::table('stock_transfers')->where('id', $id)->value('version') === 2
        && DB::table('stock_transfers')->where('id', $id)->value('notes') === 'Parent', 'Concurrent edit stale ditolak tanpa lost update');
    DB::beginTransaction();
    DB::table('warehouses')->where('id', $payload['source_warehouse_id'])->lockForUpdate()->first();
    $one = $start($id, array_replace($payload, ['version' => 2, 'notes' => 'Master race']));
    $wait($one);
    DB::table('warehouses')->where('id', $payload['source_warehouse_id'])->update(['is_active' => false]);
    DB::commit();
    $result = $finish($one);
    $check(($result['rejected'] ?? false) && DB::table('stock_transfers')->where('id', $id)->value('version') === 2, 'Current read gudang nonaktif setelah lock ditolak');
    DB::table('warehouses')->where('id', $payload['source_warehouse_id'])->update(['is_active' => true]);
    $check($before === TransferDraftFixture::fingerprint(), 'Source/Stock/Finance/master identik sesudah draft');
    $snapshot = DB::table('stock_transfers')->orderBy('id')->get()->toJson();
    Activity::creating(fn ($log) => $log->log_name === 'inventory.transfers' ? false : null);
    $rejected = false;
    try {
        $action->delete($id, ['version' => 2]);
    } catch (RuntimeException) {
        $rejected = true;
    } finally {
        Activity::flushEventListeners();
    }
    $check($rejected && $snapshot === DB::table('stock_transfers')->orderBy('id')->get()->toJson(), 'Audit veto native rollback lifecycle');
    $action->delete($id, ['version' => 2]);
    $action->restore($id, ['version' => 3]);
    $check(DB::table('stock_transfers')->where('id', $id)->value('version') === 4 && $before === TransferDraftFixture::fingerprint(), 'Restore snapshot tanpa ledger mutation');
    $rejected = false;
    try {
        $migration->down();
    } catch (LogicException) {
        $rejected = true;
    }
    $check($rejected, 'Populated down ditolak termasuk draft');
    $rejected = false;
    try {
        DB::table('stock_transfers')->where('id', $id)->delete();
    } catch (QueryException $error) {
        if (($error->errorInfo[1] ?? null) !== 1451) {
            throw $error;
        } $rejected = true;
    }
    $check($rejected, 'FK restrict menjaga baris draft');
    $check(Artisan::call('migrate:status') === 0, 'Native migration status');
} finally {
    if (config('database.connections.return_refund_qa') !== null) {
        while (DB::connection('return_refund_qa')->transactionLevel() > 0) {
            DB::connection('return_refund_qa')->rollBack();
        }
    }
    foreach ($workers as $entry) {
        if (is_resource($entry['process'])) {
            proc_terminate($entry['process']);
            foreach ($entry['pipes'] as $pipe) {
                if (is_resource($pipe)) {
                    fclose($pipe);
                }
            }
            proc_close($entry['process']);
        }
    }
    DB::purge('return_refund_qa');
    if ($created) {
        $server->exec('DROP DATABASE `'.$name.'`');
        echo "PASS: Worker/database QA dibersihkan; DB user tidak dimutasi.\n";
    }
}
