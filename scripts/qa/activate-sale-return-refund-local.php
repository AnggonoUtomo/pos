<?php

use App\Modules\Sales\Returns\Database\Seeders\ReturnsAccessSeeder;
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
    fwrite(STDERR, 'FAIL: Aktivasi refund gagal; periksa '.basename($error->getFile()).':'.$error->getLine().'. DDL tidak di-rollback otomatis.'.PHP_EOL);
    exit(1);
});
if (! app()->environment('local') || config('database.default') !== 'mysql') {
    throw new LogicException('Aktivasi hanya local/MySQL.');
}
if (count($argv) !== 1) {
    $name = $argv[2] ?? null;
    if (count($argv) !== 3 || $argv[1] !== '--qa-database' || ! is_string($name)
        || ! preg_match('/^pos_refund_activation_qa_[a-f0-9]{16}$/D', $name)
        || $name === config('database.connections.mysql.database')) {
        throw new LogicException('Override hanya database QA disposable.');
    }
    config(['database.connections.mysql.database' => $name, 'database.connections.mysql.url' => null, 'cache.default' => 'array']);
    DB::purge('mysql');
}
if (DB::selectOne('SELECT DATABASE() AS name')->name !== DB::getDatabaseName()) {
    throw new LogicException('Database aktual tidak sesuai konfigurasi aktivasi.');
}
$lock = 'pos_refund_activate_'.substr(hash('sha256', DB::getDatabaseName()), 0, 32);
if ((int) DB::selectOne('SELECT GET_LOCK(?, 0) AS acquired', [$lock])->acquired !== 1) {
    throw new LogicException('Aktivasi lain sedang berlangsung.');
}
try {
    foreach (['users', 'permissions', 'roles', 'model_has_roles', 'model_has_permissions', 'role_has_permissions',
        'payment_accounts', 'payment_methods', 'sales_invoices', 'sales_invoice_lines', 'sales_returns', 'sales_return_lines',
        'stock_receipts', 'stock_return_allocations', 'fifo_consumptions', 'activity_log'] as $table) {
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
    $paths = ['app/Modules/Finance/Payments/Database/Migrations/2026_10_19_000001_create_payment_refunds.php',
        'app/Modules/Sales/Returns/Database/Migrations/2026_10_19_000002_create_sales_return_settlements.php'];
    $newTables = ['payment_refunds', 'payment_refund_lines', 'sales_return_refund_scopes', 'sales_return_settlements', 'sales_return_refund_allocations'];
    if (Artisan::call('migrate', ['--path' => $paths, '--force' => true]) !== 0) {
        throw new RuntimeException('Migration gagal; catalog/grant/demo tidak dijalankan.');
    }
    DB::transaction(function () use ($before, $metadata, $hash, $paths, $newTables, $tables): void {
        app(ReturnsAccessSeeder::class)->run();
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
                throw new RuntimeException('Aktivasi membuat data refund yang tidak diizinkan.');
            }
        }
        $actualTables = Schema::getTableListing(schema: DB::getDatabaseName(), schemaQualified: false);
        if (array_diff($actualTables, $tables, $newTables) !== [] || array_diff($tables, $actualTables) !== []) {
            throw new RuntimeException('Perubahan tabel di luar scope aktivasi.');
        }
        $migrationNames = array_map(fn ($path) => basename($path, '.php'), $paths);
        if (DB::table('migrations')->whereNotIn('id', array_keys($metadata['migrations']))->whereNotIn('migration', $migrationNames)->exists()
            || DB::table('migrations')->whereIn('migration', $migrationNames)->count() !== 2) {
            throw new RuntimeException('Metadata migration di luar scope aktivasi.');
        }
        $allowed = ['sales.returns.view', 'sales.returns.manage', 'sales.returns.post', 'sales.returns.refund'];
        if (DB::table('permissions')->whereNotIn('id', array_keys($metadata['permissions']))
            ->where(fn ($query) => $query->whereNotIn('name', $allowed)->orWhere('guard_name', '!=', 'web'))->exists()
            || DB::table('permissions')->where('name', 'sales.returns.refund')->where('guard_name', 'web')->count() !== 1) {
            throw new RuntimeException('Catalog permission di luar scope aktivasi.');
        }
    });
    echo "PASS: dua migration refund Ran, catalog additive tanpa grant/reset/demo/payout.\n";
    echo "PASS: tabel/kolom/data existing, user/role/grant, source/Stock/Finance/audit identik.\n";
} finally {
    DB::selectOne('SELECT RELEASE_LOCK(?) AS released', [$lock]);
}
