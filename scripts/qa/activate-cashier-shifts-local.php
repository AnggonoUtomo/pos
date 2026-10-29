<?php

use App\Modules\Sales\POS\Database\Seeders\POSAccessSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
set_exception_handler(static function (Throwable $error): never {
    fwrite(STDERR, 'FAIL aktivasi Shift Kasir: '.class_basename($error).' '.basename($error->getFile()).':'.$error->getLine().PHP_EOL);
    exit(1);
});
if (DB::connection()->getDriverName() !== 'mysql' || ! in_array(app()->environment(), ['local', 'production'], true)) {
    throw new LogicException('Aktivasi hanya MySQL toko.');
}
if (! array_filter(glob(base_path('backups/*.sql')) ?: [], fn ($path) => filemtime($path) > time() - 3600 && filesize($path) > 0)) {
    throw new LogicException('Backup verified terbaru wajib tersedia.');
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
if ((int) DB::selectOne('SELECT GET_LOCK(?, 0) AS acquired', ['pos_shift_activation'])->acquired !== 1) {
    throw new LogicException('Aktivasi lain sedang berjalan.');
}
try {
    $before = [];
    foreach (Schema::getTableListing(schema: DB::getDatabaseName(), schemaQualified: false) as $table) {
        if (in_array($table, ['cache', 'cache_locks', 'sessions'], true)) {
            continue;
        }
        $columns = Schema::getColumnListing($table);
        $ids = in_array($table, ['permissions', 'migrations'], true) ? DB::table($table)->pluck('id')->all() : null;
        $before[$table] = ['columns' => $columns, 'ids' => $ids, 'hash' => $fingerprint($table, $columns, $ids)];
    }
    $path = 'app/Modules/Sales/POS/Database/Migrations/2026_10_28_000002_create_cashier_shifts.php';
    if (Artisan::call('migrate', ['--path' => $path, '--force' => true]) !== 0) {
        throw new RuntimeException('Migration gagal.');
    }
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    app(POSAccessSeeder::class)->run();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    foreach ($before as $table => $state) {
        if (! hash_equals($state['hash'], $fingerprint($table, $state['columns'], $state['ids']))) {
            throw new RuntimeException('Data existing berubah; periksa tanpa reset.');
        }
    }
    if (Artisan::call('route:cache') !== 0) {
        throw new RuntimeException('Cache route gagal.');
    }
    echo 'PASS: migration/catalog additive, route cache diperbarui; data/master/ledger/user/grant existing identik.'.PHP_EOL;
    echo 'INFO: saldo awal dan shift produksi tidak diisi otomatis.'.PHP_EOL;
} finally {
    DB::selectOne('SELECT RELEASE_LOCK(?) AS released', ['pos_shift_activation']);
}
