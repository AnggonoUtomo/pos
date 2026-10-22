<?php

use App\Modules\Purchasing\Returns\Database\Seeders\ReturnsAccessSeeder;
use Illuminate\Contracts\Console\Kernel;
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

if (! app()->environment('local') || config('database.default') !== 'mysql') {
    throw new LogicException('Aktivasi Returns hanya MySQL local.');
}

$mysql = config('database.connections.mysql');
if (! empty($mysql['url']) || ! is_string($mysql['database'] ?? null)) {
    throw new LogicException('Aktivasi membutuhkan koneksi bernama tanpa URL override.');
}
$base = DB::connection('mysql')->getPdo();
$baseName = DB::selectOne('SELECT DATABASE() AS name')->name ?? null;
if ($baseName !== $mysql['database']) {
    throw new LogicException('Database aktif berbeda dari konfigurasi MySQL.');
}

$qaName = null;
foreach ($argv as $argument) {
    if (str_starts_with($argument, '--qa-database=')) {
        $qaName = substr($argument, strlen('--qa-database='));
    }
}
if ($qaName !== null && (! preg_match('/^pos_purchase_returns_activation_qa_[a-f0-9]{16}$/D', $qaName)
    || $qaName === $baseName)) {
    throw new LogicException('Target aktivasi QA tidak valid.');
}

$target = $qaName ?? $baseName;
$connectionName = $qaName === null ? 'mysql' : 'purchase_returns_activation_qa';
if ($qaName !== null) {
    config(['database.connections.'.$connectionName => array_replace($mysql, ['database' => $qaName, 'url' => null]),
        'database.default' => $connectionName]);
    DB::purge($connectionName);
    DB::setDefaultConnection($connectionName);
}
config(['cache.default' => 'array']);
if ((DB::selectOne('SELECT DATABASE() AS name')->name ?? null) !== $target) {
    throw new LogicException('Koneksi target tidak sesuai database aktivasi.');
}
app(PermissionRegistrar::class)->forgetCachedPermissions();

$lockName = 'pos_returns_activate_'.substr(hash('sha256', $target), 0, 24);
$lock = DB::selectOne('SELECT GET_LOCK(?, 10) AS acquired', [$lockName]);
if ((int) ($lock->acquired ?? 0) !== 1) {
    throw new RuntimeException('Lock aktivasi Returns tidak diperoleh.');
}

$hash = static fn ($row): string => hash('sha256', json_encode($row, JSON_THROW_ON_ERROR));
$snapshotTables = static function () use ($hash): array {
    $snapshot = [];
    foreach (Schema::getTableListing(schema: DB::getDatabaseName(), schemaQualified: false) as $table) {
        if (in_array($table, ['migrations', 'permissions', 'cache', 'cache_locks'], true)) {
            continue;
        }
        $columns = Schema::getColumnListing($table);
        $rows = DB::table($table)->select($columns)->get()->map($hash)->sort()->values()->all();
        $snapshot[$table] = ['columns' => $columns, 'rows' => $hash($rows)];
    }

    ksort($snapshot);

    return $snapshot;
};
$snapshotRows = static fn (string $table): array => DB::table($table)->get()->mapWithKeys(fn ($row) => [$row->id => $hash($row)])->all();
$migrationNames = ['2026_10_19_000003_create_purchase_return_drafts', '2026_10_21_000001_add_purchase_return_posting'];
$newPermissions = ['purchasing.returns.manage', 'purchasing.returns.view', 'purchasing.returns.post'];

