<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$database = config('database.connections.sqlite.database');
if (! app()->environment('testing') || config('database.default') !== 'sqlite'
    || realpath(dirname($database)) !== realpath(__DIR__.'/../../storage/framework/testing')
    || ! preg_match('/^parties-browser-[a-f0-9]{16}\.sqlite$/D', basename($database))) {
    throw new LogicException('QA checkout hanya SQLite disposable.');
}
$item = DB::table('items')->where('sku', 'DEMO-CCTV-IP-2MP')->value('id');
if (($argv[1] ?? null) === '--reprice') {
    DB::table('item_prices')->where('item_id', $item)->update(['amount' => '55000']);
    echo 'QA price updated.';
    exit;
}
echo json_encode(['invoices' => DB::table('sales_invoices')->count(), 'issues' => DB::table('stock_issues')->count(),
    'receipts' => DB::table('payment_receipts')->count(), 'records' => DB::table('payment_records')->count(),
    'stock' => (string) DB::table('stock_balances')->where('item_id', $item)->value('quantity')], JSON_THROW_ON_ERROR);
