<?php

use App\Modules\Sales\Returns\Database\Seeders\ReturnsAccessSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('local') || config('database.default') !== 'mysql' || ! Schema::hasTable('sales_invoices')) {
    throw new LogicException('Aktivasi hanya local/MySQL dengan baseline invoice posted.');
}
$hash = static fn ($value) => hash('sha256', json_encode($value, JSON_THROW_ON_ERROR));
$before = [];
foreach (Schema::getTableListing(schema: DB::getDatabaseName(), schemaQualified: false) as $table) {
    if (in_array($table, ['cache', 'cache_locks', 'permissions', 'migrations'], true)) {
        continue;
    }
    $before[$table] = $hash(DB::table($table)->get()->map($hash)->sort()->values()->all());
}
$permissions = DB::table('permissions')->get()->mapWithKeys(fn ($row) => [$row->id => $hash($row)])->all();
$migrations = DB::table('migrations')->get()->mapWithKeys(fn ($row) => [$row->id => $hash($row)])->all();
$path = 'app/Modules/Sales/Returns/Database/Migrations/2026_10_18_000001_create_sales_return_drafts.php';
if (Artisan::call('migrate', ['--path' => [$path], '--force' => true]) !== 0) {
    throw new RuntimeException('Migration retur gagal. Tidak ada demo/grant dijalankan.');
}
DB::transaction(function () use ($before, $permissions, $migrations, $hash): void {
    app(ReturnsAccessSeeder::class)->run();
    foreach ($before as $table => $value) {
        if ($value !== $hash(DB::table($table)->get()->map($hash)->sort()->values()->all())) {
            throw new RuntimeException('Data existing berubah; rollback catalog.');
        }
    }
    foreach (['permissions' => $permissions, 'migrations' => $migrations] as $table => $rows) {
        foreach ($rows as $id => $value) {
            if ($value !== $hash(DB::table($table)->where('id', $id)->first())) {
                throw new RuntimeException('Metadata existing berubah; rollback catalog.');
            }
        }
    }
});
echo "PASS: migration module + catalog permission, tanpa demo/grant/reset/transaksi.\n";
echo "PASS: data/master/user/role/ledger/audit dan metadata existing identik.\n";
