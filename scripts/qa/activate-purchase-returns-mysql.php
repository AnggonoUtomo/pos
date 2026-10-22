<?php

use App\Models\User;
use App\Modules\Finance\Payments\Database\Seeders\PaymentsDemoSeeder;
use App\Modules\Inventory\Items\Database\Seeders\ItemsDemoSeeder;
use App\Modules\Inventory\Units\Database\Seeders\UnitsDemoSeeder;
use App\Modules\Inventory\Warehouses\Database\Seeders\WarehousesDemoSeeder;
use App\Modules\Purchasing\PurchaseOrders\Database\Seeders\PurchaseOrdersAccessSeeder;
use App\Modules\Purchasing\PurchaseOrders\Database\Seeders\PurchaseOrdersDemoSeeder;
use App\Modules\Purchasing\PurchaseOrders\Database\Seeders\PurchaseOrdersReceiptDemoSeeder;
use App\Modules\Purchasing\Returns\Application\Actions\ManagePurchaseReturnDraft;
use App\Modules\Purchasing\Suppliers\Database\Seeders\SuppliersDemoSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('local') || config('database.default') !== 'mysql') {
    throw new LogicException('QA aktivasi Returns hanya local/MySQL disposable.');
}

$original = config('database.connections.mysql');
if (! empty($original['url']) || ! is_string($original['database'] ?? null)) {
    throw new LogicException('QA aktivasi membutuhkan koneksi bernama tanpa URL override.');
}
$server = DB::connection('mysql')->getPdo();
$baseDatabase = DB::selectOne('SELECT DATABASE() AS name')->name ?? null;
if ($baseDatabase !== $original['database']) {
    throw new LogicException('Database koneksi dasar tidak sesuai konfigurasi aktif.');
}

$name = 'pos_purchase_returns_activation_qa_'.bin2hex(random_bytes(8));
if (! preg_match('/^pos_purchase_returns_activation_qa_[a-f0-9]{16}$/D', $name) || $name === $baseDatabase) {
    throw new LogicException('Nama database aktivasi QA tidak aman.');
}
$created = false;
$check = static function (bool $ok, string $label): void {
    if (! $ok) {
        throw new RuntimeException('FAIL: '.$label);
    }
    echo 'PASS: '.$label.PHP_EOL;
};
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
$activation = base_path('scripts/qa/apply-purchase-returns-local.php');

