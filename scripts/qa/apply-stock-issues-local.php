<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('local') || config('database.default') !== 'mysql') {
    throw new LogicException('Aktivasi hanya MySQL local, additive dan tanpa demo pengeluaran.');
}
$tables = ['users', 'roles', 'permissions', 'model_has_roles', 'model_has_permissions', 'role_has_permissions', 'warehouses', 'units', 'items', 'item_units',
    'customer_levels', 'customers', 'item_prices', 'stock_openings', 'stock_opening_lines', 'stock_balances', 'stock_movements', 'fifo_layers',
    'payment_accounts', 'payment_methods', 'activity_log', 'stock_issues', 'stock_issue_lines', 'fifo_consumptions'];
$snapshot = static function (string $table, array $columns): string {
    $hashes = DB::table($table)->select($columns)->get()->map(fn ($row) => hash('sha256', json_encode($row, JSON_THROW_ON_ERROR)))->all();
    sort($hashes);

    return hash('sha256', json_encode($hashes, JSON_THROW_ON_ERROR));
};
$before = $columns = [];
foreach ($tables as $table) {
    if (Schema::hasTable($table)) {
        $columns[$table] = Schema::getColumnListing($table);
        $before[$table] = $snapshot($table, $columns[$table]);
    }
}
if (Artisan::call('migrate', ['--path' => 'app/Modules/Inventory/Stock/Database/Migrations', '--force' => true]) !== 0) {
    throw new RuntimeException('Migration Stock gagal. Tidak ada demo/reset dijalankan.');
}
echo Artisan::output();
foreach ($before as $table => $hash) {
    if ($hash !== $snapshot($table, $columns[$table])) {
        throw new RuntimeException('Fingerprint kolom existing berubah. Hentikan aktivasi; jangan down schema berisi data.');
    }
}
foreach (['stock_issues', 'stock_issue_lines', 'fifo_consumptions'] as $table) {
    if (! isset($before[$table]) && DB::table($table)->exists()) {
        throw new RuntimeException('Tabel pengeluaran baru seharusnya kosong.');
    }
}
echo "PASS: migration additive; fingerprint seluruh kolom existing tetap identik, tanpa seeder/grant/reset/pengeluaran stok pengguna.\n";
