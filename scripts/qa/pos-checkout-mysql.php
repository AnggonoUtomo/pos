<?php

use App\Models\User;
use App\Modules\Finance\Payments\Database\Seeders\PaymentsDemoSeeder;
use App\Modules\Inventory\Items\Database\Seeders\ItemsDemoSeeder;
use App\Modules\Inventory\Stock\Database\Seeders\StockDemoSeeder;
use App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder;
use App\Modules\Inventory\Warehouses\Application\Contracts\WarehouseLookup;
use App\Modules\Inventory\Warehouses\Database\Seeders\WarehousesDemoSeeder;
use App\Modules\Sales\Parties\Database\Seeders\ItemPricesDemoSeeder;
use App\Modules\Sales\Parties\Database\Seeders\PartiesDemoSeeder;
use App\Modules\Sales\POS\Application\Actions\PostInvoice;
use App\Modules\Sales\POS\Application\DTOs\CartLineData;
use App\Modules\Sales\POS\Application\Queries\CheckCart;
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
    throw new LogicException('QA hanya MySQL local dengan database generated.');
}
$original = config('database.connections.mysql');
$validName = static fn ($name) => is_string($name) && preg_match('/^pos_checkout_qa_[a-f0-9]{16}$/D', $name) && $name !== $original['database'];
if (($argv[1] ?? null) === '--worker') {
    try {
        $input = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
        if (! $validName($input['database'] ?? null)) {
            throw new LogicException('Database worker tidak aman.');
        }
        config(['database.connections.checkout_worker' => array_replace($original, ['database' => $input['database']])]);
        DB::setDefaultConnection('checkout_worker');
        $app->detectEnvironment(fn () => 'testing');
        echo json_encode(['thread' => DB::selectOne('SELECT CONNECTION_ID() AS id')->id])."\n";
        flush();
        try {
            $invoice = app(PostInvoice::class)->handle($input['checkout'], $input['actor']);
            echo json_encode(['status' => 'posted', 'id' => $invoice['id']])."\n";
        } catch (ValidationException $exception) {
            echo json_encode(['status' => 'rejected', 'fields' => array_keys($exception->errors())])."\n";
        }
        exit(0);
    } catch (Throwable) {
        fwrite(STDERR, "FAIL: worker checkout gagal.\n");
        exit(1);
    }
}
$name = 'pos_checkout_qa_'.bin2hex(random_bytes(8));
if (! $validName($name)) {
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
    config(['database.connections.checkout_qa' => $connection, 'database.connections.checkout_peer' => $connection]);
    DB::setDefaultConnection('checkout_qa');
    $app->detectEnvironment(fn () => 'testing');
    if (Artisan::call('migrate', ['--force' => true]) !== 0) {
        throw new RuntimeException('Migration QA gagal.');
    }
    foreach ([WarehousesDemoSeeder::class, UnitsDemoSeeder::class, ItemsDemoSeeder::class, PartiesDemoSeeder::class,
        ItemPricesDemoSeeder::class, StockDemoSeeder::class, PaymentsDemoSeeder::class] as $seeder) {
        app($seeder)->run();
    }
    $actor = User::factory()->create()->id;
    $item = DB::table('items')->where('sku', 'DEMO-CCTV-IP-2MP')->first();
    $warehouse = DB::table('warehouses')->where('code', 'WH-MAIN')->value('id');
    $customer = DB::table('customers')->where('code', 'DEMO-INSTALLER')->value('id');
    $cash = DB::table('payment_methods')->where('code', 'TUNAI')->value('id');
    DB::table('item_prices')->where('item_id', $item->id)->update(['amount' => '50000']);
    $migration = require app_path('Modules/Sales/POS/Database/Migrations/2026_10_16_000003_create_sales_invoices.php');
    $before = DB::table('stock_balances')->orderBy('id')->get()->toJson();
    $migration->down();
    $migration->up();
    $check($before === DB::table('stock_balances')->orderBy('id')->get()->toJson(), 'Native invoice up/down empty mempertahankan ledger existing');
    $payload = static function (string $quantity) use ($item, $warehouse, $customer, $cash): array {
        $quote = app(CheckCart::class)->handle($warehouse, $customer, [new CartLineData($item->id, $item->base_unit_id, $quantity)]);

        return ['idempotency_key' => strtolower((string) Str::ulid()), 'warehouse_id' => $warehouse, 'customer_id' => $customer,
            'quote_fingerprint' => $quote['fingerprint'], 'lines' => [['item_id' => $item->id, 'unit_id' => $item->base_unit_id, 'quantity' => $quantity]],
            'payments' => [['method_id' => $cash, 'amount' => $quote['subtotal']]]];
    };
    $peer = DB::connection('checkout_peer');
    DB::statement('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    $peer->statement('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    $sample = $payload('2');
    $unitName = DB::table('units')->where('id', $item->base_unit_id)->value('name');
    foreach (['price', 'customer', 'unit', 'method', 'sale_flag'] as $case) {
        DB::beginTransaction();
        try {
            foreach (['item_prices', 'customers', 'units', 'payment_methods', 'item_units'] as $table) {
                DB::table($table)->get();
            }
            match ($case) {
                'price' => $peer->table('item_prices')->where('item_id', $item->id)->update(['amount' => '49000']),
                'customer' => $peer->table('customers')->where('id', $customer)->update(['is_active' => false]),
                'unit' => $peer->table('units')->where('id', $item->base_unit_id)->update(['name' => 'Satuan Berubah']),
                'method' => $peer->table('payment_methods')->where('id', $cash)->update(['is_active' => false]),
                'sale_flag' => $peer->table('item_units')->where('item_id', $item->id)->update(['is_sale_unit' => false]),
            };
            $rejected = false;
            $fields = [];
            try {
                app(PostInvoice::class)->handle($sample, $actor);
            } catch (ValidationException $exception) {
                $rejected = true;
                $fields = array_keys($exception->errors());
            }
            $expected = match ($case) {
                'price', 'unit' => 'quote_fingerprint', 'customer' => 'customer_id', 'method' => 'cart', 'sale_flag' => 'lines.0.unit_id',
            };
            $check($rejected && in_array($expected, $fields, true) && DB::table('sales_invoices')->count() === 0 && DB::table('stock_issues')->count() === 0 && DB::table('payment_records')->count() === 0,
                'RR stale '.$case.': current read menolak, seluruh invoice/Stock/Finance rollback');
        } finally {
            DB::rollBack();
            $peer->table('item_prices')->where('item_id', $item->id)->update(['amount' => '50000']);
            $peer->table('customers')->where('id', $customer)->update(['is_active' => true]);
            $peer->table('units')->where('id', $item->base_unit_id)->update(['name' => $unitName]);
            $peer->table('payment_methods')->where('id', $cash)->update(['is_active' => true]);
            $peer->table('item_units')->where('item_id', $item->id)->update(['is_sale_unit' => true]);
        }
    }
    foreach (['replay', 'last_stock'] as $scenario) {
        $input = $payload($scenario === 'replay' ? '2' : '4');
        $workerInput = $input;
        if ($scenario === 'last_stock') {
            $workerInput['idempotency_key'] = strtolower((string) Str::ulid());
        }
        DB::beginTransaction();
        try {
            app(WarehouseLookup::class)->findActive($warehouse, lock: true);
            $worker = proc_open([PHP_BINARY, __FILE__, '--worker'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path(), null, ['bypass_shell' => true]);
            if (! is_resource($worker)) {
                throw new RuntimeException('Worker gagal dibuat.');
            }
            fwrite($pipes[0], json_encode(['database' => $name, 'checkout' => $workerInput, 'actor' => $actor], JSON_THROW_ON_ERROR));
            fclose($pipes[0]);
            stream_set_timeout($pipes[1], 20);
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
            $check($waiting, $scenario.': worker checkout terbukti LOCK WAIT pada gudang');
            $main = app(PostInvoice::class)->handle($input, $actor);
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
        $check($exit === 0 && ($scenario === 'replay' ? ($outcome['id'] ?? null) === $main['id'] : ($outcome['status'] ?? null) === 'rejected' && in_array('lines.0.quantity', $outcome['fields'] ?? [], true)),
            $scenario.': retry identik satu invoice, checkout stok terakhir yang kalah ditolak');
    }
    $check(DB::table('sales_invoices')->count() === 2 && DB::table('stock_issues')->count() === 2 && DB::table('payment_receipts')->count() === 2
        && DB::table('activity_log')->where('log_name', 'sales.pos')->where('event', 'posted')->count() === 2
        && DB::table('stock_balances')->where('warehouse_id', $warehouse)->where('item_id', $item->id)->value('quantity') === '2.000000',
        'Tidak ada invoice/payment/stock/audit ganda atau sisa mutation checkout kalah');
    $retry = $payload('1');
    DB::beginTransaction();
    try {
        DB::table('sales_invoices')->where('idempotency_key', $retry['idempotency_key'])->first();
        DB::setDefaultConnection('checkout_peer');
        try {
            $posted = app(PostInvoice::class)->handle($retry, $actor);
        } finally {
            DB::setDefaultConnection('checkout_qa');
        }
        $check($posted === app(PostInvoice::class)->handle($retry, $actor), 'RR retry membaca invoice/lines committed peer sesudah snapshot kosong');
    } finally {
        DB::rollBack();
    }
    $invoice = DB::table('sales_invoices')->first();
    $restricted = false;
    try {
        DB::table('sales_invoices')->where('id', $invoice->id)->delete();
    } catch (QueryException $exception) {
        if (($exception->errorInfo[1] ?? null) !== 1451) {
            throw $exception;
        }
        $restricted = true;
    }
    $check($restricted, 'FK RESTRICT melindungi header invoice posted');
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
    foreach (['checkout_qa', 'checkout_peer'] as $connectionName) {
        if (config('database.connections.'.$connectionName) !== null) {
            $connection = DB::connection($connectionName);
            while ($connection->transactionLevel() > 0) {
                $connection->rollBack();
            }
            DB::purge($connectionName);
        }
    }
    if ($created) {
        $server->exec('DROP DATABASE `'.$name.'`');
        echo "PASS: database generated dibersihkan; tanpa penjualan di database pengguna.\n";
    }
}
if ($failure !== null) {
    fwrite(STDERR, 'FAIL: '.get_class($failure).' pada '.basename($failure->getFile()).':'.$failure->getLine()."\n");
    exit(1);
}
