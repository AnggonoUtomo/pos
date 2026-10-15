<?php

use App\Modules\Finance\Payments\Application\Actions\ManagePaymentMethod;
use App\Modules\Finance\Payments\Application\Actions\RecordSalePayment;
use App\Modules\Finance\Payments\Application\Contracts\PaymentsRepository;
use App\Modules\Finance\Payments\Database\Seeders\PaymentsDemoSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('local') || config('database.default') !== 'mysql') {
    throw new LogicException('QA hanya MySQL local dengan database disposable baru.');
}
$original = config('database.connections.mysql');
$validName = static fn ($name) => is_string($name) && preg_match('/^pos_payment_records_qa_[a-f0-9]{16}$/D', $name) && $name !== $original['database'];
if (($argv[1] ?? null) === '--worker') {
    try {
        $input = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
        if (! $validName($input['database'] ?? null)) {
            throw new LogicException('Database worker tidak aman.');
        }
        config(['database.connections.receipt_worker' => array_replace($original, ['database' => $input['database']])]);
        DB::setDefaultConnection('receipt_worker');
        $app->detectEnvironment(fn () => 'testing');
        echo json_encode(['thread' => DB::selectOne('SELECT CONNECTION_ID() AS id')->id])."\n";
        flush();
        $result = app(RecordSalePayment::class)->handle($input['payment']);
        echo json_encode(['receipt_id' => $result['id']])."\n";
        exit(0);
    } catch (Throwable) {
        fwrite(STDERR, "FAIL: worker penerimaan gagal.\n");
        exit(1);
    }
}
$name = 'pos_payment_records_qa_'.bin2hex(random_bytes(8));
if (! $validName($name)) {
    throw new LogicException('Nama database QA tidak aman.');
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
    config(['database.connections.receipt_qa' => $connection, 'database.connections.receipt_peer' => $connection]);
    DB::setDefaultConnection('receipt_qa');
    $app->detectEnvironment(fn () => 'testing');
    if (Artisan::call('migrate', ['--force' => true]) !== 0) {
        throw new RuntimeException('Migration QA gagal.');
    }
    app(PaymentsDemoSeeder::class)->run();
    $before = DB::table('payment_methods')->orderBy('id')->get()->toJson();
    $migration = require app_path('Modules/Finance/Payments/Database/Migrations/2026_10_16_000002_create_payment_receipts.php');
    $migration->down();
    $migration->up();
    $check($before === DB::table('payment_methods')->orderBy('id')->get()->toJson(), 'Native MySQL up/down empty mempertahankan master');
    $methods = DB::table('payment_methods')->pluck('id', 'code')->all();
    $payment = static function (string $total = '100000.00', string $cash = '50000.00', string $bank = '60000.00') use ($methods): array {
        return ['source_id' => strtolower((string) Str::ulid()), 'source_number' => 'POS-QA', 'total' => $total,
            'payments' => [['method_id' => $methods['TUNAI'], 'amount' => $cash], ['method_id' => $methods['TRANSFER'], 'amount' => $bank]]];
    };
    $peerRecord = static function (array $input): array {
        DB::setDefaultConnection('receipt_peer');
        try {
            return app(RecordSalePayment::class)->handle($input);
        } finally {
            DB::setDefaultConnection('receipt_qa');
        }
    };
    $peer = DB::connection('receipt_peer');
    DB::statement('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    $peer->statement('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    $accountId = DB::table('payment_methods')->where('id', $methods['TUNAI'])->value('payment_account_id');
    foreach (['account', 'method', 'assignment'] as $case) {
        DB::beginTransaction();
        try {
            DB::table('payment_methods')->get();
            DB::table('payment_accounts')->get();
            if ($case === 'assignment') {
                $peer->table('payment_methods')->where('id', $methods['TUNAI'])->update(['payment_account_id' => DB::table('payment_accounts')->where('type', 'bank')->value('id')]);
            } else {
                $peer->table($case === 'account' ? 'payment_accounts' : 'payment_methods')->where('id', $case === 'account' ? $accountId : $methods['TUNAI'])->update(['is_active' => false]);
            }
            $rejected = false;
            try {
                app(RecordSalePayment::class)->handle($payment());
            } catch (InvalidArgumentException) {
                $rejected = true;
            }
            $check($rejected && DB::table('payment_receipts')->count() === 0, 'RR stale '.$case.': state/assignment terbaru ditolak tanpa receipt');
        } finally {
            DB::rollBack();
            $peer->table('payment_accounts')->where('id', $accountId)->update(['is_active' => true]);
            $peer->table('payment_methods')->where('id', $methods['TUNAI'])->update(['is_active' => true, 'payment_account_id' => $accountId]);
        }
    }
    $input = $payment();
    DB::beginTransaction();
    try {
        DB::table('payment_receipts')->where('source_id', $input['source_id'])->first();
        DB::table('payment_records')->get();
        $posted = $peerRecord($input);
        $replay = app(RecordSalePayment::class)->handle($input);
        $check($posted === $replay && count($replay['payments']) === 2, 'Replay current header/records melihat commit peer sesudah snapshot kosong');
    } finally {
        DB::rollBack();
    }
    DB::beginTransaction();
    try {
        DB::table('payment_records')->where('method_id', $methods['DEBIT'])->get();
        $debit = $payment('1', '1', '1');
        $debit['payments'] = [['method_id' => $methods['DEBIT'], 'amount' => '1']];
        $peerRecord($debit);
        $rejected = false;
        try {
            app(ManagePaymentMethod::class)->delete($methods['DEBIT']);
        } catch (ValidationException) {
            $rejected = true;
        }
        $check($rejected, 'Guard histori memakai current read meskipun snapshot records awal kosong');
    } finally {
        DB::rollBack();
    }
    $large = $payment('9007199254740993.17', '9007199254740993.23', '1');
    $exact = app(RecordSalePayment::class)->handle($large);
    $cash = collect($exact['payments'])->firstWhere('method_type', 'cash');
    $check($exact['total'] === $large['total'] && $exact['change'] === '1.06' && $cash['applied_amount'] === '9007199254740992.17', 'DECIMAL MySQL di atas integer aman JS tetap exact tanpa float');
    $max = $payment('9999999999999999999999999999.99');
    $max['payments'] = [['method_id' => $methods['TRANSFER'], 'amount' => $max['total']]];
    $check(app(RecordSalePayment::class)->handle($max)['total'] === $max['total'], 'Batas DECIMAL(30,2) tersimpan exact');
    $concurrent = $payment();
    DB::beginTransaction();
    try {
        app(PaymentsRepository::class)->lockAccount($accountId);
        $worker = proc_open([PHP_BINARY, __FILE__, '--worker'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path(), null, ['bypass_shell' => true]);
        if (! is_resource($worker)) {
            throw new RuntimeException('Worker gagal dibuat.');
        }
        fwrite($pipes[0], json_encode(['database' => $name, 'payment' => $concurrent], JSON_THROW_ON_ERROR));
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
        $check($waiting, 'Worker simultan terbukti LOCK WAIT pada akun pembayaran');
        $main = app(RecordSalePayment::class)->handle($concurrent);
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
    $check($exit === 0 && $outcome['receipt_id'] === $main['id'] && DB::table('payment_receipts')->where('source_id', $concurrent['source_id'])->count() === 1
        && DB::table('payment_records')->where('receipt_id', $main['id'])->count() === 2
        && DB::table('activity_log')->where('subject_id', $main['id'])->where('event', 'received')->count() === 1, 'Retry simultan satu sumber menghasilkan tepat satu receipt/dua record/satu audit');
    foreach (['payment_methods' => $methods['TUNAI'], 'payment_accounts' => $accountId, 'payment_receipts' => $main['id']] as $table => $id) {
        $restricted = false;
        try {
            DB::table($table)->where('id', $id)->delete();
        } catch (QueryException $exception) {
            if (($exception->errorInfo[1] ?? null) !== 1451) {
                throw $exception;
            }
            $restricted = true;
        }
        $check($restricted, 'FK RESTRICT melindungi '.$table.' dengan histori');
    }
    $outside = false;
    try {
        app(PaymentsRepository::class)->lockReceiptBySource($input['source_id']);
    } catch (LogicException) {
        $outside = true;
    }
    $check($outside, 'Port receipt menolak locking di luar transaction');
    $refused = false;
    try {
        $migration->down();
    } catch (LogicException) {
        $refused = true;
    }
    $check($refused, 'Down berisi penerimaan posted ditolak');
} catch (Throwable $exception) {
    $failure = $exception;
} finally {
    if (is_resource($worker)) {
        proc_terminate($worker);
        foreach ($pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }
        proc_close($worker);
    }
    foreach (['receipt_qa', 'receipt_peer'] as $connectionName) {
        if (config('database.connections.'.$connectionName) === null) {
            continue;
        }
        $connection = DB::connection($connectionName);
        while ($connection->transactionLevel() > 0) {
            $connection->rollBack();
        }
        DB::purge($connectionName);
    }
    if ($created) {
        $server->exec('DROP DATABASE `'.$name.'`');
        echo "PASS: database generated dibersihkan; tidak memutasi pembayaran pengguna.\n";
    }
}
if ($failure !== null) {
    fwrite(STDERR, 'FAIL: '.get_class($failure).' pada '.basename($failure->getFile()).':'.$failure->getLine()."\n");
    exit(1);
}
