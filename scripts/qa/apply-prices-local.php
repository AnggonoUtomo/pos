<?php

use App\Modules\Sales\Parties\Database\Seeders\ItemPricesDemoSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('local') || config('database.default') !== 'mysql') {
    throw new LogicException('Penerapan harga demo hanya MySQL local.');
}
$tables = ['users', 'roles', 'permissions', 'model_has_roles', 'model_has_permissions', 'role_has_permissions',
    'warehouses', 'units', 'items', 'item_units', 'customer_levels', 'customers',
    'stock_openings', 'stock_opening_lines', 'stock_balances', 'stock_movements', 'fifo_layers'];
$fingerprint = static function () use ($tables): array {
    $hashes = [];
    foreach ($tables as $table) {
        $rows = DB::table($table)->get()->map(fn ($row) => hash('sha256', json_encode($row, JSON_THROW_ON_ERROR)))->all();
        sort($rows);
        $hashes[$table] = hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR));
    }

    return $hashes;
};
$before = $fingerprint();
$existingPrices = Schema::hasTable('item_prices') ? DB::table('item_prices')->get()->keyBy('id') : collect();
Artisan::call('migrate', ['--path' => 'app/Modules/Sales/Parties/Database/Migrations', '--force' => true]);
echo Artisan::output();
DB::transaction(function () use ($fingerprint, $before, $existingPrices): void {
    app(ItemPricesDemoSeeder::class)->run();
    if ($before !== $fingerprint()) {
        throw new RuntimeException('Fingerprint akun/grant/master/ledger berubah; rollback harga.');
    }
    foreach ($existingPrices as $id => $row) {
        if (json_encode($row) !== json_encode(DB::table('item_prices')->where('id', $id)->first())) {
            throw new RuntimeException('Harga existing berubah; rollback demo.');
        }
    }
});
echo 'PASS: '.DB::table('item_prices')->count()." harga tersedia; akun/grant/master/stok/ledger dan harga existing identik.\n";