try {
    $server->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $created = true;
    config(['database.connections.purchase_returns_activation_qa' => array_replace($original, ['database' => $name, 'url' => null]),
        'database.default' => 'purchase_returns_activation_qa', 'cache.default' => 'array']);
    DB::purge('purchase_returns_activation_qa');
    DB::setDefaultConnection('purchase_returns_activation_qa');
    $check((DB::selectOne('SELECT DATABASE() AS name')->name ?? null) === $name, 'Target activation terverifikasi sebagai database disposable');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $migrator = app('migrator');
    $migrationNames = ['2026_10_19_000003_create_purchase_return_drafts', '2026_10_21_000001_add_purchase_return_posting'];
    $paths = array_merge($migrator->paths(), [database_path('migrations')]);
    $files = array_diff_key($migrator->getMigrationFiles($paths), array_fill_keys($migrationNames, true));
    $check(Artisan::call('migrate', ['--path' => array_values($files), '--realpath' => true, '--force' => true]) === 0,
        'Baseline project populated tanpa migration Returns');
    $app->detectEnvironment(fn () => 'testing');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach ([UnitsDemoSeeder::class, WarehousesDemoSeeder::class, ItemsDemoSeeder::class,
        SuppliersDemoSeeder::class, PaymentsDemoSeeder::class, PurchaseOrdersAccessSeeder::class,
        PurchaseOrdersDemoSeeder::class, PurchaseOrdersReceiptDemoSeeder::class] as $seeder) {
        app($seeder)->run();
    }
    $check(Artisan::call('migrate', ['--path' => 'app/Modules/Purchasing/Returns/Database/Migrations/2026_10_19_000003_create_purchase_return_drafts.php',
        '--force' => true]) === 0, 'Fondasi Returns existing aktif sebelum migration posting');
    $purchaseId = DB::table('purchase_orders')->where('demo_key', 'cctv-electrical-purchase-v1')->where('status', 'posted')->value('id');
    $purchaseLineId = DB::table('purchase_order_lines')->where('purchase_order_id', $purchaseId)->orderBy('id')->value('id');
    $draftId = app(ManagePurchaseReturnDraft::class)->save(['purchase_order_id' => $purchaseId,
        'lines' => [['purchase_order_line_id' => $purchaseLineId, 'quantity' => '1']]]);
    Permission::findOrCreate('purchasing.returns.view', 'web');
    Permission::findOrCreate('purchasing.returns.manage', 'web');
    $permission = Permission::findOrCreate('qa.activation.baseline', 'web');
    $role = Role::findOrCreate('qa-activation-baseline', 'web');
    $role->givePermissionTo($permission);
    User::factory()->create(['email' => 'qa-activation-'.bin2hex(random_bytes(6)).'@example.test'])->assignRole($role);

    $beforeTables = $snapshotTables();
    $beforePermissions = $snapshotRows('permissions');
    $beforeMigrations = $snapshotRows('migrations');
    $run = static function () use ($activation, $name, $check): void {
        $pipes = [];
        $process = proc_open([PHP_BINARY, $activation, '--qa-database='.$name],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path(), null, ['bypass_shell' => true]);
        if (! is_resource($process)) {
            throw new RuntimeException('Proses aktivasi disposable tidak dapat dimulai.');
        }
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $check(proc_close($process) === 0 && str_contains($output, 'PASS: schema dan tiga permission'),
            'Child aktivasi additive selesai tanpa grant/demo');
    };

    $run();
    $firstPass = $snapshotTables();
    $run();
    $secondPass = $snapshotTables();
    $check($firstPass === $secondPass, 'Aktivasi kedua idempotent pada semua tabel/data existing');
    foreach ($beforeTables as $table => $snapshot) {
        if ($table === 'purchase_returns') {
            $oldColumns = $snapshot['columns'];
            $newColumns = array_values(array_diff($secondPass[$table]['columns'] ?? [], $oldColumns));
            sort($newColumns);
            $expected = ['posted_at', 'posted_by', 'posted_from_version', 'stock_issue_id'];
            sort($expected);
            $oldRows = DB::table($table)->select($oldColumns)->get()->map($hash)->sort()->values()->all();
            $check($newColumns === $expected && $hash($oldRows) === $snapshot['rows'],
                'Draft existing mempertahankan seluruh kolom dan data lama');

            continue;
        }
        $check(($secondPass[$table] ?? null) === $snapshot, 'Tabel/data existing identik: '.$table);
    }
    foreach ($beforePermissions as $id => $value) {
        $check($value === $hash(DB::table('permissions')->where('id', $id)->first()), 'Permission existing identik: '.$id);
    }
    foreach ($beforeMigrations as $id => $value) {
        $check($value === $hash(DB::table('migrations')->where('id', $id)->first()), 'Migration existing identik: '.$id);
    }
    foreach ($migrationNames as $migrationName) {
        $check(DB::table('migrations')->where('migration', $migrationName)->count() === 1,
            'Migration Returns tercatat tepat satu kali: '.$migrationName);
    }
    foreach (['purchasing.returns.view', 'purchasing.returns.manage', 'purchasing.returns.post'] as $permissionName) {
        $permissionId = DB::table('permissions')->where('name', $permissionName)->where('guard_name', 'web')->value('id');
        $check($permissionId !== null && DB::table('permissions')->where('name', $permissionName)->where('guard_name', 'web')->count() === 1,
            'Permission catalog additive: '.$permissionName);
        $check(! DB::table('role_has_permissions')->where('permission_id', $permissionId)->exists()
            && ! DB::table('model_has_permissions')->where('permission_id', $permissionId)->exists(),
            'Permission tidak otomatis di-grant: '.$permissionName);
    }
    $check(DB::table('purchase_orders')->where('demo_key', 'cctv-electrical-purchase-v1')->where('status', 'posted')->exists(),
        'Purchase source baseline tetap populated');
    $check(DB::table('purchase_returns')->where('id', $draftId)->where('status', 'draft')->count() === 1
        && DB::table('purchase_return_lines')->where('purchase_return_id', $draftId)->count() === 1,
        'Draft Returns existing tetap utuh setelah dua aktivasi');
} finally {
    DB::purge('purchase_returns_activation_qa');
    if ($created) {
        $server->exec('DROP DATABASE `'.$name.'`');
        echo 'PASS: database aktivasi disposable dibersihkan; database bisnis tidak dimutasi.'.PHP_EOL;
    }
}
