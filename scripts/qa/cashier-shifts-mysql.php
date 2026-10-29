<?php

use App\Models\User;
use App\Modules\Finance\Payments\Application\Contracts\CashDrawerPosition;
use App\Modules\Sales\POS\Application\Actions\ManageCashierShift;
use App\Modules\Sales\POS\Application\Actions\PostInvoice;
use App\Modules\Sales\POS\Application\Contracts\CashierShiftRepository;
use App\Modules\Sales\POS\Application\DTOs\CartLineData;
use App\Modules\Sales\POS\Application\Queries\CheckCart;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;
use Tests\Fixtures\SalesReturnFixture;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
set_exception_handler(static function (Throwable $error): never {
    fwrite(STDERR, 'FAIL Shift MySQL: '.class_basename($error).' '.basename($error->getFile()).':'.$error->getLine().PHP_EOL);
    exit(1);
});
if (DB::connection()->getDriverName() !== 'mysql') {
    throw new LogicException('MySQL diperlukan.');
}
$original = DB::connection()->getConfig();
unset($original['name']);
$safe = fn ($name) => is_string($name) && preg_match('/^pos_shift_qa_[a-f0-9]{16}$/D', $name) && $name !== $original['database'];
$connect = function (string $name) use ($app, $original, $safe): void {
    if (! $safe($name)) {
        throw new LogicException('Target tidak aman.');
    }
    config(['database.connections.shift_qa' => array_replace($original, ['database' => $name, 'url' => null]), 'cache.default' => 'array', 'app.timezone' => 'UTC', 'hashing.bcrypt.rounds' => 4]);
    DB::setDefaultConnection('shift_qa');
    Schema::clearResolvedInstance('db.schema');
    $app->detectEnvironment(fn () => 'testing');
    if (DB::selectOne('SELECT DATABASE() AS db')->db !== $name) {
        throw new LogicException('Koneksi salah.');
    }
};
if (($argv[1] ?? '') === '--worker') {
    $data = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
    $connect($data['database']);
    try {
        $result = match ($data['mode']) {
            'open' => app(ManageCashierShift::class)->open($data['id'], $data['account'], $data['actor']),
            'close' => app(ManageCashierShift::class)->close($data['id'], '0', 'QA concurrent close', $data['actor']),
            'checkout' => app(PostInvoice::class)->handle($data['payload'], $data['actor']),
        };
        echo json_encode(['status' => 'ok', 'id' => $result['id']]);
    } catch (ValidationException) {
        echo json_encode(['status' => 'rejected']);
    }
    exit;
}
$server = DB::connection()->getPdo();
$name = 'pos_shift_qa_'.bin2hex(random_bytes(8));
$created = false;
$workers = [];
$check = function (bool $ok, string $label): void {
    if (! $ok) {
        throw new RuntimeException($label);
    } echo 'PASS: '.$label.PHP_EOL;
};
try {
    $server->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $created = true;
    $connect($name);
    $check(Artisan::call('migrate', ['--force' => true]) === 0, 'Migration native disposable');
    $invoice = SalesReturnFixture::seed();
    $actor = (int) DB::table('sales_invoices')->where('id', $invoice['id'])->value('actor_id');
    $repo = app(CashierShiftRepository::class);
    $action = app(ManageCashierShift::class);
    $active = $repo->active($actor);
    $account = $active['account_id'];
    $position = fn () => app(CashDrawerPosition::class)->read($account)->balance;
    $action->close($active['id'], $position(), '', $actor);
    $pair = function (array $jobs) use (&$workers, $name): array {
        $running = [];
        foreach ($jobs as $job) {
            $p = new Process([PHP_BINARY, __FILE__, '--worker'], base_path(), timeout: 60);
            $p->setInput(json_encode($job + ['database' => $name]));
            $p->start();
            $workers[] = $running[] = $p;
        }
        $results = [];
        foreach ($running as $p) {
            $p->wait();
            if (! $p->isSuccessful()) {
                throw new RuntimeException('Worker gagal.');
            } $results[] = json_decode($p->getOutput(), true, flags: JSON_THROW_ON_ERROR);
        }

        return $results;
    };
    $other = User::factory()->create()->id;
    $jobs = array_map(fn ($id) => ['mode' => 'open', 'actor' => $id, 'account' => $account, 'id' => strtolower((string) Str::ulid())], [$actor, $other]);
    $results = $pair($jobs);
    $check(count(array_filter($results, fn ($r) => $r['status'] === 'ok')) === 1 && DB::table('cashier_shifts')->whereNotNull('active_account_id')->count() === 1, 'Dua worker berebut laci: tepat satu shift aktif');
    $row = DB::table('cashier_shifts')->where('active_account_id', $account)->first();
    $action->close($row->id, $position(), '', (int) $row->actor_id);
    $id = strtolower((string) Str::ulid());
    $action->open($id, $account, $actor);
    $line = $invoice['lines'][0];
    $quote = app(CheckCart::class)->handle($invoice['warehouse_id'], $invoice['customer_id'], [new CartLineData($line['item_id'], $line['unit_id'], '1')]);
    $payload = ['idempotency_key' => strtolower((string) Str::ulid()), 'shift_id' => $id, 'warehouse_id' => $invoice['warehouse_id'], 'customer_id' => $invoice['customer_id'], 'quote_fingerprint' => $quote['fingerprint'],
        'lines' => [['item_id' => $line['item_id'], 'unit_id' => $line['unit_id'], 'quantity' => '1']], 'payments' => [['method_id' => $invoice['payments']['payments'][0]['method_id'], 'amount' => $quote['total']]]];
    $paid = app(PostInvoice::class)->handle($payload, $actor);
    $action->close($id, $position(), '', $actor);
    $check(app(PostInvoice::class)->handle($payload, $actor)['id'] === $paid['id'] && DB::table('sales_invoices')->count() === 2, 'Checkout terikat shift dan replay sesudah tutup tetap satu invoice');
    $id = strtolower((string) Str::ulid());
    $action->open($id, $account, $actor);
    $payload['idempotency_key'] = strtolower((string) Str::ulid());
    $payload['shift_id'] = $id;
    $results = $pair([['mode' => 'checkout', 'actor' => $actor, 'payload' => $payload], ['mode' => 'close', 'actor' => $actor, 'id' => $id]]);
    $closed = $repo->find($id);
    $check($results[1]['status'] === 'ok' && $closed['status'] === 'closed' && $closed['expected_cash'] === $position(), 'Checkout lawan tutup: snapshot saldo memuat seluruh penjualan committed sebelum tutup');
    $count = DB::table('sales_invoices')->count();
    $check($count === ($results[0]['status'] === 'ok' ? 3 : 2), 'Checkout kalah tidak membuat invoice/stok/pembayaran parsial');
    if ($results[0]['status'] === 'ok') {
        $check(app(PostInvoice::class)->handle($payload, $actor)['id'] === $results[0]['id'], 'Replay invoice tetap berhasil setelah shift ditutup');
    } else {
        try {
            app(PostInvoice::class)->handle($payload, $actor);
            throw new RuntimeException('Checkout tanpa shift lolos.');
        } catch (ValidationException) {
            $check(DB::table('sales_invoices')->count() === $count, 'Shift tertutup menolak checkout baru');
        }
    }
    $column = collect(DB::select('SHOW COLUMNS FROM cashier_shifts'))->firstWhere('Field', 'opening_balance');
    $check(strtolower($column->Type) === 'decimal(38,6)', 'Nominal shift exact6 native');
} finally {
    foreach ($workers as $worker) {
        if ($worker->isRunning()) {
            $worker->stop();
        }
    }
    DB::purge('shift_qa');
    if ($created) {
        $server->exec('DROP DATABASE `'.$name.'`');
        echo 'PASS: database disposable dibersihkan, data toko tidak dimutasi.'.PHP_EOL;
    }
}
