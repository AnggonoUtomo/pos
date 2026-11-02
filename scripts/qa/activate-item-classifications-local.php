<?php

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
    fwrite(STDERR, 'FAIL aktivasi klasifikasi: '.class_basename($error).' pada '.basename($error->getFile()).':'.$error->getLine().'. DDL tidak rollback otomatis.'.PHP_EOL);
    exit(1);
});
if (DB::connection()->getDriverName() !== 'mysql' || DB::selectOne('SELECT DATABASE() AS db')->db !== DB::getDatabaseName()) {
    throw new LogicException('Target MySQL tidak cocok.');
}
$lock = 'pos_classification_activation_'.substr(hash('sha256', DB::getDatabaseName()), 0, 20);
if ((int) DB::selectOne('SELECT GET_LOCK(?, 0) AS acquired', [$lock])->acquired !== 1) {
    throw new LogicException('Aktivasi lain berlangsung.');
}
try {
    $hash = static function (string $table, array $columns, ?array $ids = null): string {
        $query = DB::table($table)->select($columns);
        if ($ids !== null) {
            $query->whereIn('id', $ids);
        }
        foreach ($columns as $column) {
            $query->orderBy($column);
        }
        $state = hash_init('sha256');
        foreach ($query->cursor() as $row) {
            hash_update($state, json_encode((array) $row, JSON_THROW_ON_ERROR));
        }

        return hash_final($state);
    };
    $tables = Schema::getTableListing(schema: DB::getDatabaseName(), schemaQualified: false);
    $migrationIds = DB::table('migrations')->pluck('id')->all();
    $before = [];
    foreach ($tables as $table) {
        if (in_array($table, ['cache', 'cache_locks', 'sessions'], true)) {
            continue;
        }
        $columns = Schema::getColumnListing($table);
        $before[$table] = ['columns' => $columns, 'hash' => $hash($table, $columns, $table === 'migrations' ? $migrationIds : null)];
    }
    if (Artisan::call('migrate', ['--path' => 'app/Modules/Inventory/Items/Database/Migrations/2026_11_02_000001_add_item_categories_and_brands.php', '--force' => true]) !== 0) {
        throw new RuntimeException('Migration gagal.');
    }
    foreach ($before as $table => $snapshot) {
        $columns = Schema::getColumnListing($table);
        $extra = array_diff($columns, $snapshot['columns']);
        if (array_diff($snapshot['columns'], $columns) !== [] || array_diff($extra, $table === 'items' ? ['category_id', 'brand_id'] : []) !== []
            || ! hash_equals($snapshot['hash'], $hash($table, $snapshot['columns'], $table === 'migrations' ? $migrationIds : null))) {
            throw new RuntimeException('Data/kolom/grant existing berubah.');
        }
    }
    if (array_diff(Schema::getTableListing(schema: DB::getDatabaseName(), schemaQualified: false), $tables, ['item_categories', 'item_brands']) !== []) {
        throw new RuntimeException('Tabel baru di luar scope.');
    }
    foreach (['item_categories', 'item_brands'] as $table) {
        if (! in_array($table, $tables, true) && DB::table($table)->exists()) {
            throw new RuntimeException('Tidak boleh seed master toko.');
        }
    }
    if (! in_array('category_id', $before['items']['columns'], true) && DB::table('items')->where(fn ($q) => $q->whereNotNull('category_id')->orWhereNotNull('brand_id'))->exists()) {
        throw new RuntimeException('Tidak boleh backfill barang lama.');
    }
    if (DB::table('migrations')->whereNotIn('id', $migrationIds)->where('migration', '!=', '2026_11_02_000001_add_item_categories_and_brands')->exists()) {
        throw new RuntimeException('Migration di luar scope.');
    }
    if (Artisan::call('route:cache') !== 0) {
        throw new RuntimeException('Route cache gagal.');
    }
    echo 'PASS: migration klasifikasi additive dan route cache aktif; fingerprint data/kolom/grant existing identik, tanpa seed/backfill/repricing/transaksi.'.PHP_EOL;
} finally {
    DB::selectOne('SELECT RELEASE_LOCK(?) AS released', [$lock]);
}
