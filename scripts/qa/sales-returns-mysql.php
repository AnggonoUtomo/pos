<?php

use App\Modules\Sales\Returns\Application\Actions\ManageReturnDraft;
use App\Modules\Sales\Returns\Application\Contracts\ReturnDraftRepository;
use App\Modules\Sales\Returns\Application\Queries\ReturnWorkspace;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
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
    throw new LogicException('QA retur hanya local/MySQL disposable.');
}
$original = config('database.connections.mysql');
$name = 'pos_returns_qa_'.bin2hex(random_bytes(8));
if (! preg_match('/^pos_returns_qa_[a-f0-9]{16}$/D', $name) || $name === $original['database']) {
    throw new LogicException('Nama DB tidak aman.');
}
$server = DB::connection('mysql')->getPdo();
$created = false;
$check = static function (bool $ok, string $label): void {
    if (! $ok) {
        throw new RuntimeException($label);
    }
    echo "PASS: {$label}\n";
};
try {
    $server->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $created = true;
    foreach (['returns_qa', 'returns_peer'] as $connection) {
        config(['database.connections.'.$connection => array_replace($original, ['database' => $name])]);
    }
    DB::setDefaultConnection('returns_qa');
    $app->detectEnvironment(fn () => 'testing');
    $check(Artisan::call('migrate', ['--force' => true]) === 0, 'Native migration baseline');
    $invoice = SalesReturnFixture::seed('BOX', '1');
    $input = ['invoice_id' => $invoice['id'], 'notes' => 'QA native', 'lines' => [['invoice_line_id' => $invoice['lines'][0]['id'], 'quantity' => '0.123456']]];
    $before = SalesReturnFixture::fingerprint();
    $action = app(ManageReturnDraft::class);
    $id = $action->save($input);
    $row = app(ReturnWorkspace::class)->records(1, '', false)['data'][0];
    $check($row['lines'][0]['quantity'] === '0.123456' && $row['lines'][0]['base_quantity'] === '0.493824', 'Native decimal20,6 dan snapshot satuan BOX exact');
    $action->save($input + ['version' => 1], $id);
    $check(DB::table('sales_returns')->where('id', $id)->value('version') === 1 && Activity::where('log_name', 'sales.returns')->count() === 1, 'No-op JSON native tidak menambah version/audit');
    $peer = DB::connection('returns_peer');
    $peer->statement('SET SESSION innodb_lock_wait_timeout=1');
    DB::beginTransaction();
    app(ReturnDraftRepository::class)->lock($id);
    try {
        $peer->table('sales_returns')->where('id', $id)->lockForUpdate()->first();
        throw new RuntimeException('Row lock tidak melindungi draft.');
    } catch (QueryException $e) {
        $check(($e->errorInfo[1] ?? null) === 1205, 'Peer mutation diblokir row lock header');
    } finally {
        DB::rollBack();
    }
    $peer->beginTransaction();
    $check($peer->table('sales_returns')->where('id', $id)->value('version') === 1, 'Peer membuka snapshot version1');
    $action->save(array_replace($input, ['notes' => 'QA versi baru', 'version' => 1]), $id);
    DB::setDefaultConnection('returns_peer');
    try {
        app(ManageReturnDraft::class)->save($input + ['version' => 1], $id);
        throw new RuntimeException('Stale peer lolos.');
    } catch (ValidationException $e) {
        $check(isset($e->errors()['version']), 'Lock current read menolak stale snapshot peer');
    } finally {
        $peer->rollBack();
        DB::setDefaultConnection('returns_qa');
    }
    $check(DB::table('sales_returns')->where('id', $id)->value('version') === 2, 'Stale mutation tidak menimpa versi baru');
    $saved = DB::table('sales_returns')->where('id', $id)->first();
    Activity::saving(fn ($log) => $log->log_name === 'sales.returns' ? false : null);
    try {
        $action->lifecycle($id, 2, false);
        throw new LogicException('Audit veto lolos.');
    } catch (RuntimeException) {
        $check($saved == DB::table('sales_returns')->where('id', $id)->first(), 'Native audit veto rollback lifecycle');
    } finally {
        Activity::flushEventListeners();
    }
    $action->lifecycle($id, 2, false);
    $action->lifecycle($id, 3, true);
    $check(DB::table('sales_returns')->where('id', $id)->value('version') === 4, 'Native soft delete/restore versioned');
    $duplicate = (array) DB::table('sales_return_lines')->where('sales_return_id', $id)->sole();
    $duplicate['id'] = strtolower((string) Str::ulid());
    try {
        DB::table('sales_return_lines')->insert($duplicate);
        throw new RuntimeException('Unique gagal.');
    } catch (QueryException $e) {
        $check(($e->errorInfo[1] ?? null) === 1062, 'Unique source line per draft');
    }
    $duplicate['invoice_line_id'] = strtolower((string) Str::ulid());
    try {
        DB::table('sales_return_lines')->insert($duplicate);
        throw new RuntimeException('FK gagal.');
    } catch (QueryException $e) {
        $check(($e->errorInfo[1] ?? null) === 1452, 'FK source invoice line restrict');
    }
    $check($before === SalesReturnFixture::fingerprint(), 'Seluruh draft lifecycle menjaga invoice/stock/FIFO/finance identik');
    $migration = require __DIR__.'/../../app/Modules/Sales/Returns/Database/Migrations/2026_10_18_000001_create_sales_return_drafts.php';
    $migration->down();
    $migration->up();
    $check($before === SalesReturnFixture::fingerprint(), 'Rollback/up schema retur disposable tidak mengubah sumber');
} finally {
    foreach (['returns_qa', 'returns_peer'] as $connection) {
        DB::purge($connection);
    }
    if ($created) {
        $server->exec('DROP DATABASE `'.$name.'`');
        echo "PASS: DB QA dibersihkan; DB user tidak dimutasi.\n";
    }
}
