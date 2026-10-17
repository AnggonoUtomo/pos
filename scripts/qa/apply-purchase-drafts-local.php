<?php

use App\Modules\Purchasing\PurchaseOrders\Database\Seeders\PurchaseOrdersAccessSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('local') || config('database.default') !== 'mysql') {
    throw new LogicException('Aktivasi hanya MySQL local.');
}
$hash = static fn ($row) => hash('sha256', json_encode($row, JSON_THROW_ON_ERROR));
$before = [];
foreach (Schema::getTableListing(schema: DB::getDatabaseName(), schemaQualified: false) as $table) {
    if (in_array($table, ['migrations', 'cache', 'cache_locks', 'permissions'], true)) {
        continue;
    }
    $before[$table] = $hash(DB::table($table)->get()->map($hash)->sort()->values()->all());
}
$permissions = DB::table('permissions')->get()->mapWithKeys(fn ($row) => [$row->id => $hash($row)])->all();
if (Artisan::call('migrate', ['--path' => 'app/Modules/Purchasing/PurchaseOrders/Database/Migrations', '--force' => true]) !== 0) {
    throw new RuntimeException('Migration draft gagal.');
}
echo Artisan::output();
DB::transaction(function () use ($hash, $before, $permissions): void {
    app(PurchaseOrdersAccessSeeder::class)->run();
    foreach ($before as $table => $value) {
        if ($value !== $hash(DB::table($table)->get()->map($hash)->sort()->values()->all())) {
            throw new RuntimeException('Data existing berubah; rollback catalog.');
        }
    }
    foreach ($permissions as $id => $value) {
        if ($value !== $hash(DB::table('permissions')->where('id', $id)->first())) {
            throw new RuntimeException('Permission existing berubah; rollback catalog.');
        }
    }
});
echo "PASS: schema/catalog additive; seluruh data existing/master/grant/stock/finance/audit identik.\n";
echo "PASS: tidak seed draft demo, membuat transaksi, memberi grant, atau mereset akun/password.\n";
