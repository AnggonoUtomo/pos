<?php

use App\Modules\Finance\Payments\Database\Seeders\PaymentsAccessSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
set_exception_handler(static function (Throwable $error): never {
    fwrite(STDERR, 'FAIL aktivasi Buku Kas: '.class_basename($error).' '.basename($error->getFile()).':'.$error->getLine().PHP_EOL);
    exit(1);
});
if (DB::connection()->getDriverName() !== 'mysql' || ! in_array(app()->environment(), ['local', 'production'], true)) {
    throw new LogicException('Aktivasi hanya MySQL toko.');
}
$backups = array_merge(glob(base_path('backups/*.sql')) ?: [], glob(storage_path('app/private/backups/*.sql')) ?: []);
if (! array_filter($backups, fn ($path) => filemtime($path) > time() - 3600 && filesize($path) > 0)) {
    throw new LogicException('Backup terbaru wajib tersedia.');
}
$path = 'app/Modules/Finance/Payments/Database/Migrations/2026_10_28_000001_create_cashbook.php';
$fingerprint = static function (string $table, ?array $ids = null): string {
    $columns = Schema::getColumnListing($table);
    $query = DB::table($table);
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
if ((int) DB::selectOne('SELECT GET_LOCK(?, 0) AS acquired', ['pos_cashbook_activation'])->acquired !== 1) {
    throw new LogicException('Aktivasi lain sedang berjalan.');
}
try {
    $before = [];
    foreach (Schema::getTableListing(schema: DB::getDatabaseName(), schemaQualified: false) as $table) {
        if (in_array($table, ['cache', 'cache_locks', 'sessions'], true)) {
            continue;
        }
        $ids = in_array($table, ['permissions', 'migrations'], true) ? DB::table($table)->pluck('id')->all() : null;
        $before[$table] = ['ids' => $ids, 'hash' => $fingerprint($table, $ids)];
    }
    if (Artisan::call('migrate', ['--path' => $path, '--force' => true]) !== 0) {
        throw new RuntimeException('Migration gagal.');
    }
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    app(PaymentsAccessSeeder::class)->run();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    foreach ($before as $table => $state) {
        if (! hash_equals($state['hash'], $fingerprint($table, $state['ids']))) {
            throw new RuntimeException('Data existing berubah; hentikan dan periksa tanpa reset.');
        }
    }
    echo 'PASS: migration additive dan catalog permission aktif; fingerprint seluruh data/grant/ledger existing identik.'.PHP_EOL;
    echo 'INFO: opening dan mutasi produksi tidak diisi otomatis.'.PHP_EOL;
} finally {
    DB::selectOne('SELECT RELEASE_LOCK(?) AS released', ['pos_cashbook_activation']);
}