$failure = null;
try {
    foreach (['purchase_orders', 'purchase_order_lines', 'permissions', 'roles', 'activity_log'] as $table) {
        if (! Schema::hasTable($table)) {
            throw new LogicException('Baseline table tidak tersedia untuk aktivasi additive.');
        }
    }

    $beforeTables = $snapshotTables();
    $beforePermissions = $snapshotRows('permissions');
    $beforeMigrations = $snapshotRows('migrations');
    $path = 'app/Modules/Purchasing/Returns/Database/Migrations';
    if (Artisan::call('migrate', ['--path' => $path, '--force' => true]) !== 0) {
        throw new RuntimeException('Migration Returns gagal.');
    }
    $migrationOutput = Artisan::output();
    app(ReturnsAccessSeeder::class)->run();

    foreach ($beforeTables as $table => $snapshot) {
        $columns = Schema::getColumnListing($table);
        $expectedColumns = $snapshot['columns'];
        $allowedColumns = $table === 'purchase_returns'
            ? ['stock_issue_id', 'posted_from_version', 'posted_at', 'posted_by'] : [];
        $actualNewColumns = array_values(array_diff($columns, $expectedColumns));
        $expectedNewColumns = array_values(array_diff($allowedColumns, $expectedColumns));
        sort($actualNewColumns);
        sort($expectedNewColumns);
        if ($actualNewColumns !== $expectedNewColumns || array_diff($expectedColumns, $columns) !== []) {
            throw new RuntimeException('Kolom tabel existing berubah di luar schema posting Returns.');
        }
        $rows = DB::table($table)->select($expectedColumns)->get()->map($hash)->sort()->values()->all();
        if ($hash($rows) !== $snapshot['rows']) {
            throw new RuntimeException('Tabel/data/grant existing berubah saat aktivasi.');
        }
    }
    foreach ($beforePermissions as $id => $value) {
        if ($value !== $hash(DB::table('permissions')->where('id', $id)->first())) {
            throw new RuntimeException('Permission existing berubah saat aktivasi.');
        }
    }
    foreach ($beforeMigrations as $id => $value) {
        if ($value !== $hash(DB::table('migrations')->where('id', $id)->first())) {
            throw new RuntimeException('Metadata migration existing berubah saat aktivasi.');
        }
    }

    $newMigrationRows = DB::table('migrations')->whereIn('migration', $migrationNames)->count();
    $addedMigrationRows = DB::table('migrations')->count() - count($beforeMigrations);
    $expectedAdded = 0;
    foreach ($migrationNames as $migrationName) {
        $row = DB::table('migrations')->where('migration', $migrationName)->sole();
        if (! isset($beforeMigrations[$row->id])) {
            $expectedAdded++;
        }
    }
    if ($newMigrationRows !== count($migrationNames) || $addedMigrationRows !== $expectedAdded) {
        throw new RuntimeException('Migration Returns tidak terdaftar tepat satu kali per perubahan.');
    }
    foreach ($newPermissions as $permission) {
        if (DB::table('permissions')->where('name', $permission)->where('guard_name', 'web')->count() !== 1) {
            throw new RuntimeException('Catalog permission Returns tidak lengkap/unik.');
        }
    }
    foreach (['purchase_returns', 'purchase_return_lines'] as $table) {
        if (! Schema::hasTable($table) || (! isset($beforeTables[$table]) && DB::table($table)->count() !== 0)) {
            throw new RuntimeException('Aktivasi harus mempertahankan data Returns atau membuat tabel kosong.');
        }
    }
    foreach (['stock_issue_id', 'posted_from_version', 'posted_at', 'posted_by'] as $column) {
        if (! Schema::hasColumn('purchase_returns', $column)) {
            throw new RuntimeException('Kolom posting Returns tidak tersedia: '.$column);
        }
    }

    if ($migrationOutput !== '') {
        echo $migrationOutput;
    }
    echo 'PASS: schema dan tiga permission Returns additive; tidak ada grant, demo, atau transaksi.'.PHP_EOL;
    echo 'PASS: kolom/data tabel existing, roles/grants, audit, dan permission lama tetap identik.'.PHP_EOL;
} catch (Throwable $error) {
    $failure = $error;
} finally {
    DB::selectOne('SELECT RELEASE_LOCK(?) AS released', [$lockName]);
    if ($qaName !== null) {
        DB::purge($connectionName);
    }
}
if ($failure !== null) {
    fwrite(STDERR, 'FAIL: '.$failure->getMessage().PHP_EOL);
    exit(1);
}
