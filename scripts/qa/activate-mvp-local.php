<?php

use App\Modules\Inventory\Stock\Database\Seeders\StockAccessSeeder;
use App\Modules\Inventory\Transfers\Database\Seeders\TransfersAccessSeeder;
use App\Modules\Platform\CompanySettings\Database\Seeders\CompanySettingsAccessSeeder;
use App\Modules\Reporting\OperationalReports\Database\Seeders\OperationalReportsAccessSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
set_exception_handler(static function (Throwable $error): never {
    fwrite(STDERR, 'FAIL aktivasi MVP: '.class_basename($error).' pada '.basename($error->getFile()).':'.$error->getLine().PHP_EOL);
    exit(1);
});
if (! app()->environment('local') || DB::connection()->getDriverName() !== 'mysql') {
    throw new LogicException('Aktivasi hanya database local/MySQL.');
}
$backups = glob(storage_path('app/private/backups/*.sql')) ?: [];
if (! array_filter($backups, fn ($file) => filemtime($file) > time() - 3600 && filesize($file) > 0)) {
    throw new LogicException('Jalankan pos:backup --verify sebelum aktivasi.');
}
$name = 'pos_mvp_activation';
if ((int) DB::selectOne('SELECT GET_LOCK(?, 0) AS acquired', [$name])->acquired !== 1) {
    throw new LogicException('Aktivasi lain sedang berjalan.');
}
$snapshot = static function (string $table, array $columns, ?array $ids = null): string {
    $hash = hash_init('sha256');
    $query = DB::table($table)->select($columns);
    if ($ids !== null) {
        $query->whereIn('id', $ids);
    }
    foreach ($columns as $column) {
        $query->orderBy($column);
    }
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
        $before[$table] = ['columns' => $columns, 'ids' => $ids, 'hash' => $snapshot($table, $columns, $ids)];
    }
    $paths = ['app/Modules/Inventory/Stock/Database/Migrations/2026_10_24_000001_add_operational_stock.php',
        'app/Modules/Inventory/Transfers/Database/Migrations/2026_10_24_000002_add_transfer_posting.php',
        'app/Modules/Sales/POS/Database/Migrations/2026_10_24_000003_add_invoice_pricing.php',
        'app/Modules/Platform/CompanySettings/Database/Migrations/2026_10_24_000004_create_company_settings.php',
        'app/Modules/Sales/POS/Database/Migrations/2026_10_24_000005_add_invoice_company_snapshot.php'];
    if (Artisan::call('migrate', ['--path' => $paths, '--force' => true]) !== 0) {
        throw new RuntimeException('Migration MVP gagal.');
    }
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    foreach ([StockAccessSeeder::class, TransfersAccessSeeder::class, CompanySettingsAccessSeeder::class, OperationalReportsAccessSeeder::class] as $seeder) {
        app($seeder)->run();
    }
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    foreach ($before as $table => $state) {
        if (! hash_equals($state['hash'], $snapshot($table, $state['columns'], $state['ids']))) {
            throw new RuntimeException('Data existing berubah: '.$table.'. Hentikan transaksi dan periksa tanpa reset data.');
        }
    }
    echo 'PASS: lima migration additive dan katalog permission aktif; data/grant/ledger existing tetap identik.'.PHP_EOL;
} finally {
    DB::selectOne('SELECT RELEASE_LOCK(?) AS released', [$name]);
}
