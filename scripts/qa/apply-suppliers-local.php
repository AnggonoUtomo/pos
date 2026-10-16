<?php

use App\Modules\Purchasing\Suppliers\Database\Seeders\SuppliersAccessSeeder;
use App\Modules\Purchasing\Suppliers\Database\Seeders\SuppliersDemoSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('local') || config('database.default') !== 'mysql') {
    throw new LogicException('Aktivasi Supplier hanya MySQL local.');
}
$hash = static fn ($row) => hash('sha256', json_encode($row, JSON_THROW_ON_ERROR));
$tables = Schema::getTableListing(schema: DB::getDatabaseName(), schemaQualified: false);
$additive = ['suppliers', 'activity_log', 'permissions'];
$existing = [];
$fingerprints = [];
foreach ($tables as $table) {
    if (in_array($table, ['migrations', 'cache', 'cache_locks'], true)) {
        continue;
    }
    if (in_array($table, $additive, true)) {
        $existing[$table] = DB::table($table)->get()->mapWithKeys(fn ($row) => [$row->id => $hash($row)])->all();
    } else {
        $rows = DB::table($table)->get()->map($hash)->sort()->values()->all();
        $fingerprints[$table] = $hash($rows);
    }
}
if (Artisan::call('migrate', ['--path' => 'app/Modules/Purchasing/Suppliers/Database/Migrations', '--force' => true]) !== 0) {
    throw new RuntimeException('Migration Supplier gagal.');
}
echo Artisan::output();
DB::transaction(function () use ($hash, $existing, $fingerprints): void {
    app(SuppliersAccessSeeder::class)->run();
    app(SuppliersDemoSeeder::class)->run();
    foreach ($fingerprints as $table => $before) {
        $rows = DB::table($table)->get()->map($hash)->sort()->values()->all();
        if ($before !== $hash($rows)) {
            throw new RuntimeException('Fingerprint existing berubah; rollback seed Supplier.');
        }
    }
    foreach ($existing as $table => $rows) {
        foreach ($rows as $id => $before) {
            if ($before !== $hash(DB::table($table)->where('id', $id)->first())) {
                throw new RuntimeException('Data existing berubah; rollback seed Supplier.');
            }
        }
    }
});
echo "PASS: schema/catalog/demo Supplier additive; semua baris lama akun/grant/master/ledger/audit identik.\n";
echo 'PASS: '.DB::table('suppliers')->count()." supplier tersedia; tidak membuat transaksi atau reset akun/password.\n";
