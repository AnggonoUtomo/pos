<?php

use App\Modules\Inventory\Transfers\Database\Seeders\TransfersAccessSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
set_exception_handler(static function (Throwable $error): never {
    fwrite(STDERR, 'FAIL: Aktivasi transfer gagal; periksa '.basename($error->getFile()).':'.$error->getLine().'. DDL tidak di-rollback otomatis.'.PHP_EOL);
    if ($error instanceof QueryException) {
        fwrite(STDERR, 'SQLSTATE '.($error->errorInfo[0] ?? 'unknown').' code '.($error->errorInfo[1] ?? 'unknown').PHP_EOL);
        foreach ($error->getTrace() as $frame) {
            if (($frame['file'] ?? null) === __FILE__) {
                fwrite(STDERR, 'Activation frame line '.($frame['line'] ?? 0).PHP_EOL);
            }
        }
    }
    exit(1);
});
if (! app()->environment('local') || config('database.default') !== 'mysql') {
    throw new LogicException('Aktivasi hanya local/MySQL.');
}
if (count($argv) !== 1) {
    $name = $argv[2] ?? null;
    if (count($argv) !== 3 || $argv[1] !== '--qa-database' || ! is_string($name)
        || ! preg_match('/^pos_transfer_activation_qa_[a-f0-9]{16}$/D', $name)
        || $name === config('database.connections.mysql.database')) {
        throw new LogicException('Override hanya database QA disposable.');
    }
    config(['database.connections.mysql.database' => $name, 'database.connections.mysql.url' => null, 'cache.default' => 'array']);
    DB::purge('mysql');
    config(['permission.cache.store' => 'array']);
    app(PermissionRegistrar::class)->initializeCache();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
}
if (DB::selectOne('SELECT DATABASE() AS name')->name !== DB::getDatabaseName()) {
    throw new LogicException('Database aktual tidak sesuai konfigurasi aktivasi.');
}
$lock = 'pos_transfer_activate_'.substr(hash('sha256', DB::getDatabaseName()), 0, 32);
if ((int) DB::selectOne('SELECT GET_LOCK(?, 0) AS acquired', [$lock])->acquired !== 1) {
    throw new LogicException('Aktivasi lain sedang berlangsung.');
}
try {
    foreach (['users', 'permissions', 'roles', 'model_has_roles', 'model_has_permissions', 'role_has_permissions',
        'warehouses', 'items', 'item_units', 'units', 'activity_log'] as $table) {
        if (! Schema::hasTable($table)) {
            throw new LogicException('Baseline transaksi belum lengkap.');
        }
    }
    $tables = Schema::getTableListing(schema: DB::getDatabaseName(), schemaQualified: false);
    $hash = static fn ($value) => hash('sha256', json_encode($value, JSON_THROW_ON_ERROR));
    $before = [];
    foreach ($tables as $table) {
        if (in_array($table, ['cache', 'cache_locks', 'permissions', 'migrations'], true)) {
            continue;
        }
        $columns = Schema::getColumnListing($table);
        $before[$table] = ['columns' => $columns, 'hash' => $hash(DB::table($table)->get($columns)->map($hash)->sort()->values()->all())];
    }
    $metadata = [];
    foreach (['permissions', 'migrations'] as $table) {
        $metadata[$table] = DB::table($table)->get()->mapWithKeys(fn ($row) => [$row->id => $hash($row)])->all();
    }
    $paths = ['app/Modules/Inventory/Transfers/Database/Migrations/2026_10_23_000003_create_stock_transfer_drafts.php'];
    $newTables = ['stock_transfers', 'stock_transfer_lines'];
    if (Artisan::call('migrate', ['--path' => $paths, '--force' => true]) !== 0) {
        throw new RuntimeException('Migration gagal; catalog/grant/demo tidak dijalankan.');
    }
    DB::transaction(function () use ($before, $metadata, $hash, $paths, $newTables, $tables): void {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        app(TransfersAccessSeeder::class)->run();
        foreach ($before as $table => $snapshot) {
            if ($snapshot['columns'] !== Schema::getColumnListing($table)
                || $snapshot['hash'] !== $hash(DB::table($table)->get($snapshot['columns'])->map($hash)->sort()->values()->all())) {
                throw new RuntimeException('Data/schema existing berubah; rollback catalog.');
            }
        }
        foreach ($metadata as $table => $rows) {
            foreach ($rows as $id => $value) {
                if ($value !== $hash(DB::table($table)->where('id', $id)->first())) {
                    throw new RuntimeException('Metadata existing berubah; rollback catalog.');
                }
            }
        }
        foreach (array_diff($newTables, $tables) as $table) {
            if (DB::table($table)->exists()) {
                throw new RuntimeException('Aktivasi membuat draft yang tidak diizinkan.');
            }
        }
        $actualTables = Schema::getTableListing(schema: DB::getDatabaseName(), schemaQualified: false);
        if (array_diff($actualTables, $tables, $newTables) !== [] || array_diff($tables, $actualTables) !== []) {
            throw new RuntimeException('Perubahan tabel di luar scope aktivasi.');
        }
        $migrationNames = array_map(fn ($path) => basename($path, '.php'), $paths);
        if (DB::table('migrations')->whereNotIn('id', array_keys($metadata['migrations']))->whereNotIn('migration', $migrationNames)->exists()
            || DB::table('migrations')->whereIn('migration', $migrationNames)->count() !== 1) {
            throw new RuntimeException('Metadata migration di luar scope aktivasi.');
        }
        $allowed = ['inventory.transfers.view', 'inventory.transfers.manage'];
        if (DB::table('permissions')->whereNotIn('id', array_keys($metadata['permissions']))
            ->where(fn ($query) => $query->whereNotIn('name', $allowed)->orWhere('guard_name', '!=', 'web'))->exists()
            || DB::table('permissions')->where('name', 'inventory.transfers.manage')->where('guard_name', 'web')->count() !== 1) {
            throw new RuntimeException('Catalog permission di luar scope aktivasi.');
        }
    });
    echo "PASS: migration draft transfer Ran, catalog additive tanpa grant/reset/demo/ledger.\n";
    echo "PASS: tabel/kolom/data existing, user/role/grant, source/Stock/Finance/audit identik.\n";
} finally {
    DB::selectOne('SELECT RELEASE_LOCK(?) AS released', [$lock]);
}
