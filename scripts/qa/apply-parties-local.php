<?php

use App\Modules\Sales\Parties\Database\Seeders\PartiesAccessSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('local') || config('database.default') !== 'mysql') {
    throw new LogicException('Penerapan additive ini hanya untuk MySQL local.');
}
$snapshot = static function (): array {
    $hashes = [];
    foreach (['users', 'roles', 'model_has_roles', 'model_has_permissions', 'role_has_permissions',
        'warehouses', 'units', 'items', 'item_units', 'stock_openings', 'stock_opening_lines',
        'stock_balances', 'stock_movements', 'fifo_layers', 'customer_levels', 'customers'] as $table) {
        if (! Schema::hasTable($table)) {
            continue;
        }
        $rows = DB::table($table)->get()->map(static fn ($row): string => hash('sha256', json_encode($row, JSON_THROW_ON_ERROR)))->all();
        sort($rows);
        $hashes[$table] = hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR));
    }

    return $hashes;
};
$before = $snapshot();
Artisan::call('migrate', ['--path' => 'app/Modules/Sales/Parties/Database/Migrations', '--force' => true]);
echo Artisan::output();
app(PartiesAccessSeeder::class)->run();
$after = $snapshot();
foreach ($before as $table => $hash) {
    if (($after[$table] ?? null) !== $hash) {
        throw new RuntimeException('Data existing berubah; hentikan delivery tanpa reset.');
    }
}
if (! Schema::hasTable('customer_levels') || ! Schema::hasTable('customers')) {
    throw new RuntimeException('Schema Parties belum lengkap.');
}
echo "PASS: schema dan permission catalog tersedia; akun/role/grant/master/ledger existing identik, tanpa demo otomatis.\n";
