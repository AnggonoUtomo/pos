<?php

use App\Modules\Purchasing\Returns\Application\Actions\SettleReturnRefund;
use App\Modules\Purchasing\Returns\Application\Queries\QuoteReturnRefund;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;
use Tests\Fixtures\PurchaseReturnRefundFixture;

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
$valid = static fn ($name) => is_string($name) && preg_match('/^pos_purchase_refund_qa_[a-f0-9]{16}$/D', $name) && $name !== $original['database'];
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
        $result = app(SettleReturnRefund::class)->handle($input['id'], $input['body']);
        echo json_encode(['id' => $result['settlement']['id']])."\n";
        exit(0);
    } catch (ValidationException) {
        echo json_encode(['rejected' => true])."\n";
        exit(0);
    } catch (Throwable) {
        fwrite(STDERR, "FAIL: worker settlement gagal.\n");
        exit(1);
    }
}
$name = 'pos_purchase_refund_qa_'.bin2hex(random_bytes(8));
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
    $migration = require app_path('Modules/Purchasing/Returns/Database/Migrations/2026_10_23_000002_create_purchase_return_settlements.php');
    $migration->down();
    $migration->up();
    $check(Artisan::call('migrate:status') === 0, 'Empty down/up dan status disposable');
    $invoice = PurchaseReturnRefundFixture::purchase();
    $first = PurchaseReturnRefundFixture::posted($invoice, '1');
    $last = PurchaseReturnRefundFixture::posted($invoice, '1');
    $method = DB::table('payment_methods')->where('code', 'TRANSFER')->value('id');
    $body = static fn ($quote) => ['version' => $quote['version'], 'quote_fingerprint' => $quote['quote_fingerprint'],
        'payments' => $quote['total'] === '0.000000' ? [] : [['method_id' => $method, 'amount' => $quote['total']]]];
    $firstBody = $body(app(QuoteReturnRefund::class)->handle($first));
    $lastBody = $body(app(QuoteReturnRefund::class)->handle($last));
    $before = PurchaseReturnRefundFixture::fingerprint();
    $check(DB::table('purchase_return_refund_scopes')->count() === 0, 'Quote readonly tanpa membuat scope');

    DB::beginTransaction();
    DB::table('purchase_returns')->where('id', $first)->lockForUpdate()->first();
    $one = $start($first, $firstBody);
    $wait($one);
    $two = $start($last, $lastBody);
    $wait($two);
    DB::commit();
    $a = $finish($one);
    $b = $finish($two);
    $check(isset($a['id']) && ($b['rejected'] ?? false) && DB::table('purchase_refund_receipts')->count() === 1
        && DB::table('purchase_return_settlements')->count() === 1, 'Initial scope race: satu receipt, quote invoice lama ditolak');

    DB::beginTransaction();
    DB::table('purchase_returns')->where('id', $first)->lockForUpdate()->first();
    $one = $start($first, $firstBody);
    $wait($one);
    $two = $start($first, $firstBody);
    $wait($two);
    DB::commit();
    $r1 = $finish($one);
    $r2 = $finish($two);
    $check(($r1['id'] ?? null) === $a['id'] && ($r2['id'] ?? null) === $a['id'] && DB::table('purchase_refund_receipts')->count() === 1,
        'Dua retry bersamaan tidak menggandakan receipt');
    $saved = app(SettleReturnRefund::class)->handle($first, $firstBody);
    DB::table('payment_methods')->update(['is_active' => false]);
    $check($saved === app(SettleReturnRefund::class)->handle($first, $firstBody), 'Replay snapshot MySQL JSON identik tanpa master aktif');
    DB::table('payment_methods')->update(['is_active' => true]);
    app(SettleReturnRefund::class)->handle($last, $body(app(QuoteReturnRefund::class)->handle($last)));
    $check(DB::table('purchase_return_refund_scopes')->where('purchase_order_id', $invoice['id'])->value('revision') === 2
        && DB::table('purchase_refund_receipts')->count() === 2 && $before === PurchaseReturnRefundFixture::fingerprint(), 'Full settlement tetap exact dan source/Stock identik');

    $zeroInvoice = PurchaseReturnRefundFixture::purchase('1', '0.000001');
    $zero = PurchaseReturnRefundFixture::posted($zeroInvoice, '0.000001');
    $cent = PurchaseReturnRefundFixture::posted($zeroInvoice, '0.999999');
    $before = PurchaseReturnRefundFixture::fingerprint();
    $zeroResult = app(SettleReturnRefund::class)->handle($zero, $body(app(QuoteReturnRefund::class)->handle($zero)));
    $check($zeroResult['settlement']['outcome'] === 'no_payment' && $zeroResult['settlement']['finance_receipt_id'] === null
        && DB::table('purchase_refund_receipts')->count() === 2, 'Nol diselesaikan tanpa ledger pembayaran');
    $lastQuote = app(QuoteReturnRefund::class)->handle($cent);
    app(SettleReturnRefund::class)->handle($cent, $body($lastQuote));
    $check($lastQuote['total'] === '0.000001' && $before === PurchaseReturnRefundFixture::fingerprint(), 'Residual mikro terakhir dan kuantitas terjaga native');

    $legacy = PurchaseReturnRefundFixture::purchase();
    $vetoReturn = PurchaseReturnRefundFixture::posted($legacy, '0.1');
    $vetoBody = $body(app(QuoteReturnRefund::class)->handle($vetoReturn));
    $before = PurchaseReturnRefundFixture::fingerprint();
    $counts = [DB::table('purchase_refund_receipts')->count(), DB::table('purchase_return_settlements')->count(), DB::table('purchase_return_refund_scopes')->count()];
    Activity::creating(fn ($log) => $log->log_name === 'purchasing.returns' && $log->event === 'refund_settled' ? false : null);
    $rejected = false;
    try {
        app(SettleReturnRefund::class)->handle($vetoReturn, $vetoBody);
    } catch (RuntimeException) {
        $rejected = true;
    } finally {
        Activity::flushEventListeners();
    }
    $after = [DB::table('purchase_refund_receipts')->count(), DB::table('purchase_return_settlements')->count(), DB::table('purchase_return_refund_scopes')->count()];
    $check($rejected && $counts === $after && $before === PurchaseReturnRefundFixture::fingerprint(), 'Returns audit veto setelah Finance rollback seluruh settlement');
    $large = PurchaseReturnRefundFixture::posted(PurchaseReturnRefundFixture::purchase('10000000001', '90000000000000.123456'), '10000000001');
    $largeQuote = app(QuoteReturnRefund::class)->handle($large);
    $before = PurchaseReturnRefundFixture::fingerprint();
    $largeResult = app(SettleReturnRefund::class)->handle($large, $body($largeQuote));
    $check($largeQuote['total'] === '900000000090001234560000.123456'
        && $largeResult['settlement']['total'] === $largeQuote['total']
        && $before === PurchaseReturnRefundFixture::fingerprint(), 'Nominal24 digit dan enam pecahan tetap exact native');
    $rejected = false;
    try {
        $migration->down();
    } catch (LogicException) {
        $rejected = true;
    }
    $check($rejected, 'Down settlement posted ditolak');
    $rejected = false;
    try {
        DB::table('purchase_return_settlements')->where('id', $a['id'])->delete();
    } catch (QueryException $error) {
        if (($error->errorInfo[1] ?? null) !== 1451) {
            throw $error;
        }
        $rejected = true;
    }
    $check($rejected, 'FK restrict menjaga alokasi settlement');
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
