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
    throw new LogicException('QA barcode hanya menggunakan SQLite disposable.');
}
$ledger = [];
foreach (['stock_balances', 'stock_movements', 'fifo_layers', 'fifo_consumptions', 'sales_invoices', 'payment_records'] as $table) {
    $ledger[$table] = DB::table($table)->orderBy('id')->get()->toArray();
}
echo json_encode(['ledger' => hash('sha256', json_encode($ledger, JSON_THROW_ON_ERROR)),
    'orders' => DB::table('purchase_orders')->select(['status'])->get(),
    'lines' => DB::table('purchase_order_lines')->select(['quantity', 'unit_cost'])->get()], JSON_THROW_ON_ERROR);
