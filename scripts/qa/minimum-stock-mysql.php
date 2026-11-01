<?php

use App\Modules\Inventory\Stock\Application\Actions\ManageMinimumStock;
use App\Modules\Inventory\Stock\Application\Contracts\MinimumStockAlerts;
use App\Modules\Inventory\Stock\Application\Contracts\MinimumStockRepository;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Fixtures\SalesReturnFixture;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
set_exception_handler(static function (Throwable $error): never {
    fwrite(STDERR, 'FAIL minimum MySQL: '.class_basename($error).' pada '.basename($error->getFile()).':'.$error->getLine().PHP_EOL);
    exit(1);
});
if (DB::connection()->getDriverName() !== 'mysql') {
    throw new LogicException('QA memerlukan MySQL.');
}
$original = config('database.connections.mysql');
$name = 'pos_minimum_qa_'.bin2hex(random_bytes(8));
if (! preg_match('/^pos_minimum_qa_[a-f0-9]{16}$/D', $name) || $name === DB::getDatabaseName()) {
    throw new LogicException('Target tidak aman.');
}
$server = DB::connection('mysql')->getPdo();
$check = static function (bool $condition, string $label): void {
    if (! $condition) {
        throw new RuntimeException($label);
    } echo 'PASS: '.$label.PHP_EOL;
};
try {
    $server->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    config(['database.connections.minimum_qa' => array_replace($original, ['database' => $name, 'url' => null]), 'cache.default' => 'array']);
    DB::setDefaultConnection('minimum_qa');
    $check(DB::selectOne('SELECT DATABASE() AS db')->db === $name, 'Koneksi database disposable');
    $app->detectEnvironment(fn () => 'testing');
    $check(Artisan::call('migrate', ['--force' => true]) === 0, 'Migration native');
    SalesReturnFixture::seed();
    $warehouse = DB::table('warehouses')->where('code', 'WH-MAIN')->value('id');
    $item = DB::table('items')->where('sku', 'DEMO-CCTV-IP-2MP')->value('id');
    $actor = DB::table('users')->value('id');
    $before = SalesReturnFixture::fingerprint();
    $action = app(ManageMinimumStock::class);
    $body = ['warehouse_id' => $warehouse, 'item_id' => $item, 'minimum_quantity' => '99999999999999.999999', 'is_active' => true];
    $id = $action->create($body, $actor);
    $record = app(MinimumStockRepository::class)->find($id);
    $check($record['minimum_quantity'] === $body['minimum_quantity'], 'DECIMAL(20,6) exact');
    $action->update($id, ['minimum_quantity' => '6.000001', 'is_active' => true, 'version' => 0], $actor);
    $check(app(MinimumStockAlerts::class)->summary()['low_count'] === 1, 'Saldo nyata <= minimum');
    try {
        $action->create($body, $actor);
        throw new RuntimeException('Duplikat diterima.');
    } catch (InvalidArgumentException) {
        echo 'PASS: pair duplikat ditolak'.PHP_EOL;
    }
    $action->remove($id, 1, false, $actor);
    try {
        $action->create($body, $actor);
        throw new RuntimeException('Duplikat terhapus diterima.');
    } catch (InvalidArgumentException) {
        echo 'PASS: pair terhapus tetap unik'.PHP_EOL;
    }
    $check(app(MinimumStockAlerts::class)->summary()['low_count'] === 0, 'Soft delete menonaktifkan alert');
    $action->remove($id, 2, true, $actor);
    $check(app(MinimumStockAlerts::class)->summary()['low_count'] === 1, 'Restore dan summary');
    $check(hash_equals($before, SalesReturnFixture::fingerprint()), 'Seluruh stok/FIFO/penjualan/payment identik');
} finally {
    DB::disconnect('minimum_qa');
    $server->exec('DROP DATABASE IF EXISTS `'.$name.'`');
    echo 'PASS: cleanup database QA'.PHP_EOL;
}
