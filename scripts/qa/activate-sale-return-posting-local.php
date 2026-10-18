<?php

use App\Modules\Sales\Returns\Database\Seeders\ReturnsAccessSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('local') || config('database.default') !== 'mysql') {
    throw new LogicException('Aktivasi hanya local/MySQL.');
}
foreach (['sales_returns', 'stock_receipts', 'fifo_consumptions'] as $table) {
    if (! Schema::hasTable($table)) {
        throw new LogicException('Baseline transaksi belum lengkap.');
    }
}
$hash = static fn ($value) => hash('sha256', json_encode($value, JSON_THROW_ON_ERROR));
$before = [];
foreach (Schema::getTableListing(schema: DB::getDatabaseName(), schemaQualified: false) as $table) {
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
$paths = ['app/Modules/Inventory/Stock/Database/Migrations/2026_10_18_000002_create_stock_return_allocations.php',
    'app/Modules/Sales/Returns/Database/Migrations/2026_10_18_000003_add_sales_return_posting.php'];
if (Artisan::call('migrate', ['--path' => $paths, '--force' => true]) !== 0) {
    throw new RuntimeException('Migration gagal; demo/grant tidak dijalankan.');
}
DB::transaction(function () use ($before, $metadata, $hash): void {
    app(ReturnsAccessSeeder::class)->run();
    foreach ($before as $table => $snapshot) {
        if ($snapshot['hash'] !== $hash(DB::table($table)->get($snapshot['columns'])->map($hash)->sort()->values()->all())) {
            throw new RuntimeException('Data existing berubah; rollback catalog.');
        }
    }
    foreach ($metadata as $table => $rows) {
        foreach ($rows as $id => $value) {
            if ($value !== $hash(DB::table($table)->where('id', $id)->first())) {
                throw new RuntimeException('Metadata existing berubah; rollback catalog.');
            }
        }
    }
});
echo "PASS: schema posting retur dan catalog saja, tanpa demo/grant/reset/posting.\n";
echo "PASS: kolom/data existing, user/role, source invoice/Finance, stok dan audit identik.\n";
