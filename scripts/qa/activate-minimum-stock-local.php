<?php

use App\Modules\Inventory\Stock\Database\Seeders\StockAccessSeeder;
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
    fwrite(STDERR, 'FAIL aktivasi minimum: '.class_basename($error).' pada '.basename($error->getFile()).':'.$error->getLine().'. DDL tidak rollback otomatis.'.PHP_EOL);
    exit(1);
});
if (DB::connection()->getDriverName() !== 'mysql' || ! in_array(app()->environment(), ['local', 'production'], true)
    || DB::selectOne('SELECT DATABASE() AS db')->db !== DB::getDatabaseName()) {
    throw new LogicException('Konfigurasi MySQL toko tidak cocok.');
}
$lock = 'pos_minimum_activation_'.substr(hash('sha256', DB::getDatabaseName()), 0, 24);
if ((int) DB::selectOne('SELECT GET_LOCK(?, 0) AS acquired', [$lock])->acquired !== 1) {
    throw new LogicException('Aktivasi lain berlangsung.');
}
try {
    $hash = static function (string $table, ?array $ids = null): string {
        $query = DB::table($table);
        if ($ids !== null) {
            $query->whereIn('id', $ids);
        }
        foreach (Schema::getColumnListing($table) as $column) {
            $query->orderBy($column);
        }
        $state = hash_init('sha256');
        foreach ($query->cursor() as $row) {
            hash_update($state, json_encode((array) $row, JSON_THROW_ON_ERROR));
        }

        return hash_final($state);
    };
    $metadata = [];
    foreach (['permissions', 'migrations'] as $table) {
        $metadata[$table] = DB::table($table)->pluck('id')->all();
    }
    $tables = Schema::getTableListing(schema: DB::getDatabaseName(), schemaQualified: false);
    $before = [];
    foreach ($tables as $table) {
        if (! in_array($table, ['cache', 'cache_locks', 'sessions'], true)) {
            $before[$table] = ['columns' => Schema::getColumnListing($table), 'hash' => $hash($table, $metadata[$table] ?? null)];
        }
    }
    if (Artisan::call('migrate', ['--path' => 'app/Modules/Inventory/Stock/Database/Migrations/2026_11_01_000001_create_stock_minimums.php', '--force' => true]) !== 0) {
        throw new RuntimeException('Migration gagal.');
    }
    DB::transaction(function () use ($before, $metadata, $hash, $tables): void {
        app(StockAccessSeeder::class)->run();
        app(StockAccessSeeder::class)->run();
        foreach ($before as $table => $snapshot) {
            if ($snapshot['columns'] !== Schema::getColumnListing($table) || ! hash_equals($snapshot['hash'], $hash($table, $metadata[$table] ?? null))) {
                throw new RuntimeException('Data/schema existing berubah.');
            }
        }
        if (! in_array('stock_minimums', $tables, true) && DB::table('stock_minimums')->exists()) {
            throw new RuntimeException('Aktivasi tidak boleh mengatur minimum toko.');
        }
        if (DB::table('permissions')->whereNotIn('id', $metadata['permissions'])->where(fn ($q) => $q->whereNotIn('name', ['inventory.stock.minimums.view', 'inventory.stock.minimums.manage'])->orWhere('guard_name', '!=', 'web'))->exists()) {
            throw new RuntimeException('Permission di luar scope.');
        }
        if (DB::table('migrations')->whereNotIn('id', $metadata['migrations'])->where('migration', '!=', '2026_11_01_000001_create_stock_minimums')->exists()) {
            throw new RuntimeException('Migration di luar scope.');
        }
    });
    if (Artisan::call('route:cache') !== 0) {
        throw new RuntimeException('Route cache gagal.');
    }
    echo 'PASS: migration/catalog additive, fingerprint seluruh data/grant existing identik, route cache aktif; tanpa demo atau batas minimum toko.'.PHP_EOL;
} finally {
    DB::selectOne('SELECT RELEASE_LOCK(?) AS released', [$lock]);
}
