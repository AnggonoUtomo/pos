<?php

use App\Modules\Purchasing\PurchaseOrders\Application\Queries\PurchaseMasterChoices;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
set_exception_handler(static function (Throwable $error): never {
    fwrite(STDERR, 'FAIL aktivasi autocomplete: '.class_basename($error).' pada '.basename($error->getFile()).':'.$error->getLine().PHP_EOL);
    exit(1);
});
if (DB::connection()->getDriverName() !== 'mysql' || DB::selectOne('SELECT DATABASE() AS db')->db !== DB::getDatabaseName()) {
    throw new LogicException('Target MySQL tidak cocok.');
}
$hash = static function (): array {
    $result = [];
    foreach (Schema::getTableListing(schema: DB::getDatabaseName(), schemaQualified: false) as $table) {
        if (in_array($table, ['cache', 'cache_locks', 'sessions'], true)) {
            continue;
        }
        $columns = Schema::getColumnListing($table);
        $query = DB::table($table);
        foreach ($columns as $column) {
            $query->orderBy($column);
        }
        $state = hash_init('sha256');
        foreach ($query->cursor() as $row) {
            hash_update($state, json_encode((array) $row, JSON_THROW_ON_ERROR));
        }
        $result[$table] = ['columns' => $columns, 'hash' => hash_final($state)];
    }

    return $result;
};
$before = $hash();
$options = app(PurchaseMasterChoices::class)->creationOptions();
if (! isset($options['units'], $options['levels']) || Artisan::call('route:cache') !== 0) {
    throw new RuntimeException('Metadata atau route cache gagal.');
}
foreach (['master-options', 'master-choice'] as $route) {
    if (! app('router')->getRoutes()->hasNamedRoute('purchasing.purchase-orders.'.$route)) {
        throw new RuntimeException('Route metadata belum aktif.');
    }
}
if ($before !== $hash()) {
    throw new RuntimeException('Data/schema/grant berubah.');
}
echo 'PASS: metadata native MySQL, route cache aktif; fingerprint seluruh data/schema/grant identik; tanpa migration/seed/transaksi.'.PHP_EOL;
