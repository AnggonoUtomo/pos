<?php

use App\Models\User;
use App\Modules\Finance\Payments\Database\Seeders\PaymentsDemoSeeder;
use App\Modules\Inventory\Items\Database\Seeders\ItemsDemoSeeder;
use App\Modules\Inventory\Stock\Application\Actions\ManageOpening;
use App\Modules\Inventory\Stock\Application\Actions\PostOpening;
use App\Modules\Inventory\Stock\Database\Seeders\StockDemoSeeder;
use App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder;
use App\Modules\Inventory\Warehouses\Database\Seeders\WarehousesDemoSeeder;
use App\Modules\Sales\Parties\Database\Seeders\ItemPricesDemoSeeder;
use App\Modules\Sales\Parties\Database\Seeders\PartiesDemoSeeder;
use App\Modules\Sales\POS\Application\Actions\ManageHeldCart;
use App\Modules\Sales\POS\Application\Actions\PostInvoice;
use App\Modules\Sales\POS\Application\Contracts\HeldCartRepository;
use App\Modules\Sales\POS\Application\DTOs\CartLineData;
use App\Modules\Sales\POS\Application\Queries\CheckCart;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
set_exception_handler(static function (Throwable $error): never {
    fwrite(STDERR, 'FAIL held carts MySQL: '.class_basename($error).' line '.$error->getLine().PHP_EOL);
    exit(1);
});
if (DB::connection()->getDriverName() !== 'mysql') {
    throw new LogicException('MySQL diperlukan.');
}
$original = DB::connection()->getConfig();
unset($original['name']);
$safe = static fn ($name) => is_string($name) && preg_match('/^pos_held_qa_[a-f0-9]{16}$/D', $name) && $name !== $original['database'];
$connect = function (string $name) use ($app, $original, $safe): void {
    if (! $safe($name)) {
        throw new LogicException('Database disposable tidak aman.');
    }
    config(['database.connections.held_qa' => array_replace($original, ['database' => $name, 'url' => null]), 'cache.default' => 'array', 'hashing.bcrypt.rounds' => 4]);
    DB::setDefaultConnection('held_qa');
    Schema::clearResolvedInstance('db.schema');
    $app->detectEnvironment(fn () => 'testing');
    if (DB::selectOne('SELECT DATABASE() AS db')->db !== $name) {
        throw new LogicException('Database tidak cocok.');
    }
};
if (($argv[1] ?? '') === '--worker') {
    $input = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
    $connect($input['database']);
    auth()->setUser(User::findOrFail($input['actor']));
    echo json_encode(['thread' => DB::selectOne('SELECT CONNECTION_ID() AS id')->id]).PHP_EOL;
    flush();
    try {
        $invoice = app(PostInvoice::class)->handle($input['checkout'], $input['actor']);
        echo json_encode(['status' => 'posted', 'id' => $invoice['id']]).PHP_EOL;
    } catch (ValidationException $e) {
        echo json_encode(['status' => 'rejected', 'fields' => array_keys($e->errors())]).PHP_EOL;
    }
    exit(0);
}
$server = DB::connection()->getPdo();
$name = 'pos_held_qa_'.bin2hex(random_bytes(8));
if (! $safe($name)) {
    throw new LogicException('Target tidak aman.');
}
$created = false;
$workers = [];
try {
    $server->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $created = true;
    $connect($name);
    if (Artisan::call('migrate', ['--force' => true, '--database' => 'held_qa']) !== 0) {
        throw new RuntimeException('Migration gagal.');
    }
    foreach ([WarehousesDemoSeeder::class, UnitsDemoSeeder::class, ItemsDemoSeeder::class, PartiesDemoSeeder::class,
        ItemPricesDemoSeeder::class, StockDemoSeeder::class, PaymentsDemoSeeder::class] as $seeder) {
        app($seeder)->run();
    }
    $actor = User::factory()->create();
    auth()->setUser($actor);
    $item = DB::table('items')->where('sku', 'DEMO-CCTV-IP-2MP')->first();
    $warehouses = DB::table('warehouses')->orderBy('code')->pluck('id', 'code');
    $customer = DB::table('customers')->where('code', 'DEMO-INSTALLER')->value('id');
    $cash = DB::table('payment_methods')->where('code', 'TUNAI')->value('id');
    DB::table('item_prices')->where('item_id', $item->id)->update(['amount' => '50000.00']);
    $other = $warehouses->first(fn ($id) => $id !== $warehouses['WH-MAIN']);
    $opening = app(ManageOpening::class)->create(['warehouse_id' => $other, 'notes' => 'QA held concurrency',
        'lines' => [['item_id' => $item->id, 'unit_id' => $item->base_unit_id, 'quantity' => '8', 'unit_cost' => '50000']]]);
    app(PostOpening::class)->handle($opening);
    $heldId = strtolower((string) Str::ulid());
    $intent = ['version' => 0, 'warehouse_id' => $warehouses['WH-MAIN'], 'customer_id' => $customer,
        'lines' => [['item_id' => $item->id, 'unit_id' => $item->base_unit_id, 'quantity' => '1']]];
    $held = app(ManageHeldCart::class)->save($heldId, $intent, $actor->id);
    $payloads = [];
    foreach ([$warehouses['WH-MAIN'], $other] as $warehouse) {
        $quote = app(CheckCart::class)->handle($warehouse, $customer, [new CartLineData($item->id, $item->base_unit_id, '1')]);
        $payloads[] = ['idempotency_key' => strtolower((string) Str::ulid()), 'warehouse_id' => $warehouse, 'customer_id' => $customer,
            'lines' => $intent['lines'], 'quote_fingerprint' => $quote['fingerprint'], 'held_cart' => ['id' => $held['id'], 'version' => 1],
            'payments' => [['method_id' => $cash, 'amount' => $quote['total']]]];
    }
    DB::beginTransaction();
    app(HeldCartRepository::class)->findOwned($heldId, $actor->id, lock: true);
    foreach ($payloads as $checkout) {
        $worker = new Process([PHP_BINARY, __FILE__, '--worker'], base_path(), timeout: 90);
        $worker->setInput(json_encode(['database' => $name, 'actor' => $actor->id, 'checkout' => $checkout], JSON_THROW_ON_ERROR));
        $worker->start();
        $workers[] = $worker;
    }
    $waiting = false;
    for ($i = 0; $i < 200; $i++) {
        $statement = $server->prepare('SELECT COUNT(DISTINCT w.REQUESTING_ENGINE_TRANSACTION_ID) FROM performance_schema.data_lock_waits w JOIN performance_schema.data_locks l ON l.ENGINE_LOCK_ID = w.REQUESTING_ENGINE_LOCK_ID WHERE l.OBJECT_SCHEMA = ? AND l.OBJECT_NAME = ?');
        $statement->execute([$name, 'pos_held_carts']);
        if ((int) $statement->fetchColumn() >= 2) {
            $waiting = true;
            break;
        }
        usleep(100000);
    }
    if (! $waiting) {
        throw new RuntimeException('Dua worker tidak mencapai held row lock.');
    }
    DB::rollBack();
    $results = [];
    foreach ($workers as $worker) {
        $worker->wait();
        if (! $worker->isSuccessful()) {
            throw new RuntimeException('Worker gagal.');
        }
        $lines = array_values(array_filter(explode("\n", trim($worker->getOutput()))));
        $results[] = json_decode(end($lines), true, flags: JSON_THROW_ON_ERROR);
    }
    if (count(array_filter($results, fn ($r) => $r['status'] === 'posted')) !== 1
        || count(array_filter($results, fn ($r) => $r['status'] === 'rejected' && in_array('held_cart', $r['fields'], true))) !== 1
        || DB::table('sales_invoices')->count() !== 1 || DB::table('stock_issues')->count() !== 1 || DB::table('payment_receipts')->count() !== 1) {
        throw new RuntimeException('Double checkout invariant gagal.');
    }
    $winner = DB::table('sales_invoices')->sole();
    $input = collect($payloads)->firstWhere('idempotency_key', $winner->idempotency_key);
    if (app(PostInvoice::class)->handle($input, $actor->id)['id'] !== $winner->id) {
        throw new RuntimeException('Replay gagal.');
    }
    echo 'PASS: native media-independent schema ULID, dua warehouse/dua key menunggu held row; satu invoice/stock issue/payment, satu ditolak, replay aman.'.PHP_EOL;
} finally {
    if (DB::transactionLevel() > 0) {
        DB::rollBack();
    }
    foreach ($workers as $worker) {
        if ($worker->isRunning()) {
            $worker->stop(1);
        }
    }
    DB::purge('held_qa');
    if ($created && $safe($name)) {
        $server->exec('DROP DATABASE `'.$name.'`');
    }
}
