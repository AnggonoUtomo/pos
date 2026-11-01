<?php

use App\Modules\Purchasing\PurchaseOrders\Application\Contracts\PurchaseCostLookup;
use App\Modules\Sales\Parties\Application\Contracts\ItemPriceCatalog;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
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
    fwrite(STDERR, 'FAIL aktivasi markup: '.class_basename($error).' pada '.basename($error->getFile()).':'.$error->getLine().PHP_EOL);
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
$checked = 0;
foreach (DB::table('purchase_order_lines')->distinct()->pluck('item_id') as $itemId) {
    $cost = app(PurchaseCostLookup::class)->latestForItem($itemId);
    if ($cost === null) {
        continue;
    }
    $expected = (string) BigDecimal::of($cost->unitCost)->toScale(2, RoundingMode::HALF_UP);
    try {
        $actual = app(ItemPriceCatalog::class)->markup($cost->unitCost, $cost->conversionRatio, $cost->conversionRatio, '0');
    } catch (InvalidArgumentException $error) {
        if ($expected !== '0.00') {
            throw $error;
        }
        $checked++;

        continue;
    }
    if ($actual !== $expected) {
        throw new RuntimeException('Normalisasi biaya native tidak cocok.');
    }
    $checked++;
}
if (Artisan::call('route:cache') !== 0 || $before !== $hash()) {
    throw new RuntimeException('Cache gagal atau data/schema/grant berubah.');
}
echo 'PASS: native MySQL lookup/normalisasi '.$checked.' barang, fingerprint seluruh data/schema/grant existing identik; route cache aktif, tanpa migration/seed/repricing.'.PHP_EOL;
