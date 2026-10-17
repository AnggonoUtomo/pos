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
    $columns = Schema::getColumnListing($table);
    $before[$table] = ['columns' => $columns, 'hash' => $hash(DB::table($table)->select($columns)->get()->map($hash)->sort()->values()->all())];
}
$permissions = DB::table('permissions')->get()->mapWithKeys(fn ($row) => [$row->id => $hash($row)])->all();
foreach (['app/Modules/Finance/Payments/Database/Migrations', 'app/Modules/Purchasing/PurchaseOrders/Database/Migrations'] as $path) {
    if (Artisan::call('migrate', ['--path' => $path, '--force' => true]) !== 0) {
        throw new RuntimeException('Migration pembayaran gagal.');
    }
    echo Artisan::output();
}
DB::transaction(function () use ($before, $permissions, $hash): void {
    app(PurchaseOrdersAccessSeeder::class)->run();
    foreach ($before as $table => $snapshot) {
        if ($snapshot['hash'] !== $hash(DB::table($table)->select($snapshot['columns'])->get()->map($hash)->sort()->values()->all())) {
            throw new RuntimeException('Data existing berubah; rollback catalog.');
        }
    }
    foreach ($permissions as $id => $value) {
        if ($value !== $hash(DB::table('permissions')->where('id', $id)->first())) {
            throw new RuntimeException('Permission lama berubah; rollback catalog.');
        }
    }
});
echo "PASS: schema/catalog saja, seluruh kolom/data existing identik.\n";
echo "PASS: tanpa grant/seed/pay/reset akun, stok dan Finance lama aman.\n";
