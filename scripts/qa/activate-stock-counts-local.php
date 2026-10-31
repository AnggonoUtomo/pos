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
    fwrite(STDERR, 'FAIL aktivasi opname: '.class_basename($error).' pada '.basename($error->getFile()).':'.$error->getLine().'. DDL tidak rollback otomatis.'.PHP_EOL);
    exit(1);
});
if (DB::connection()->getDriverName() !== 'mysql' || ! in_array(app()->environment(), ['local', 'production'], true)
    || DB::selectOne('SELECT DATABASE() AS db')->db !== DB::getDatabaseName()) {
    throw new LogicException('Aktivasi hanya konfigurasi MySQL toko yang cocok.');
}
$lock = 'pos_stock_counts_activation_'.substr(hash('sha256', DB::getDatabaseName()), 0, 24);
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
    $before = [];
    $tables = Schema::getTableListing(schema: DB::getDatabaseName(), schemaQualified: false);
    foreach ($tables as $table) {
        if (in_array($table, ['cache', 'cache_locks', 'sessions'], true)) {
            continue;
        }
        $before[$table] = ['columns' => Schema::getColumnListing($table), 'hash' => $hash($table, $metadata[$table] ?? null)];
    }
    $path = 'app/Modules/Inventory/Stock/Database/Migrations/2026_10_31_000001_create_stock_counts.php';
    if (Artisan::call('migrate', ['--path' => $path, '--force' => true]) !== 0) {
        throw new RuntimeException('Migration gagal.');
    }
    DB::transaction(function () use ($before, $hash, $metadata, $tables): void {
        app(StockAccessSeeder::class)->run();
        app(StockAccessSeeder::class)->run();
        foreach ($before as $table => $snapshot) {
            if ($snapshot['columns'] !== Schema::getColumnListing($table)
                || ! hash_equals($snapshot['hash'], $hash($table, $metadata[$table] ?? null))) {
                throw new RuntimeException('Data/schema existing berubah.');
            }
        }
        foreach (array_diff(['stock_counts', 'stock_count_lines'], $tables) as $table) {
            if (DB::table($table)->exists()) {
                throw new RuntimeException('Aktivasi tidak boleh membuat sesi/hitungan.');
            }
        }
        $allowed = ['inventory.stock.counts.view', 'inventory.stock.counts.manage', 'inventory.stock.counts.post'];
        if (DB::table('permissions')->whereNotIn('id', $metadata['permissions'])
            ->where(fn ($q) => $q->whereNotIn('name', $allowed)->orWhere('guard_name', '!=', 'web'))->exists()) {
            throw new RuntimeException('Permission baru di luar scope.');
        }
        if (DB::table('migrations')->whereNotIn('id', $metadata['migrations'])
            ->where('migration', '!=', '2026_10_31_000001_create_stock_counts')->exists()) {
            throw new RuntimeException('Migration baru di luar scope.');
        }
    });
    if (Artisan::call('route:cache') !== 0) {
        throw new RuntimeException('Route cache gagal.');
    }
    echo 'PASS: migration/catalog additive, seluruh data/grant existing identik, route cache aktif; tanpa sesi demo atau freeze gudang toko.'.PHP_EOL;
} finally {
    DB::selectOne('SELECT RELEASE_LOCK(?) AS released', [$lock]);
}
