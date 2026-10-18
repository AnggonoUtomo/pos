<?php

use App\Modules\Finance\Payments\Application\Actions\ManagePaymentAccount;
use App\Modules\Finance\Payments\Application\Actions\ManagePaymentMethod;
use App\Modules\Finance\Payments\Application\Contracts\SaleRefundPaymentWriter;
use App\Modules\Finance\Payments\Application\DTOs\PaymentAccountData;
use App\Modules\Finance\Payments\Application\DTOs\PaymentMethodData;
use App\Modules\Finance\Payments\Application\DTOs\SaleRefundPaymentRequest;
use App\Modules\Finance\Payments\Database\Seeders\PaymentsDemoSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('local') || config('database.default') !== 'mysql') {
    throw new LogicException('QA refund hanya local/MySQL disposable.');
}
$original = config('database.connections.mysql');
$valid = static fn ($name) => is_string($name) && preg_match('/^pos_refund_pay_qa_[a-f0-9]{16}$/D', $name) && $name !== $original['database'];
$connect = static function (string $name) use ($original, $app): void {
    config(['database.connections.refund_qa' => array_replace($original, ['database' => $name, 'url' => null]), 'cache.default' => 'array']);
    DB::setDefaultConnection('refund_qa');
    if (DB::selectOne('SELECT DATABASE() AS selected_database')->selected_database !== $name) {
        throw new LogicException('Koneksi QA tidak menunjuk database disposable yang diminta.');
    }
    $app->detectEnvironment(fn () => 'testing');
};
$request = static fn (array $data) => new SaleRefundPaymentRequest($data['source'], 'SR-NATIVE-QA', $data['total'], $data['payments']);
if (($argv[1] ?? '') === '--worker') {
    try {
        $input = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
        if (! $valid($input['database'] ?? null)) {
            throw new LogicException('Database worker tidak aman.');
        }
        $connect($input['database']);
        echo json_encode(['thread' => DB::selectOne('SELECT CONNECTION_ID() AS id')->id])."\n";
        flush();
        $result = DB::transaction(fn () => app(SaleRefundPaymentWriter::class)->pay($request($input)), 3);
        echo json_encode(['id' => $result->id])."\n";
        exit(0);
    } catch (InvalidArgumentException|ValidationException) {
        echo json_encode(['rejected' => true])."\n";
        exit(0);
    } catch (Throwable) {
        fwrite(STDERR, "FAIL: worker refund gagal.\n");
        exit(1);
    }
}
$name = 'pos_refund_pay_qa_'.bin2hex(random_bytes(8));
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
    }
    echo "PASS: {$label}\n";
};
$start = static function (array $data) use (&$worker, &$pipes, $name): int {
    $worker = proc_open([PHP_BINARY, __FILE__, '--worker'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path(), null, ['bypass_shell' => true]);
    if (! is_resource($worker)) {
        throw new RuntimeException('Worker refund tidak dimulai.');
    }
    fwrite($pipes[0], json_encode(['database' => $name] + $data, JSON_THROW_ON_ERROR));
    fclose($pipes[0]);
    stream_set_timeout($pipes[1], 30);
    $ready = json_decode(trim((string) fgets($pipes[1])), true, flags: JSON_THROW_ON_ERROR);

    return (int) $ready['thread'];
};
$waiting = static function (int $thread) use ($check): void {
    for ($i = 0; $i < 100; $i++) {
        $state = DB::connection('mysql')->selectOne('SELECT trx_state FROM information_schema.innodb_trx WHERE trx_mysql_thread_id = ?', [$thread]);
        if (($state->trx_state ?? null) === 'LOCK WAIT') {
            $check(true, 'Worker native terbukti LOCK WAIT');

            return;
        }
        usleep(100000);
    }
    throw new RuntimeException('Worker tidak terbukti menunggu lock.');
};
$finish = static function () use (&$worker, &$pipes): array {
    $output = stream_get_contents($pipes[1]);
    $meta = stream_get_meta_data($pipes[1]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if ($meta['timed_out']) {
        throw new RuntimeException('Worker refund timeout.');
    }
    $exit = proc_close($worker);
    $worker = null;
    if ($exit !== 0) {
        throw new RuntimeException('Worker refund gagal tanpa output sensitif.');
    }

    return json_decode(trim((string) $output), true, flags: JSON_THROW_ON_ERROR);
};
try {
    $server->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $created = true;
    $connect($name);
    $check(Artisan::call('migrate', ['--force' => true]) === 0, 'Native migration lengkap');
    $migration = require app_path('Modules/Finance/Payments/Database/Migrations/2026_10_19_000001_create_payment_refunds.php');
    $migration->down();
    $migration->up();
    $check(Artisan::call('migrate:status') === 0, 'Status migration disposable diperiksa');
    $column = DB::selectOne('SELECT numeric_precision AS p, numeric_scale AS s FROM information_schema.columns WHERE table_schema = ? AND table_name = ? AND column_name = ?', [$name, 'payment_refunds', 'total']);
    $check((int) $column->p === 30 && (int) $column->s === 2, 'Native schema decimal30,2 dan empty down/up');
    app(PaymentsDemoSeeder::class)->run();
    $methods = DB::table('payment_methods')->pluck('id', 'code');
    $cashAccount = DB::table('payment_methods')->where('id', $methods['TUNAI'])->value('payment_account_id');
    $data = ['source' => strtolower((string) Str::ulid()), 'total' => '100.01', 'payments' => [['method_id' => $methods['TUNAI'], 'amount' => '100.01']]];

    DB::beginTransaction();
    DB::table('payment_accounts')->where('id', $cashAccount)->lockForUpdate()->first();
    $waiting($start($data));
    $result = app(SaleRefundPaymentWriter::class)->pay($request($data));
    DB::commit();
    $peer = $finish();
    $check(($peer['id'] ?? null) === $result->id && DB::table('payment_refunds')->count() === 1 && DB::table('activity_log')->where('event', 'refunded')->count() === 1,
        'Concurrent replay/current read menghasilkan satu refund dan audit');

    $newMethod = app(ManagePaymentMethod::class)->save(new PaymentMethodData('QA-CASH', 'Tunai QA', 'cash', $cashAccount));
    $secondAccount = app(ManagePaymentAccount::class)->save(new PaymentAccountData('QA-CASH-2', 'Kas QA kedua', 'cash'));
    foreach (['inactive', 'mapping'] as $case) {
        $input = ['source' => strtolower((string) Str::ulid()), 'total' => '5.01', 'payments' => [['method_id' => $newMethod, 'amount' => '5.01']]];
        DB::beginTransaction();
        DB::table('payment_accounts')->where('id', $cashAccount)->lockForUpdate()->first();
        $waiting($start($input));
        app(ManagePaymentMethod::class)->save(new PaymentMethodData('QA-CASH', 'Tunai QA', 'cash', $case === 'mapping' ? $secondAccount : $cashAccount, $case !== 'inactive'), $newMethod);
        DB::commit();
        $peer = $finish();
        $check(($peer['rejected'] ?? false) && DB::table('payment_refunds')->count() === 1, 'Current read menolak perubahan '.$case.' tanpa payout');
        app(ManagePaymentMethod::class)->save(new PaymentMethodData('QA-CASH', 'Tunai QA', 'cash', $cashAccount), $newMethod);
    }
    $max = '9999999999999999999999999999.99';
    $large = ['source' => strtolower((string) Str::ulid()), 'total' => $max, 'payments' => [['method_id' => $methods['TRANSFER'], 'amount' => $max]]];
    $paid = DB::transaction(fn () => app(SaleRefundPaymentWriter::class)->pay($request($large)));
    $check($paid->snapshot['total'] === $max && $paid->snapshot['payments'][0]['amount'] === $max, 'Nominal28 digit dan sen terakhir exact native');
    DB::table('payment_methods')->update(['is_active' => false, 'name' => 'Nama berubah']);
    $replay = DB::transaction(fn () => app(SaleRefundPaymentWriter::class)->pay($request($large)));
    $check($paid->snapshot === $replay->snapshot && $paid->id === $replay->id && DB::table('activity_log')->where('event', 'refunded')->count() === 2, 'Replay memakai snapshot tanpa master aktif atau audit ganda');
    DB::table('payment_methods')->update(['is_active' => true]);
    Activity::creating(fn ($log) => $log->event === 'refunded' ? false : null);
    $rejected = false;
    try {
        $fresh = $data;
        $fresh['source'] = strtolower((string) Str::ulid());
        DB::transaction(fn () => app(SaleRefundPaymentWriter::class)->pay($request($fresh)));
    } catch (RuntimeException) {
        $rejected = true;
    } finally {
        Activity::flushEventListeners();
    }
    $check($rejected && DB::table('payment_refunds')->count() === 2 && DB::table('payment_refund_lines')->count() === 2, 'Audit veto rollback ledger native');
    $rejected = false;
    try {
        app(ManagePaymentMethod::class)->delete($methods['TUNAI']);
    } catch (ValidationException) {
        $rejected = true;
    }
    $check($rejected, 'Histori refund melindungi metode');
    $rejected = false;
    try {
        DB::table('payment_refunds')->where('id', $result->id)->delete();
    } catch (QueryException $error) {
        if (($error->errorInfo[1] ?? null) !== 1451) {
            throw $error;
        }
        $rejected = true;
    }
    $check($rejected, 'FK restrict mempertahankan refund');
    $rejected = false;
    try {
        $migration->down();
    } catch (LogicException) {
        $rejected = true;
    }
    $check($rejected, 'Down migration posted ditolak');
    foreach (['sales_invoices', 'payment_receipts', 'payment_disbursements', 'stock_movements', 'fifo_layers'] as $table) {
        $check(DB::table($table)->count() === 0, 'Source ledger '.$table.' tidak dimutasi');
    }
} finally {
    while (DB::connection('refund_qa')->transactionLevel() > 0) {
        DB::connection('refund_qa')->rollBack();
    }
    if (is_resource($worker)) {
        proc_terminate($worker);
        foreach ($pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }
        proc_close($worker);
    }
    DB::purge('refund_qa');
    if ($created) {
        $server->exec('DROP DATABASE `'.$name.'`');
        echo "PASS: Database QA dibersihkan; DB user tidak dimutasi.\n";
    }
}
