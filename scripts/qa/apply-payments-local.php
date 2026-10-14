<?php

use App\Modules\Finance\Payments\Database\Seeders\PaymentsAccessSeeder;
use App\Modules\Finance\Payments\Database\Seeders\PaymentsDemoSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('local') || config('database.default') !== 'mysql') {
    throw new LogicException('Aktivasi master Finance hanya MySQL local.');
}
$snapshot = static function (): array {
    $hashes = [];
    foreach (['users', 'roles', 'model_has_roles', 'model_has_permissions', 'role_has_permissions', 'warehouses', 'units', 'items', 'item_units', 'customer_levels', 'customers',
        'item_prices', 'stock_openings', 'stock_opening_lines', 'stock_balances', 'stock_movements', 'fifo_layers'] as $table) {
        $rows = DB::table($table)->get()->map(fn ($row) => hash('sha256', json_encode($row, JSON_THROW_ON_ERROR)))->all();
        sort($rows);
        $hashes[$table] = hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR));
    }

    return $hashes;
};
$before = $snapshot();
$existing = [];
foreach (['payment_accounts', 'payment_methods', 'activity_log', 'permissions'] as $table) {
    if (Schema::hasTable($table)) {
        $existing[$table] = DB::table($table)->get()->mapWithKeys(fn ($row) => [$row->id => hash('sha256', json_encode($row, JSON_THROW_ON_ERROR))])->all();
    }
}
if (Artisan::call('migrate', ['--path' => 'app/Modules/Finance/Payments/Database/Migrations', '--force' => true]) !== 0) {
    throw new RuntimeException('Migration Finance gagal.');
}
echo Artisan::output();
DB::transaction(function () use ($snapshot, $before, $existing): void {
    app(PaymentsAccessSeeder::class)->run();
    app(PaymentsDemoSeeder::class)->run();
    if ($before !== $snapshot()) {
        throw new RuntimeException('Fingerprint existing berubah; rollback master Finance.');
    }
    foreach ($existing as $table => $rows) {
        foreach ($rows as $id => $hash) {
            if ($hash !== hash('sha256', json_encode(DB::table($table)->where('id', $id)->first(), JSON_THROW_ON_ERROR))) {
                throw new RuntimeException('Data existing berubah; rollback demo.');
            }
        }
    }
});
echo 'PASS: '.DB::table('payment_accounts')->count().' akun dan '.DB::table('payment_methods')->count()." metode tersedia; data existing/account/grant/master/stok/ledger/harga tetap identik.\n";
echo "PASS: tidak membuat saldo atau transaksi pembayaran, tidak reset akun/password.\n";
