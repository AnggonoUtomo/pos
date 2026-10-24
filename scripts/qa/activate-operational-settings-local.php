<?php

use App\Modules\Platform\OperationalSettings\Database\Seeders\OperationalSettingsAccessSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
set_exception_handler(static function (Throwable $error): never {
    fwrite(STDERR, 'FAIL aktivasi operasional: '.class_basename($error).' pada '.basename($error->getFile()).':'.$error->getLine().PHP_EOL);
    exit(1);
});
if (! app()->environment('local') || DB::connection()->getDriverName() !== 'mysql') {
    throw new LogicException('Aktivasi hanya local/MySQL.');
}
$backups = glob(storage_path('app/private/backups/*.sql')) ?: [];
if (! array_filter($backups, fn ($file) => filemtime($file) > time() - 3600 && filesize($file) > 0)) {
    throw new LogicException('Buat backup terverifikasi sebelum aktivasi.');
}
$lock = 'pos_operational_settings_activation';
if ((int) DB::selectOne('SELECT GET_LOCK(?, 0) AS acquired', [$lock])->acquired !== 1) {
    throw new LogicException('Aktivasi sedang berjalan.');
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
try {
    $before = [];
    foreach (DB::select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'") as $entry) {
        $table = array_values((array) $entry)[0];
        if (in_array($table, ['cache', 'cache_locks'], true)) {
            continue;
        }
        $columns = DB::connection()->getSchemaBuilder()->getColumnListing($table);
        $ids = in_array($table, ['permissions', 'migrations'], true) ? DB::table($table)->pluck('id')->all() : null;
        $before[$table] = [$columns, $ids, $fingerprint($table, $columns, $ids)];
    }
    if (Artisan::call('migrate', ['--path' => 'app/Modules/Platform/OperationalSettings/Database/Migrations', '--force' => true]) !== 0) {
        throw new RuntimeException('Migration operasional gagal.');
    }
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    app(OperationalSettingsAccessSeeder::class)->run();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    foreach ($before as $table => [$columns, $ids, $hash]) {
        if (! hash_equals($hash, $fingerprint($table, $columns, $ids))) {
            throw new RuntimeException('Data existing berubah; hentikan aktivasi dan periksa tanpa reset data.');
        }
    }
    if ((bool) DB::table('operational_settings')->value('backup_enabled')) {
        throw new RuntimeException('Backup otomatis tidak boleh aktif dari aktivasi awal.');
    }
    echo 'PASS: pengaturan operasional aktif; data/grant/ledger existing identik, backup otomatis tetap mati.'.PHP_EOL;
} finally {
    DB::selectOne('SELECT RELEASE_LOCK(?) AS released', [$lock]);
}
