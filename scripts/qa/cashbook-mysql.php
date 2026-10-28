<?php

use App\Models\User;
use App\Modules\Finance\Payments\Application\Actions\ManageCashbook;
use App\Modules\Finance\Payments\Application\Contracts\CashbookReader;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\Fixtures\PaymentHistoryFixture;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
set_exception_handler(static function (Throwable $error): never {
    fwrite(STDERR, 'FAIL Buku Kas MySQL: '.class_basename($error).' '.basename($error->getFile()).':'.$error->getLine().PHP_EOL);
    exit(1);
});
if (DB::connection()->getDriverName() !== 'mysql') {
    throw new LogicException('MySQL diperlukan.');
}
$original = DB::connection()->getConfig();
unset($original['name']);
$safe = fn ($name) => is_string($name) && preg_match('/^pos_cashbook_qa_[a-f0-9]{16}$/D', $name) && $name !== $original['database'];
$connect = function (string $name) use ($app, $original, $safe): void {
    if (! $safe($name)) {
        throw new LogicException('Target tidak aman.');
    }
    config(['database.connections.cashbook_qa' => array_replace($original, ['database' => $name, 'url' => null]), 'app.timezone' => 'UTC', 'cache.default' => 'array', 'hashing.bcrypt.rounds' => 4]);
    DB::setDefaultConnection('cashbook_qa');
    Schema::clearResolvedInstance('db.schema');
    $app->detectEnvironment(fn () => 'testing');
    if (DB::selectOne('SELECT DATABASE() AS db')->db !== $name) {
        throw new LogicException('Koneksi salah.');
    }
};
if (($argv[1] ?? '') === '--worker') {
    $input = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
    $connect($input['database']);
    $action = app(ManageCashbook::class);
    $id = $input['mode'] === 'post' ? $action->post($input['payload'], $input['actor']) : $action->reverse($input['source'], 'QA reversal concurrent', $input['actor']);
    echo json_encode(['id' => $id]);
    exit;
}
$server = DB::connection()->getPdo();
$name = 'pos_cashbook_qa_'.bin2hex(random_bytes(8));
if (! $safe($name)) {
    throw new LogicException('Target tidak aman.');
}
$check = static function (bool $ok, string $label): void {
    if (! $ok) {
        throw new RuntimeException($label);
    } echo 'PASS: '.$label.PHP_EOL;
};
$created = false;
$workers = [];
try {
    $server->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $created = true;
    $connect($name);
    $check(Artisan::call('migrate', ['--force' => true]) === 0, 'Migration native pada database disposable');
    $fixture = PaymentHistoryFixture::seed();
    $actor = User::factory()->create()->id;
    $action = app(ManageCashbook::class);
    $reader = app(CashbookReader::class);
    foreach (['cash_account', 'bank_account'] as $key) {
        $action->open(['account_id' => $fixture[$key], 'starts_on' => '2026-10-16', 'amount' => '100'], $actor);
    }
    $cash = fn () => $reader->statement($fixture['cash_account'], null, null, 1)['summary']['closing'];
    $check($cash() === '4500.120000', 'Union memakai net cash dan cutoff WIB, bukan tendered');
    $payload = ['kind' => 'transfer', 'account_id' => $fixture['cash_account'], 'counter_account_id' => $fixture['bank_account'], 'amount' => '9007199254740993.123456', 'occurred_on' => now('Asia/Jakarta')->toDateString(), 'description' => 'QA transfer exact', 'idempotency_key' => (string) Str::uuid()];
    $id = $action->post($payload, $actor);
    $check($cash() === '-9007199254736493.003456', 'Saldo negatif besar exact6 tanpa float');
    $check($action->post($payload, $actor) === $id, 'Replay transfer idempotent');
    $action->reverse($id, 'QA reversal', $actor);
    $check($cash() === '4500.120000', 'Reversal dua kaki menjaga saldo asal');
    $payload['amount'] = '1.000001';
    $payload['idempotency_key'] = (string) Str::uuid();
    $concurrent = function (array $data) use (&$workers, $name, $actor): array {
        $pair = [];
        for ($i = 0; $i < 2; $i++) {
            $process = new Process([PHP_BINARY, __FILE__, '--worker'], base_path(), timeout: 60);
            $process->setInput(json_encode($data + ['database' => $name, 'actor' => $actor]));
            $process->start();
            $workers[] = $process;
            $pair[] = $process;
        }
        $ids = [];
        foreach ($pair as $process) {
            $process->wait();
            if (! $process->isSuccessful()) {
                throw new RuntimeException('Worker gagal.');
            } $ids[] = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR)['id'];
        }

        return $ids;
    };
    $ids = $concurrent(['mode' => 'post', 'payload' => $payload]);
    $check($ids[0] === $ids[1] && DB::table('cashbook_entries')->count() === 3, 'Dua worker MySQL: satu transfer untuk kunci sama');
    $ids = $concurrent(['mode' => 'reverse', 'source' => $ids[0]]);
    $check($ids[0] === $ids[1] && DB::table('cashbook_entries')->count() === 4 && $cash() === '4500.120000', 'Dua worker: satu pembalik dan saldo kembali tepat');
    $check(DB::table('activity_log')->where('log_name', 'finance.cashbook')->count() === 6, 'Satu audit per opening/post/reversal, replay tidak menambah audit');
    $column = collect(DB::select('SHOW COLUMNS FROM cashbook_entries'))->firstWhere('Field', 'amount');
    $check(strtolower($column->Type) === 'decimal(30,6)', 'Schema native decimal30,6');
} finally {
    foreach ($workers as $worker) {
        if ($worker->isRunning()) {
            $worker->stop();
        }
    }
    DB::purge('cashbook_qa');
    if ($created) {
        $server->exec('DROP DATABASE `'.$name.'`');
        echo 'PASS: database disposable dibersihkan; database toko tidak dimutasi.'.PHP_EOL;
    }
}
