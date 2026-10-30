<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
set_exception_handler(static function (Throwable $error): never {
    fwrite(STDERR, 'FAIL aktivasi pagination: '.class_basename($error).' pada '.basename($error->getFile()).':'.$error->getLine().PHP_EOL);
    exit(1);
});
if (DB::connection()->getDriverName() !== 'mysql' || ! in_array(app()->environment(), ['local', 'production'], true)) {
    throw new LogicException('Aktivasi hanya MySQL toko.');
}
if (! array_filter(glob(base_path('backups/*.sql')) ?: [], fn ($path) => filemtime($path) > time() - 3600 && filesize($path) > 0)) {
    throw new LogicException('Jalankan backup verified terlebih dahulu.');
}
$fingerprint = static function (string $table, array $columns, ?array $ids): string {
    $query = DB::table($table)->select($columns);
    if ($ids !== null) {
        $query->whereIn('id', $ids);
    }
    foreach ($columns as $column) {
        $query->orderBy($column);
    }
    $hash = hash_init('sha256');
    foreach ($query->cursor() as $row) {
        hash_update($hash, json_encode((array) $row, JSON_THROW_ON_ERROR));
    }

    return hash_final($hash);
};
if ((int) DB::selectOne('SELECT GET_LOCK(?, 0) AS acquired', ['pos_table_pagination_activation'])->acquired !== 1) {
    throw new LogicException('Aktivasi lain sedang berjalan.');
}
try {
    $before = [];
    foreach (Schema::getTableListing(schema: DB::getDatabaseName(), schemaQualified: false) as $table) {
        if (in_array($table, ['cache', 'cache_locks', 'sessions'], true)) {
            continue;
        }
        $columns = Schema::getColumnListing($table);
        $ids = $table === 'migrations' ? DB::table($table)->pluck('id')->all() : null;
        $before[$table] = ['columns' => $columns, 'ids' => $ids, 'hash' => $fingerprint($table, $columns, $ids)];
    }
    $path = 'app/Modules/Platform/OperationalSettings/Database/Migrations/2026_10_29_000001_add_table_per_page_to_operational_settings.php';
    if (Artisan::call('migrate', ['--path' => $path, '--force' => true]) !== 0) {
        throw new RuntimeException('Migration gagal.');
    }
    foreach ($before as $table => $state) {
        if (! hash_equals($state['hash'], $fingerprint($table, $state['columns'], $state['ids']))) {
            throw new RuntimeException('Data existing berubah; periksa tanpa reset.');
        }
    }
    if (! in_array((int) DB::table('operational_settings')->value('table_per_page'), [10, 25, 50, 100], true)) {
        throw new RuntimeException('Preferensi tabel tidak valid.');
    }
    echo 'PASS: migration pagination aktif; seluruh kolom/data existing identik, tanpa reset/seeder/grant.'.PHP_EOL;
} finally {
    DB::selectOne('SELECT RELEASE_LOCK(?) AS released', ['pos_table_pagination_activation']);
}
